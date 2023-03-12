<?php
declare(strict_types=1);

/*
 * 	Session handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

/**
 * 	Variables used in session object:
 *
 *  <GUID/>                 		Global Unique Identified: Server unique session id
 *  <Group/>						Record group: ''
 *  <Type/>				    		Record type: DataStore::TYP_DATA
 *  <Created/>						Time of record creation
 *  <LastMod/>			    		Time of last modification
 *  <Data>
 *
 *  ==== General configuration parameter ================================================================================================================
 *
 *   <OneTimeMessage/>         		One time message per session (used in lib/Log.php)
 *
 *  ==== DAV configuration parameter =====================================================================================================================
 *
 *   <DAVSync/>						1= Datastore syncronization done for this session
 *
 *  ==== ActiveSync configuration parameter ==============================================================================================================
 *
 *   <ItemPart NO=n>				<ItemOperations> for ID (NO=part #)
 *   <BodyPart NO=n/>				<Body> part  for ID (NO=Part #)
 *
 *  </Data>
 */

namespace syncgw\lib;

class Session extends XML {

	// module version number
	const VER 			 = 10;

    /**
     * 	Singleton instance of object
     * 	@var Session
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Session {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages 10901-11000
			$log = Log::getInstance();
			$log->setMsg([
					10901 => _('Session [%s] started'),
					10902 => _('Session [%s] restarted'), //3
					10903 => 10902, //3
					10904 => _('Session [%s] prolongated from [%s]'), //3
					10905 => _('Cleanup %d session records'),
			]);

			// create new session record
			self::$_obj->loadXML('<syncgw>'.
					'<GUID/>'.
					'<LUID/>'.
					'<SyncStat>'.DataStore::STAT_OK.'</SyncStat>'.
					'<Group/>'.
					'<Type>'.DataStore::TYP_DATA.'</Type>'.
					'<LastMod>'.time().'</LastMod>'.
					'<Created>'.time().'</Created>'.
					'<CRC/>'.
					'<extID/>'.
					'<extGroup/>'.
					'<Data>'.
						'<DAVSync>0</DAVSync>'.
	    			    '<OneTimeMessage/>'.
					'</Data>'.
				'</syncgw>');

			// register shutdown function
			$srv = Server::getInstance();
			$srv->regShutdown(__CLASS__);

		}

		return self::$_obj;
	}

    /**
	 * 	Shutdown function
	 */
	public function delInstance(): void {

		if (self::$_obj && self::$_obj->getVar('GUID')) {
			$db = DB::getInstance();
	       	$db->Query(DataStore::SESSION, DataStore::UPD, self::$_obj);
        }

		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('Session handler'));
		$xml->addVar('Ver', strval(self::VER));

		if ($status) {
			$cnf = Config::getInstance();
			$xml->addVar('Stat', strval($cnf->getVar(Config::SESSION_TIMEOUT)));
			$xml->addVar('Opt', _('Session timeout (in seconds)'));
		} else {
			$xml->addVar('Opt', _('Data base "Cookie" handler'));
			$xml->addVar('Stat', _('Implemented'));
		}
	}

	/**
	 * 	Create or restart session
	 *
	 * 	@return	TRUE = Ok; FALSE = Error
	 */
	public function mkSession(): bool {

		// session already existent?
		if ($id = parent::getVar('GUID'))
			return TRUE;

		$http = HTTP::getInstance();
		$cnf  = Config::getInstance();
		$log  = Log::getInstance();
		$db   = DB::getInstance();

        // get session id (e.g. 'MAS-127.0.0.1'
        $id = $cnf->getVar(Config::HD).'-'.$http->getHTTPVar('REMOTE_ADDR');

		// are we debugging?
		if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE && //2
			strncmp($id, Util::DBG_PREF, Util::DBG_PLEN) //2
			&& !($cnf->getvar(Config::TRACE) & Config::TRACE_FORCE)  //3
		) //2
			$id = Util::DBG_PREF.$id; //2

		// create full id

		// load request time
		$t = intval($http->getHTTPVar('REQUEST_TIME'));

		// get session timeout
		$w = intval($cnf->getVar(Config::SESSION_TIMEOUT));
		$t = intval($t / $w);

		// old session id
		$oid = $id.'-'.($t - 1);

		// create new session ID
		$id = $id.'-'.$t;

		// try to load "new" session data
		if ($xml = $db->Query(DataStore::SESSION, DataStore::RGID, $id)) {

			// load record
			parent::loadXML($xml->saveXML());

			$log->Msg(Log::DEBUG, 10902, $id); //3

			return TRUE;
		}

		// check for existing session

		// try to load "old" session
		if ($xml = $db->Query(DataStore::SESSION, DataStore::RGID, $oid)) {

			// load record
			parent::loadXML($xml->saveXML());

			$log->Msg(Log::DEBUG, 10904, $oid, $id); //3

			// rewrite new group record
			parent::updVar('GUID', $id);
			parent::updVar('LastMod', strval(time()));

			// create new session record
			$db->Query(DataStore::SESSION, DataStore::ADD, $this);

			// delete old master record
			$db->Query(DataStore::SESSION, DataStore::DEL, $oid);

    		$log->Msg(Log::DEBUG, 10903, $id); //3

			return TRUE;
		}

		// set new session ID
		parent::updVar('GUID', $id);
		$db->Query(DataStore::SESSION, DataStore::ADD, $this);

		$log->Msg(Log::DEBUG, 10901, $id);

		return TRUE;
	}

	/**
	 * 	Create or update session variable
	 *
	 * 	@param	- Variable name
	 * 	@param	- Value; NULL = Don't change value
	 * 	@param	- Handler ID
	 * 	@return	- Old value; NULL = If variable is created
	 */
	public function updSessVar(string $name, ?string $val = NULL, int $hid = 0): ?string {

		// data store specific variable?
		if ($hid) {
			parent::xpath('//DataStore[HandlerID="'.$hid.'"]/.');
			if (parent::getItem() === FALSE)  {
				Debug::Warn('Datastore/HandlerID="'.sprintf('%04X', $hid).'" not found!'); //3
				parent::getVar('Data');
			}
		} else
			parent::getVar('Data');

		if ($val === NULL) {
			$old = parent::getVar($name, FALSE);
			Debug::Msg('Get session variable "'.$name.'"'. //3
				($hid ? ' for "'.Util::HID(Util::HID_TAB, $hid).'"' : '').' - value is "'.(is_array($old) ? 'ARRAY()' : $old).'"'); //3
		} else {
			$old = parent::updVar($name, strval($val), FALSE);
			Debug::Msg('Update session variable "'.$name.'" = "'.(is_array($val) ? 'ARRAY()' : $val).'"'. //3
					   ($hid ? ' for "'.Util::HID(Util::HID_TAB, $hid).'"' : '').' - old value is "'. //3
					   (is_array($old) ? 'ARRAY()' : $old).'"'); //3
		}

		return $old;
	}

	/**
	 * 	Perform session expiration
	 */
	public function Expiration(): void {

		$cnf = Config::getInstance();

	    // delete expired records
		if (!($tme = $cnf->getVar(Config::SESSION_EXP)))
			return;

		// convert hour to seconds
		$tme *= 3600;

		$cnt = 0;
		$db  = DB::getInstance();
		$log = Log::getInstance();

		// delete old session records
		foreach ($db->Query(DataStore::SESSION, DataStore::RIDS) as $gid => $unused) {

			// read record
			if (!($xml = $db->Query(DataStore::SESSION, DataStore::RGID, $gid)))
			    continue;

			// check expiration
			$t = $xml->getVar('LastMod');

			if ($t + $tme < time()) {
				// delete group record
				$db->Query(DataStore::SESSION, DataStore::DEL, $gid);
				$cnt++;
			}
		}
		$unused; // disable Eclipse warining

		if ($cnt)
			$log->Msg(Log::DEBUG, 10905, $cnt);
	}

}

?>
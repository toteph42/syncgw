<?php
declare(strict_types=1);

/*
 * 	User handler class
 *
 * 	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

/**
 * 	Variables used in session object:
 *
 *  <GUID/>                 		User name
 *  <LUID/>							Internal user ID
 *  <SyncStat/>						DataStore::STAT_OK
 *  <Group/>						Record group: ''
 *  <Type/>				    		Record type: DataStore::TYP_DATA
 *  <Created/>						Time of record creation
 *  <LastMod/>			    		Time of last modification
 *  <Data>
 *
 *  ==== General configuration parameter ================================================================================================================
 *
 *   <Logins/>			        	Login counter
 *   <Banned/>			        	User is banned
 *   <ActiveDevice/>	        	Active device name
 *   <EMailPrime/>					Primary e-Mail address
 *
 *   <Device>			        	Device information (created dynamically)
 *    <DeviceId/>			        Device name
 *    ...							Additional device specific parameter (see below)
 *   </Device>
 *
 *   ==== Configuration parameter which can be manually modified =========================================================================================
 *
 *   <EMailSec/>					Secondary e-Mail address (may be multiple)
 *   <SendDisabled> 				Disable client send mail messages feasibility (0-Allowed/1-Forbidden)
 *   								Defaults to "0"
 *   <AccountName/>					The account name for the contact
 *   								Defaults to "010000xxxx000000-nn"
 *   								(x = <LUID> in hex., n = <GUID>)
 *
 *   <DisplayName/>					Display name of the user associated with the given account (e.g. Full name)
 *   								Defaults to "sync*gw user"
 *   <SMTPLoginName/>				The SMTP account name. Defaults to <EMailPrime>
 *   <IMAPLoginName/>				The IMAP account name. Defaults to <EMailPrime>
 *   <Photo/>						The user photography. Defaults to source/syncgw.png
 *
 *  ==== DAV configuration parameter =====================================================================================================================
 *
 *   <Device>			        	Device information (created dynamically)
 *	  <SyncKey ID=""/>				Synchronization key (GID=Group iD)
 *   </Device>
 *
 *  ==== ActiveSync configuration parameter ==============================================================================================================
 *
 *   <OutOfOffice>					Out of office message
 *    <Time/> 						Start time (unix).'/'.End time (unix) or NULL for global property
 *	  <State/>						0 - The Oof property is disabled
 *									1 - The Oof property is enabled
 *    <Message>
 *     <Audience/>					1 - Internal
 *   								2 - Known external user
 *   								3 - Unknown external user
 *	   <Text TYP="TEXT">			Message text (or NULL)
 *	   <Text TYP="HTML">			Message text (or NULL)
 *    <Message/>
 *	 </OutOfOffice>
 *	 <FreeBusy>						Free / busy array
 *									0 Free
 *									1 Tentative
 *									2 Busy
 *									3 Out of Office (OOF)
 *									4 No data
 *	  <Slot/>						start time (unix).'/'.end time (unix).'/'.type
 *	 </FreeBusy>
 *
 *   <Device>			        	Device information (created dynamically)
 *	  <SyncKey ID=""/>				Synchronization key (GID=Group iD)
 *    <DataStore>
 *     <HandlerID/>		        	Handler ID
 *     <Ping>   		    		<Ping> folder
 *      <Group/>					Group ID - see activesync/masHandler.php:PingStat()
 *     </Ping>
 *     <Sync>						Cached <Sync> request
 *      <Group/>					Group ID - see activesync/masSync.php
 *     </Sync>
 *     <Search> 					Cached search record information - see activesync/Handler.php:SearchId()
 *      <Record>					$hid.'/'.$grp.'/'.$gid (LongId)
 *     </Search>
 *     <MoveAlways>					Whether to move the specified conversation, including all future emails
 * 									in the conversation, to the folder specified by the <DstFldId> element
 * 									(Destination <GUID>)
 * 		<CID Int="xx" Ext="">		<ConversationId> (Int= Internal folder <GUID>; Ext=External folder <GUID>)
 * 	   </ModeAlways>
 *    </DataStore>
 *   </Device>
 *
 *  </Data>
 */

namespace syncgw\lib;

class User extends XML {

	// module version number
	const VER = 18;

    /**
     * 	Singleton instance of object
     * 	@var User
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): User {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages 11001-11100
			$log = Log::getInstance();
			$log->setMsg([
					11001 => _('User \'%s\' connected from \'%s\' invalid password'),
					11002 => _('User \'%s\' connected from \'%s\' authorized'),
			        11003 => _('User \'%s\' is banned'),
	 		]);

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

		if (!self::$_obj)
			return;

		// object modified?
		if (self::$_obj->updObj() && self::$_obj->getVar('GUID')) {
			$db = DB::getInstance();
		    $db->Query(DataStore::USER, DataStore::UPD, self::$_obj);
			self::$_obj->updObj(-1);
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

		$xml->addVar('Name', _('User handler'));
		$xml->addVar('Ver', strval(self::VER));

		if (!$status)
			return;
	}

	/**
	 * 	Log in user
	 *
	 * 	@param	- User name
	 * 	@param	- Password
	 * 	@param	- Device name
	 * 	@return	- TRUE = Ok; FALSE =E rror
	 */
	public function Login(string $uid = NULL, string $upw = NULL, ?string $devname = NULL): bool {

		// could we catch user id and password?
		if (!$uid || !$upw)
			return FALSE;

		$unam = $uid;

		// check for e-mail syntax
		if (strpos($uid, '@'))
			list($uid, $host) = explode('@', $uid);
		else
			$host = '';

		Debug::Msg('Login "'.$uid.'" with password "'.$upw.'" from host "'.$host.'" and device "'. //3
				   ($devname ? $devname : 'localhost').'"'); //3

		$log  = Log::getInstance();
		$db   = DB::getInstance();
		$cnf  = Config::getInstance();

		// load user data
		if (!self::loadUsr($uid, $host, $devname))
			return FALSE;

		// perform login
		if (!$upw || !$db->Authorize($uid, $host, $upw)) {
		    if ($upw)
    			$log->Msg(Log::WARN, 11001, $unam, $devname);
		    return FALSE;
 		}

		// update login counter
		$n = parent::getVar('Logins');
		parent::setVal(strval($n + 1));

		// first time login?
	    if (!$n && $cnf->getVar(Config::HD) != 'GUI')
   			$log->Msg(Log::INFO|Log::ONETIME, 11002, $unam, $devname);

		// is device changed?
		if (!$devname || ($act = parent::getVar('ActiveDevice')) == $devname) {
			Debug::Msg('Device not changed'); //3
			return TRUE;
		}
		$act; // disable Eclipse warning

		Debug::Msg('Device changed from "'.$act.'" to "'.$devname.'"'); //3

		// locate "new" device
		if (parent::xpath('//Device[DeviceId="'.$devname.'"]/.')) {
			parent::getItem();
			return TRUE;
		}

	    // create new entry for device
		parent::getVar('Data');
		parent::addVar('Device');
		parent::addVar('DeviceId', $devname);
		$p = parent::savePos();
	    foreach (Util::HID(Util::HID_CNAME, DataStore::DATASTORES, TRUE) as $hid => $unused) {
	        parent::addVar('DataStore');
	        parent::addVar('HandlerID', strval($hid));
			parent::addVar('Ping', '');
	        parent::addVar('Sync', '');
	        parent::addVar('Search', '');
	        parent::addVar('MoveAlways', '');
	    	parent::restorePos($p);
	    }
	    $unused; // disable Eclipse warning

	    parent::updVar('ActiveDevice', $devname);
       	parent::xpath('//Device[DeviceId="'.$devname.'"]/.'); //3
       	parent::getItem(); //3

       	Debug::Msg($this, 'New device "'.$devname.'" assigned to user'); //3

		return TRUE;
	}

	/**
	 * 	Load user data (or create new one), load assignd or default device
	 *
	 * 	@param	- User name
	 * 	@param  - Host name
	 * 	@param	- Device name
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function loadUsr(string $uid, ?string $host = NULL, ?string $devname = NULL): bool {

		// user ID available?
		if (!$uid)
		    return FALSE;

		// normalize user name
		if (strpos($uid, '@') !== FALSE)
			list($uid, ) = explode('@', $uid);

		// check for banned user
		if (parent::getVar('Banned')) {
			$log = Log::getInstance();
			$log->Msg(Log::WARN, 11003, $uid);
		    return FALSE;
		}

		// already loaded?
		if (parent::getVar('GUID') != $uid) {

		    $db = DB::getInstance();

    		// load user data
	       	if (!($doc = $db->Query(DataStore::USER, DataStore::RGID, $uid))) {

	       		// disable record tracing
	       		$cnf = Config::getInstance();
	       		$mod = $cnf->updVar(Config::TRACE, Config::TRACE_OFF);

    			// get new internal user ID
    			$uno = 1;
    			foreach ($db->Query(DataStore::USER, DataStore::RIDS, '') as $id => $unused) {
    				$doc = $db->Query(DataStore::USER, DataStore::RGID, $id);
    				if (($v = $doc->getVar('LUID')) >= $uno)
    					$uno = $v + 1;
    			}
				$unused; // disable Eclipse warning

				// re-enable trace processing
				$cnf->updVar(Config::TRACE, $mod);

				// create default picture
    			$att = Attachment::getInstance();
    			$pic = $att->create(file_get_contents(Util::mkPath('source').DIRECTORY_SEPARATOR.'syncgw.jpg'));

				// create user object
    			parent::loadXML(
    				'<syncgw>'.
    					'<GUID>'.$uid.'</GUID>'.
    					'<LUID>'.$uno.'</LUID>'.
    					'<SyncStat>'.DataStore::STAT_OK.'</SyncStat>'.
    					'<Group/>'.
    					'<Type>'.DataStore::TYP_DATA.'</Type>'.
    					'<LastMod>'.time().'</LastMod>'.
    					'<Created>'.time().'</Created>'.
    					'<CRC/>'.
					    '<extID/>'.
						'<extGroup/>'.
						'<Data>'.
    				      '<Logins>0</Logins>'.
    					  '<Banned>0</Banned>'.
    					  '<SendDisabled>0</SendDisabled>'.
    					  '<AccountName>'.str_replace('xxxx', sprintf('%04X', $uno), '010000xxxx000000-'.$uid).'</AccountName>'.
    					  '<DisplayName>'.$uid.'</DisplayName>'.
    					  '<SMTPLoginName/>'.
    					  '<IMAPLoginName/>'.
    					  '<EMailPrime/>'.
    					  '<Photo>'.$pic.'</Photo>'.
     					  '<ActiveDevice/>'.
    					  '<FreeBusy/>'.
    					'</Data>'.
    				'</syncgw>'
    			);

    			// save user - it's required here because in follow up we only use DataStore::UPD
       			$db->Query(DataStore::USER, DataStore::ADD, $this);
	       	} else
    			parent::loadXML($doc->saveXML());
		}

		if ($host && !parent::getVar('EMailPrime'))
			parent::updVar('EMailPrime', $uid.'@'.$host);

		if (!parent::getVar('SMTPLoginName'))
			parent::updVar('SMTPLoginName', parent::getVar('EMailPrime'));

		if (!parent::getVar('IMAPLoginName'))
			parent::updVar('IMAPLoginName', parent::getVar('EMailPrime'));

		// load device
		if (!$devname)
			$devname = parent::getVar('ActiveDevice');

		// activate device
		if (Debug::$Conf['Script'] != 'User') { //3
			if ($devname) {
    			$dev = Device::getInstance();
	       		$dev->actDev($devname);
		    }
		} //3

		$gid = parent::getVar('GUID'); //3
		$lid = parent::getVar('LUID'); //3
		parent::getVar('syncgw'); //3
		if (Debug::$Conf['Script'] != 'Document')  //3
			Debug::Msg($this, 'User "'.$gid.'" loaded with id "'.$lid.'" for device "'.$devname.'"'); //3

		return TRUE;
	}

	/**
	 * 	Add / update Out-of-Office records
	 * 	Expired record will automatically be deleted
	 *
	 * 	@param  - 0= Delete property; 1= Enable Oof property; 2= Disable Oof property
	 * 	@param 	- 1= Internal, 2= Known external user, 3=´ Unknown external
	 * 	@param 	- Start Unix time stamp.'/'.End Unix time stamp (or NULL for global property)
	 * 	@param 	- Text message (or NULL)
	 * 	@param 	- HTML message (or NULL)
	 */
	public function setOOF(int $mod, int $audience, ?string $slot = NULL, ?string $text = NULL, ?string $html = NULL): void {

		// check property
    	if (!parent::xpath('//OutOfOffice[Time="'.($slot ? $slot : '').'"]/.')) {
			parent::getVar('Data');
    		parent::addVar('OutOfOffice');
    	} else
    		parent::getItem();

    	// delete entry?
    	if (!$mod) {
    		parent::delVar();
    		return;
    	}

		parent::updVar('Time', ($slot ? $slot : ''), FALSE);
		parent::updVar('State', $mod == 1 ? '1' : '0', FALSE);

		parent::xpath('Message[Audience="'.$audience.'"]/Text/.');
		if (!parent::getItem()) {
			parent::addVar('Message');
			parent::addVar('Audience', strval($audience));
			parent::addVar('Text', strval($text), FALSE, [ 'TYP' => 'TEXT' ]);
			parent::addVar('Text', strval($html), FALSE, [ 'TYP' => 'HTML' ]);
		} else {
			while (parent::getItem() !== NULL) {
				if (parent::getAttr('TYP') == 'TEXT')
					parent::setVal(isnull($text) ? '' : $text);
				else
					parent::setVal(isnull($html) ? '' : $html);
			}
		}
 	}

	/**
	 * 	Add / update synchronization key
	 *
	 *	@param 	- GUID
	 *  @param 	- 0 = return value only; n = update value for synckey
	 * 	@return	- Value
	 */
	public function syncKey(string $gid, int $upd = 0): string {

		$n = ($n = parent::getVar('ActiveDevice')) ? '//Device[DeviceId="'.$n.'"]' : '//Data';
		if (!parent::xpath($n.'/SyncKey[@ID="'.$gid.'"]')) {
			parent::xpath($n);
			parent::getItem();
			parent::addVar('SyncKey', $old = '0', FALSE, [ 'ID' => $gid ]);
		} else
			$old = parent::getItem();

		if ($upd)
			parent::setVal($old = strval($old + $upd));

		return $old;
	}

}

?>
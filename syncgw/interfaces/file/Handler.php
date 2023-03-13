<?php
declare(strict_types=1);

/*
 * 	Intermal file data base handler class
 *
 *	@package	sync*gw
 *	@subpackage	File handler
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

/**
 * 	structure of GUID control table:
 * 	[ <GUID> => [ 0 => <LUID>, 1 => <Group>, 2 => <Type>, 3 => <SyncStat> ]]
 */

namespace syncgw\interfaces\file;

use syncgw\lib\Debug; //3
use syncgw\interfaces\DBintHandler;
use syncgw\lib\Config;
use syncgw\lib\DB; //3
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Lock;
use syncgw\lib\Log;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class Handler implements DBintHandler {

	// module version number
	const VER 	  		 = 14;

	// control file name
	const CONTROL 		 = 'FileDB.id';

	// control fields
	const LUID 	  		 = 'LUID';
	const GROUP   		 = 'Group';
	const TYPE	  		 = 'Type';
	const STAT 	  		 = 'Stat';

 	/**
	 * 	Control table
	 *
	 * 	[ File name ]
	 *
	 * 	@var array
	 */
	private $_ctl = [];

    /**
     * 	Singleton instance of object
     * 	@var Handler
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Handler {

		if (!self::$_obj) {
            self::$_obj = new self();

			$log = Log::getInstance();
			$cnf = Config::getInstance();

			// set messages 20001-30100
			$log->setMsg([
					20001 => _('Error creating [%s]'),
			        20002 => _('Document validation failed - cannot save empty XML objects'),
					20003 => _('Invalid XML data in record \'%s\' in data store %s for user (%s)'),
					20804 => _('User not set'),
			]);

			// is handler enabled?
			if (get_class(self::$_obj) == __CLASS__ && $cnf->getVar(Config::DATABASE) != 'file')
				return self::$_obj;

			// get base directory
			if (!($base = $cnf->getVar(Config::FILE_DIR)))
				$base = $cnf->getVar(Config::TMP_DIR);

			// make sure to create root directory
			if (!self::$_obj->_mkDir($base))
				return self::$_obj;

			// create data store path names
			$unam = Util::HID(Util::HID_TAB, DataStore::USER, TRUE);
			foreach (Util::HID(Util::HID_TAB, DataStore::ALL, TRUE) as $k => $v) {
				if ($k & DataStore::SYSTEM) {
					if (!self::$_obj->_mkDir($base.$v))
						return self::$_obj;
					self::$_obj->_ctl[$k] = $base.$v.DIRECTORY_SEPARATOR;
				} elseif ($k & DataStore::DATASTORES)
					self::$_obj->_ctl[$k] = $base.$unam.DIRECTORY_SEPARATOR.'%s'.DIRECTORY_SEPARATOR.$v.DIRECTORY_SEPARATOR;
			}
		}

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('File interface handler'));
		$xml->addVar('Ver', strval(self::VER));

		$class = '\\syncgw\\interfaces\\file\\Admin';
		$class = $class::getInstance();
		$class->getInfo($xml, $status);

		if (!$status)
			return;

		$xml->addVar('Opt', _('Status'));
		$cnf = Config::getInstance();
		if ($cnf->getVar(Config::DATABASE) != 'file') {
			$xml->addVar('Stat', _('Disabled'));
			return;
		}
		$xml->addVar('Stat', _('Enabled'));

		$xml->addVar('Opt', _('Directory path'));
		$xml->addVar('Stat', $cnf->getVar(Config::FILE_DIR));
	}

	/**
	 * 	Perform query on internal data base
	 *
	 * 	@param	- Handler ID
	 * 	@param	- Query command:<fieldset>
	 * 			  DataStore::ADD 	  Add record                             $parm= XML object<br>
	 * 			  DataStore::UPD 	  Update record                          $parm= XML object<br>
	 * 			  DataStore::DEL	  Delete record or group (inc. sub-recs) $parm= GUID<br>
	 * 			  DataStore::RLID     Read single record                     $parm= LUID<br>
	 * 			  DataStore::RGID     Read single record       	             $parm= GUID<br>
	 * 			  DataStore::GRPS     Read all group records                 $parm= None<br>
	 * 			  DataStore::RIDS     Read all records in group              $parm= Group ID or '' for record in base group<br>
	 * 			  DataStore::RNOK     Read recs with SyncStat != STAT_OK     $parm= Group ID
	 * 	@return	- According  to input parameter<fieldset>
	 * 			  DataStore::ADD 	  New record ID or FALSE on error<br>
	 * 			  DataStore::UPD 	  TRUE=Ok; FALSE=Error<br>
	 * 			  DataStore::DEL	  TRUE=Ok; FALSE=Error<br>
	 * 			  DataStore::RLID     XML object; FALSE=Error<br>
	 * 			  DataStore::RGID	  XML object; FALSE=Error<br>
	 * 			  DataStore::GRPS	  [ "GUID" => Typ of record ]<br>
	 * 			  DataStore::RIDS     [ "GUID" => Typ of record ]<br>
	 * 			  DataStore::RNOK     [ "GUID" => Typ of record ]
	 */
	public function Query(int $hid, int $cmd, $parm = '') {

		if (($hid & DataStore::EXT))
			return $cmd & (DataStore::RIDS|DataStore::RNOK|DataStore::GRPS) ? [] : FALSE;

		if (($hid & DataStore::SYSTEM))
			$uid = 0;
		else {
			// get user ID
			$usr = User::getInstance();
			if (!($uid = intval($usr->getVar('LUID')))) {
				if (Debug::$Conf['Script']) //3
					$uid = 11; //3
				else { //3
					$log = Log::getInstance();
					$log->Msg(Log::ERR, 20804);
					return $cmd & (DataStore::RIDS|DataStore::RNOK|DataStore::GRPS) ? [] : FALSE;
				} //3
			}
		}

		// get directory name
		$dir = sprintf($this->_ctl[$hid], $uid);

		// check subdirectories
		if ($hid & DataStore::DATASTORES && !file_exists($dir)) {

			// create user base directory
			if (!self::_mkDir($dir))
				return $cmd & (DataStore::RIDS|DataStore::RNOK|DataStore::GRPS) ? [] : FALSE;

			// create user data store
			if (!self::_mkDir($dir))
				return $cmd & (DataStore::RIDS|DataStore::RNOK|DataStore::GRPS) ? [] : FALSE;
		}

		$dmsg = 'Query('.Util::HID(Util::HID_CNAME, $hid, TRUE).', '.DB::OPS[$cmd].', '. //3
		        (is_object($parm) ? get_class($parm) : $parm).', uid='.$uid.')'; //3

		// set default return value
		$out  = TRUE;

		// build control table
		$gids = [];
		$lids = [];
		$ctl  = sprintf($this->_ctl[$hid].self::CONTROL, $uid);
        $lck  = Lock::getInstance();

		// lock control table
		if ($cmd & (DataStore::ADD|DataStore::UPD|DataStore::DEL))
			$lck->lock($ctl, TRUE);

		// load control table
		if (file_exists($ctl)) {
			if (!($gids = unserialize(file_get_contents($ctl))))
			    $gids = [];
			foreach ($gids as $gid => $v)
				$lids[$v[self::LUID]] = $gid;
		}

		// replace parameter
		switch ($cmd) {
		case DataStore::ADD:
		case DataStore::UPD:

			// add/update <GUID> in control file
			$gid = $parm->getVar('GUID');

			// return "new" record id?
			if ($cmd & DataStore::ADD)
			    $out = $gid;

		    // save control variables
			$gids[$gid][self::LUID]  = $parm->getVar('LUID');
			$gids[$gid][self::GROUP] = $parm->getVar('Group');
			$gids[$gid][self::TYPE]  = $parm->getVar('Type');
			$gids[$gid][self::STAT]  = $parm->getVar('SyncStat');

		    // replace any non a-z, A-Z and 0-9 character with "-" in file name
			$path = $dir.preg_replace('|[^a-zA-Z0-9]+|', '-', $gid).'.xml';
			Debug::Msg($dmsg.'"'.$path.'"'); //3

			// check document consistency
			if ($parm->getVar('syncgw') === NULL)  {
                $log = Log::getInstance();

                $log->Msg(Log::WARN, 20001, $path);
			    if (Debug::$Conf['Script'] ) { //3
                    foreach (ErrorHandler::Stack() as $r) //3
                        $log->Msg(Log::WARN, 10001, $r); //3
                } //3
    			$out = FALSE;
			    break;
    		}

    		// save file
    		if ($parm->saveFile($path, TRUE) === FALSE) {
                $log = Log::getInstance();
                $log->Msg(Log::ERR, 20001, $path);
                DbgXMLError(); //3
                Debug::Save(__FUNCTION__.'%d.xml', $parm->saveXML(TRUE)); //3
                if (Debug::$Conf['Script'] ) { //3
                    foreach (ErrorHandler::Stack() as $r) //3
                        $log->Msg(Log::WARN, 10001, $r); //3
                } //3
    			$out = FALSE;
    		}
			break;

		case DataStore::DEL:
			// do we know record?
			if (!isset($gids[$parm])) {
				$out = FALSE;
				break;
			}
			// delete whole group?
			if (substr($parm, 0, 1) != DataStore::TYP_DATA) {
			    foreach ($gids as $k => $v) {
			        if ($v[self::GROUP] == $parm) {
            		    // replace any non a-z, A-Z and 0-9 character with "-" in file name
            			$path = $dir.preg_replace('|[^a-zA-Z0-9]+|', '-', $k).'.xml';
            			if (file_exists($path))
                            unlink($path);
			             unset($gids[$k]);
			        }
			    }
			}
			// delete record itself
		    // replace any non a-z, A-Z and 0-9 character with "-" in file name
			$path = $dir.preg_replace('|[^a-zA-Z0-9]+|', '-', $parm).'.xml';
			if (file_exists($path))
                unlink($path);
			unset($gids[$parm]);
            Debug::Msg($dmsg.'"'.$path.'"'); //3
			break;

		case DataStore::RGID:
		case DataStore::RLID:
			if ($cmd & DataStore::RGID) {
				if (!isset($gids[$parm])) {
   	 	            $out = FALSE;
    	            break;
				}
			} elseif (!isset($lids[$parm])) {
   	 	        $out = FALSE;
    	        break;
			}
			// replace any non a-z, A-Z and 0-9 character with "-" in file name
			$path = $dir.preg_replace('|[^a-zA-Z0-9]+|', '-', $cmd & DataStore::RGID ? $parm : $lids[$parm]).'.xml';
			Debug::Msg($dmsg.'"'.$path.'"'); //3
			if (!file_exists($path))
			    $out = FALSE;
			elseif (($out = file_get_contents($path)) !== FALSE) {
				$xml = new XML();
				if (!$xml->loadXML($out)) {
				    $id = [];
					// extract <GUID> from record to get reference record number for error message
				    preg_match('#(?<=\<GUID\>).*(?=\</GUID\>)#', NULL, $id);
					ErrorHandler::Raise(20003, $id[0], Util::HID(Util::HID_ENAME, $hid, $uid));
					$out = FALSE;
				} else
					$out = $xml;
			}
			break;

	    case DataStore::GRPS:
			Debug::Msg($dmsg.'"'.$this->_ctl[$hid].'"'); //3
			$out = [];
			foreach ($gids as $gid => $v) {
				if ($v[self::TYPE] == DataStore::TYP_GROUP)
					$out[$gid] = $v[self::TYPE];
			}
			break;

	    case DataStore::RIDS:
			Debug::Msg($dmsg.'"'.$this->_ctl[$hid].'"'); //3
			$out = [];
			foreach ($gids as $gid => $v) {
				if ($parm == $v[self::GROUP])
					$out[$gid] = $v[self::TYPE];
			}
			break;

			case DataStore::RNOK:
			Debug::Msg($dmsg.'"'.$this->_ctl[$hid].'"'); //3
			$out = [];
			foreach ($gids as $gid => $v) {
				if ($parm == $v[self::GROUP] && $v[self::STAT] != DataStore::STAT_OK)
					$out[$gid] = $v[self::TYPE];
			}
			break;

		default:
			$out = FALSE;
			break;
		}

		// update control table
		if ($cmd & (DataStore::ADD|DataStore::UPD|DataStore::DEL)) {
		    if (file_put_contents($ctl, serialize($gids)) === FALSE) {
		        // give write a second chance
		        Util::Sleep();
    			if (file_put_contents($ctl, serialize($gids)) === FALSE)
	       		    $out = FALSE;
		    }
		}

		// unlock contol file
		if ($cmd & (DataStore::ADD|DataStore::UPD|DataStore::DEL))
			$lck->unlock($ctl);

		return $out;
	}

	/**
	 * 	Excute raw SQL query on internal data base
	 *
	 * 	@param	- SQL query string
	 * 	@return	- Result string or []; NULL on error
	 */
	public function SQL(string $query) {}

	/**
	 * 	Create directory
	 *
	 * 	@param	- Directory name
	 * 	@return	- TRUE=Ok; FALSE=Error
	 */
	private function _mkDir(string $dir): bool {

		if (!@is_dir($dir)) {

			if ($base = dirname($dir))
				self::_mkDir($base);

			if (!file_exists($dir)) {
    			if (!@mkdir($dir)) {
    				ErrorHandler::Raise(20001, $dir);
    				$this->_ok = FALSE;
    				return FALSE;
    			}
			}
		}

		return TRUE;
	}

}

?>
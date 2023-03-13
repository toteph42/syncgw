<?php
declare(strict_types=1);

/*
 * 	MySQL data base handler class
 *
 *	@package	sync*gw
 *	@subpackage	mySQL handler
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\mysql;

use syncgw\lib\Debug;//3
use syncgw\interfaces\DBintHandler;
use syncgw\lib\Config;
use syncgw\lib\DB; //3
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Log;
use syncgw\lib\Server;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class Handler implements DBintHandler {

	// module version number
	const VER = 10;

	/**
	 * 	Data base handler
	 * 	@var \mysqli
	 */
	private static $_db = NULL;

	/**
	 * 	Table names
	 * 	@var array
	 */
	private static $_tab = [];

    /**
     * 	Singleton instance of object
     * 	@var Handler
     */
    private static $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance() {

		if (!self::$_obj) {

            self::$_obj = new self();

			$log = Log::getInstance();

			// set messages 20101-20200
			$log->setMsg([
					20101 => _('SQL Error: %s'),
					20102 => 20101,
					20103 => _('User ID for user (%s) not set'),
					20104 => _('Invalid XML data in record \'%s\' in %s data store for user (%s)'),
			]);

			// save table names
			$cnf = Config::getInstance();
			$pre = $cnf->getVar(Config::DB_PREF);
			foreach (Util::HID(Util::HID_TAB, DataStore::ALL, TRUE) as $k => $v)
				self::$_tab[$k] = '`'.$pre.'_'.$v.'`';

			// check data base access parameter
			$conf = [];
			foreach ([ Config::DB_HOST, Config::DB_PORT, Config::DB_USR,
					   Config::DB_UPW, Config::DB_NAME, Config::DB_PREF ] as $k) {
				if (!($conf[$k] = $cnf->getVar($k)))
					return NULL;
			}

			// connect to data base
			Debug::Msg('Connecting to data base "'.$conf[Config::DB_NAME].'" on "'. //3
						$conf[Config::DB_HOST].':'.$conf[Config::DB_PORT].'" with user "'. //3
						$conf[Config::DB_USR].'" and password "'.$conf[Config::DB_UPW].'"'); //3
			self::$_db = new \mysqli($conf[Config::DB_HOST], $conf[Config::DB_USR], $conf[Config::DB_UPW],
										   $conf[Config::DB_NAME], $conf[Config::DB_PORT]);
			if ($msg = \mysqli_connect_error()) {
				$log->Msg(Log::ERR, 20101, $msg);
				return NULL;
			}

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
		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

    	$xml->addVar('Name', _('Improved MySQL data base handler'));
		$xml->addVar('Ver', strval(self::VER));

		$class = '\\syncgw\\interfaces\\mysql\\Admin';
		$class = $class::getInstance();
		$class->getInfo($xml, $status);

		if (!$status)
			return;

		$xml->addVar('Opt', _('Status'));
		$cnf = Config::getInstance();
		if (($v = $cnf->getVar(Config::DATABASE)) == 'mysql')
			$xml->addVar('Stat', _('Enabled'));
		elseif ($v == 'file')
			$xml->addVar('Stat', _('Disabled'));
		else
			$xml->addVar('Stat', _('Sustentative'));
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
	 * 			  DataStore::RIDS     [ "GUID" => Typ of record ]<br>
	 * 			  DataStore::GRPS	  [ "GUID" => Typ of record ]<br>
	 * 			  DataStore::RNOK     [ "GUID" => Typ of record ]
	 */
	public function Query(int $hid, int $cmd, $parm = NULL) {

		if (!self::$_db|| ($hid & DataStore::EXT))
			return ($cmd & (DataStore::RIDS|DataStore::RNOK|DataStore::GRPS)) ? [] : FALSE;

		if (($hid & DataStore::SYSTEM))
			$uid = '0';
		else {

			// get user ID
			$usr = User::getInstance();
			if (!($uid = $usr->getVar('LUID'))) {
				if (Debug::$Conf['Script']) //3
					$uid = '11'; //3
				else { //3
					$log = Log::getInstance();
					$log->Msg(Log::ERR, 20103, $usr->getVar('GUID'));
					return $cmd & (DataStore::RIDS|DataStore::RNOK|DataStore::GRPS) ? [] : FALSE;
				} //3
			}
		}

		// replace parameter
		switch ($cmd) {
		case DataStore::ADD:
			foreach ([ //3
				'Uid' 		=> $uid, //3
				'GUID'		=> $parm->getVar('GUID'), //3
				'LUID'		=> $parm->getVar('LUID'), //3
				'Group'		=> $parm->getVar('Group'),  //3
				'Type'		=> $parm->getVar('Type'),  //3
				'SyncStat'	=> $parm->getVar('SyncStat'),  //3
				'XML'		=> $parm->saveXML(TRUE), ] as $key => $var) //3
				if (!is_string($var)) { //3
					Debug::Err($parm, 'ADD: Variable "'.$key.'" in "'.self::$_tab[$hid].'" has value "'.$var.'"'); //3
					$parm->addVar($key, ''); //3
				} //3
			$qry = 'INSERT '.self::$_tab[$hid].
			       ' SET '.
			       '   `Uid` = '.$uid.','.
				   '   `GUID` = "'.self::$_db->real_escape_string($out = $parm->getVar('GUID')).'",'.
				   '   `LUID` = "'.self::$_db->real_escape_string($parm->getVar('LUID')).'",'.
				   '   `Group` = "'.self::$_db->real_escape_string($parm->getVar('Group')).'",'.
				   '   `Type` = "'.self::$_db->real_escape_string($parm->getVar('Type')).'",'.
				   '   `SyncStat` = "'.self::$_db->real_escape_string($parm->getVar('SyncStat')).'",'.
				   '   `XML` = "'.self::$_db->real_escape_string($parm->saveXML(TRUE)).'"';
			return self::_query($hid, $cmd, $qry) ? $out : FALSE;

		case DataStore::UPD:
			foreach ([  //3
				'Uid' 		=> $uid,  //3
				'GUID'		=> $parm->getVar('GUID'),  //3
				'LUID'		=> $parm->getVar('LUID'),  //3
				'Group'		=> $parm->getVar('Group'),  //3
				'Type'		=> $parm->getVar('Type'),  //3
				'SyncStat'	=> $parm->getVar('SyncStat'),  //3
				'XML'		=> $parm->saveXML(TRUE), ] as $key => $var) //3
				if (!is_string($var)) { //3
					$parm->setTop(); //3
					Debug::Err($parm, 'UPD: Variable "'.$key.'" in "'.self::$_tab[$hid].'" has value "'.$var.'"'); //3
					$parm->updVar($key, ''); //3
				} //3
			$qry = 'UPDATE '.self::$_tab[$hid].
				   ' SET'.
				   '   `LUID` = "'.self::$_db->real_escape_string($parm->getVar('LUID')).'",'.
				   '   `Type` = "'.self::$_db->real_escape_string($parm->getVar('Type')).'",'.
				   '   `SyncStat` = "'.self::$_db->real_escape_string($parm->getVar('SyncStat')).'",'.
				   '   `Group` = "'.self::$_db->real_escape_string($parm->getVar('Group')).'",'.
				   '   `XML` = "'.self::$_db->real_escape_string($parm->saveXML(TRUE)).'"'.
				   ' WHERE `Uid` = "'.$uid.'"'.
				   ' AND `GUID` = "'.self::$_db->real_escape_string($parm->getVar('GUID')).'"';
			return self::_query($hid, $cmd, $qry);

		case DataStore::DEL:
			foreach ([  //3
				'Uid' 		=> $uid,  //3
				'GUID'		=> $parm, ] as $key => $var) //3
				if (!is_string($var)) { //3
					Debug::Err($parm, 'DEL: Variable "'.$key.'" in "'.self::$_tab[$hid].'" has value "'.$var.'"'); //3
					$parm = strval($parm); //3
				} //3
			$qry = 'DELETE FROM '.self::$_tab[$hid].
			       '  WHERE `Uid` = "'.$uid.'"'.
				   '  AND `GUID` = "'.self::$_db->real_escape_string($parm).'"';
			return self::_query($hid, $cmd, $qry);

		case DataStore::RGID:
			foreach ([  //3
				'Uid' 		=> $uid,  //3
				'GUID'		=> $parm, ] as $key => $var) //3
				if (!is_string($var)) { //3
					Debug::Err($parm, 'RGID: Variable "'.$key.'" in "'.self::$_tab[$hid].'" has value "'.$var.'"'); //3
					$parm = strval($parm); //3
				} //3
			$qry = 'SELECT `XML` FROM '.self::$_tab[$hid].
				   '  WHERE `Uid` = "'.$uid.'"'.
			   	   '  AND `GUID` = "'.self::$_db->real_escape_string($parm).'"';
			if (!($str = self::_query($hid, $cmd, $qry)))
				return FALSE;
			break;

		case DataStore::RLID:
			foreach ([  //3
				'Uid' 		=> $uid,  //3
				'GUID'		=> $parm, ] as $key => $var) //3
				if (!is_string($var)) { //3
					Debug::Err($parm, 'RLID: Variable "'.$key.'" in "'.self::$_tab[$hid].'" has value "'.$var.'"'); //3
					$parm = strval($parm); //3
				} //3
			$qry = 'SELECT `XML` FROM '.self::$_tab[$hid].
				   '  WHERE `Uid` = "'.$uid.'"'.
				   '  AND `LUID` = "'.self::$_db->real_escape_string($parm).'"';
			if (!($str = self::_query($hid, $cmd, $qry)))
				return FALSE;
			break;

		case DataStore::GRPS:
			$qry = 'SELECT `GUID`, `Type` FROM '.self::$_tab[$hid].
			 	   '  WHERE `Uid` = "'.$uid.'"'.
				   '  AND `Type` = "'.DataStore::TYP_GROUP.'"';
			$out = [];
			$gid = 0;
			$id  = TRUE;
			foreach (self::_query($hid, $cmd, $qry) as $k) {
				if (!$id) {
					$out[$gid] = $k;
					$id  = TRUE;
				} else {
					$gid = $k;
					$id  = FALSE;
				}
			}
			return $out;

		case DataStore::RIDS:
			foreach ([  //3
				'Uid' 		=> $uid,  //3
				'Group'		=> $parm, ] as $key => $var) //3
				if (!is_string($var)) { //3
					Debug::Err($parm, 'GRPS: Variable "'.$key.'" in "'.self::$_tab[$hid].'" has value "'.$var.'"'); //3
					$parm = strval($parm); //3
				} //3
			$qry = 'SELECT `GUID`, `Type` FROM '.self::$_tab[$hid].
			 	   '  WHERE `Uid` = "'.$uid.'"'.
				   '  AND `Group` = "'.self::$_db->real_escape_string($parm).'"';
			$out = [];
			$gid = 0;
			$id  = TRUE;
			foreach (self::_query($hid, $cmd, $qry) as $k) {
				if (!$id) {
					$out[$gid] = $k;
					$id  = TRUE;
				} else {
					$gid = $k;
					$id  = FALSE;
				}
			}
			return $out;

		case DataStore::RNOK:
			foreach ([  //3
				'Uid' 		=> $uid,  //3
				'Group'		=> $parm ] as $key => $var) //3
				if (!is_string($var)) { //3
					Debug::Err($parm, 'RNOK: Variable "'.$key.'" in "'.self::$_tab[$hid].'" has value "'.$var.'"'); //3
					$parm = strval($parm); //3
				} //3
			$qry = 'SELECT GUID, Type FROM '.self::$_tab[$hid].
				   '  WHERE `Uid` = "'.$uid.'"'.
				   '  AND `SyncStat` <> "'.DataStore::STAT_OK.'"'.
				   '  AND `Group` = "'.self::$_db->real_escape_string($parm).'"';
			$out = [];
			$gid = '';
			$id  = TRUE;
			foreach (self::_query($hid, $cmd, $qry) as $k) {
				if (!$id) {
					$out[$gid] = $k;
					$id  = TRUE;
				} else {
					$gid = $k;
					$id  = FALSE;
				}
			}
			return $out;

		default:
		    return FALSE;
		}

		$xml = new XML();
		if (!$xml->loadXML($str)) {
			$id = [];
			// extract <GUID> from record to get reference record number for error message
			preg_match('#(?<=\<GUID\>).*(?=\</GUID\>)#', '', $id);
			ErrorHandler::Raise(20103, isset($id[0]) ? $id[0] : '', Util::HID(Util::HID_ENAME, $hid), $uid);
			return FALSE;
		}

		return $xml;
	}

	/**
	 * 	Excute raw SQL query on internal data base
	 *
	 * 	@param	- SQL query string
	 * 	@return	- Result string or []; NULL on error
	 */
	public function SQL(string $query) {
		return self::_query(0, 0, $query);
	}

	/**
	 * 	Execute query
	 *
	 * 	@param	- Handler ID
	 * 	@param	- Query command
	 * 	@param	- Query string
	 * 	@return	- String or []; NULL=Error
	 */
	private function _query(int $hid, int $cmd, string $qry) {

		$dmsg = ($cmd ? DB::OPS[$cmd] : 'SQL').': '.preg_replace('/(?<=<syncgw>).*(?=<\/syncgw>)/', 'XML-Data', $qry); //3

		// lock table
		if ($cmd & (DataStore::ADD|DataStore::UPD|DataStore::DEL))
			self::_query($hid, 0, 'LOCK TABLES '.self::$_tab[$hid].' WRITE;');

		// return value
		$out = NULL;
		$cnf = Config::getInstance();
		$cnt = $cnf->getVar(Config::DB_RETRY);

		do {
			if (($obj = self::$_db->query($qry)) === FALSE) {
				$msg = self::$_db->connect_error ?
					   '['.self::$_db->connect_errno.'] '.self::$_db->connect_error :
					   '['.self::$_db->errno.'] ('.self::$_db->error.'), SQLSTATE: '.self::$_db->sqlstate;

				// [1146] (42S02): Table 'xxx' doesn't exist -> table is not locked
				if (self::$_db->errno == '1146' && !$cmd)
					return NULL;

				// [2006] MySQL server has gone away
				if (self::$_db->errno == '2006') {
					if ($cnt--) {
						Util::Sleep(300);
						$log = Log::getInstance();
						$log->Msg(Log::DEBUG, 20103, $msg);
					}
				} else {
					$cnt = 0;
					$log = Log::getInstance();
					$log->Msg(Log::ERR, 20102, $msg);
					foreach (ErrorHandler::Stack() as $rec)
						$log->Msg(Log::DEBUG, 11601, $rec);

					$out = $cmd & (DataStore::RIDS|DataStore::RNOK|DataStore::GRPS) ? [] : NULL;
				}
			} else {

				$cnt = 0;

				// do not save return data for LOCK and UNLOCK directives
				if (strncmp($qry, 'LOCK', 4) && strncmp($qry, 'UNLO', 4)) {
					Debug::Msg($dmsg); //3
					if (is_object($obj)) {
						$wrk = [];
						if (!$cmd) {
							while ($row = $obj->fetch_assoc())
								$wrk[] = $row;
						} else {
							while ($row = $obj->fetch_row())
								$wrk = array_merge($wrk, $row);
						}
						$obj->free();
						$out = [];
						foreach ($wrk as $rec) {
							// did we receive array as return value?
							if (is_array($rec)) {
								$out[] = $rec;
								continue;
							}
							if (substr($rec, 0, 1) == '<')
								$out = $rec;
							else
								$out[] = $rec;
						}
					} else
					$out = $obj;
				}
			}
		} while ($cnt);

		if ($cmd & (DataStore::RGID|DataStore::RLID) && is_array($out) && count($out))
		    $out = strval($out[0]);

		// unlock table?
		if ($cmd & (DataStore::ADD|DataStore::UPD|DataStore::DEL))
			self::_query($hid, 0, 'UNLOCK TABLES;');

		// check for empty records
		if ($cmd & (DataStore::RGID|DataStore::RLID) && is_bool($out))
			return FALSE;

		return $out;
	}

}

?>

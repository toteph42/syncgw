<?php
declare(strict_types=1);

/*
 * 	Upgrade handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\upgrade;

use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Log;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\gui\guiHandler;

class updHandler {

	// module version number
	const VER = 4;

    /**
     * 	Singleton instance of object
     * 	@var updHandler
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): updHandler {

		if (!self::$_obj) {
            self::$_obj = new self();

			// set messages 18001-18100
			$log = Log::getInstance();
			$log->setMsg([
					18001 => _('Upgrading document [%s] in %s data store from version %s'),
					18002 => _('Upgrading %s data store  from version %s'),
					18003 => _('Upgrading configuration file from version %s'),
					18004 => _('Error reading [%s]'),
			]);

			// set error filters
			ErrorHandler::filter(E_WARNING, 'updHandler.php', 'opendir');
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

    	$xml->addVar('Name', _('Upgrade handler'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Process upgrade
	 *
	 * 	@param	- Version to upgrade from
	 * 	@return	- TRUE = Ok; FALSE = Fatal error
	 */
	public function Process(string $ver): bool {

		// upgrade configuration file
		foreach (self::_loadclass(FALSE, $ver) as $v => $c) {
			$log = Log::getInstance();
			$log->Msg(Log::INFO, 18003, substr($v, 1, 1).'.'.substr($v, 2, 2).'.'.substr($v, 4, 2));
			if (!$c->upgrade($ver))
				return FALSE;
		}

		// load upgrade classes for data stores
		if (!count($lst = self::_loadclass(TRUE, $ver)))
			return TRUE;

		// user ID array
		$uids = [];

		$db = DB::getInstance();

		// update system data stores
		foreach ([ DataStore::USER, DataStore::SESSION, DataStore::DEVICE, DataStore::ATTACHMENT ] as $hid) {

			$del = [];

			// load load document list
			$ids = [];
			foreach ($db->Query($hid, DataStore::RIDS, '') as $gid => $typ) {
			    if ($typ != DataStore::TYP_DATA)
			     	$ids += $db->Query($hid, DataStore::RIDS, $gid);
			}

			foreach ($ids as $gid => $unused) {

				// load document
				$doc = $db->Query($hid, DataStore::RGID, $gid);
				$ok = TRUE;

				// save user ids
				if ($hid & DataStore::USER)
					$uids[] = $doc->getVar('LUID');

				// 0=Abort execution; 1=Update record; 2=Nothing changed; 3=Skip data store; 4=Delete
				switch (self::updDoc($hid, $doc, $ver, $lst)) {
				case 0:
					return FALSE;

				case 1:
					// did GUID change?
					if ($doc->getVar('GUID') != $gid) {
						$del[] = $gid;
						$db->Query($hid, DataStore::ADD, $doc);
					} else
						$db->Query($hid, DataStore::UPD, $doc);
					break;

				case 4:
					$db->Query($hid, DataStore::DEL, $gid);
					break;

				case 3:
					$ok = FALSE;
                    break;

				case 2:
				default:
					break;
				}
				// skip data store?
				if (!$ok)
					break;
			}
			foreach ($del as $unused => $gid)
				$db->Query($hid, DataStore::DEL, $gid);
		}

		// save current user id
		$usr = User::getInstance();
		$org = $usr->getVar('LUID');

		// now update all data stores
		foreach ($uids as $uid) {

			// set new user ID
			$usr->updVar('LUID', $uid, TRUE);

			// scan all data stroes
			foreach (Util::HID(Util::HID_TAB, DataStore::DATASTORES) as $hid => $v) {

				$del = [];
				$ids = [];
    			foreach ($db->Query($hid, DataStore::RIDS, '') as $gid => $typ) {
	       		    if ($typ != DataStore::TYP_DATA)
			      	$ids += $db->Query($hid, DataStore::RIDS, $gid);
			    }

				foreach ($ids as $gid => $unused) {

					$doc = $db->Query($hid, DataStore::RGID, $gid);
					$ok = TRUE;

					// 0=Abort execution; 1=Update record; 2=Nothing changed; 3=Skip data store; 4=Delete
					switch (self::updDoc($hid, $doc, $ver, $lst)) {
					case 0:
						return FALSE;

					case 1:
						// did GUID change?
						if ($doc->getVar('GUID') != $gid) {
							$del[] = $gid;
							$doc->Query($hid, DataStore::ADD, $doc);
						} else
							$db->Query($hid, DataStore::UPD, $doc);
						break;

					case 4:
						$db->Query($hid, DataStore::DEL, $gid);
						break;

					case 3:
						$ok = FALSE;
						break;

					case 2:
					default:
						break;
					}
					// skip data store?
					if (!$ok)
						break;
				}
				$unused; // disable Eclipse warning
				foreach ($del as $unused => $gid)
					$db->Query($hid, DataStore::DEL, $gid);
			}
		}

		// restore user id
		if ($org)
    		$usr->updVar('LUID', $org, TRUE);

		return TRUE;
	}

	/**
	 * 	Document update function
	 *
	 * 	@param	- Handler ID
	 * 	@param	- Document object
	 * 	@param	- Version to upgrade from
	 * 	@param	- Upgrade class array()
 	 * 	@return	- 0 = Abort execution; 1 = Update record; 2 = Nothing changed; 3 = Skip data store; 4 = Delete record
	 */
	public function updDoc(int $hid, XML &$doc, string $ver, ?array $lst = NULL): int {

		if (!$lst)
			$lst = self::_loadclass(TRUE, $ver);

		$rc = 2;
		foreach ($lst as $v => $c) {
			$rc = 2;
			$v  = strval($v);
			// 0=Abort execution; 1=Update record; 2=Nothing changed; 3=Skip data store
			switch ($r = $c->upgrade($hid, $doc, $ver)) {
			case 1:
				$rc = 1;
				$log = Log::getInstance();
				$log->Msg(Log::INFO, 18001, $doc->getVar('GUID'), Util::HID(Util::HID_ENAME, $hid),
						  substr($v, 0, 1).'.'.substr($v, 1, 2).'.'.substr($v, 3));
				break;

			case 3:
				$log = Log::getInstance();
				$log->Msg(Log::INFO, 18002, Util::HID(Util::HID_ENAME, $hid),
						  substr($v, 0, 1).'.'.substr($v, 1, 2).'.'.substr($v, 3));

			case 0:
				$gui = guiHandler::getInstance();
				$gui->putMsg(_('Upgrading <strong>sync&bull;gw</strong> data stores to this version is not supported! ').
							 _('For more information please check version announcement in our forum'), Util::CSS_ERR);
				return $r;

			case 2:
			default:
				break;
			}
		}
		return $rc;
	}

	/**
	 * 	Load upgrade class list
	 *
	 * 	@param 	- TRUE = Load upgrade DB files list; FALSE = Load upgrade config file list
	 * 	@param	- Version to check
	 * 	@return	- List of classes to call
	 */
	private function _loadclass(bool $mod, string $ver): array {

		$ver = str_replace('.', '', $ver);
		$lst = [];
		$p = Util::mkPath('upgrade');
		if (!($d = opendir($p))) {
			ErrorHandler::Raise(18004, $p);
			return [];
		}
		// read directory
		while (($f = readdir($d)) !== FALSE) {
			if ($f == '.' || $f == '..')
				continue;
			// typ
			$t = substr($f, 0, 1);
			// version
			$v = substr($f, 1, 5);
			// check upgrade version
			if ($v < $ver)
				continue;
			// check type
			if (($mod && $t != 'D') || (!$mod && $t != 'C'))
				continue;
			// allocate  object
			$c = 'syncgw\\upgrade\\upd'.$t.$v;
			$lst[$f] = new $c();
		}
		closedir($d);

		ksort($lst);

		return $lst;
	}

}
?>
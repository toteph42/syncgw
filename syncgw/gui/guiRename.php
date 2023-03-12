<?php
declare(strict_types=1);

/*
 * 	Rename record
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiRename {

	// module version
	const VER = 13;

    /**
     * 	Singleton instance of object
     * 	@var guiRename
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiRename {

	   	if (!self::$_obj)
            self::$_obj = new self();

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt', _('Rename record plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		$gui = guiHandler::getInstance();
        $rc  = guiHandler::CONT;
        $err = ErrorHandler::getInstance();
        $err->filter(E_WARNING, __FILE__, 'rename');

		switch ($action) {
		case 'ExpRename':
			$hid = intval($gui->getVar('ExpHID'));
			if (!($gid = $gui->getVar('ExpNewName')))
				break;
			$id  = $gui->getVar('ExpGID');

			if ($hid & DataStore::TRACE) {
				$cnf = Config::getInstance();
				$path = $cnf->getVar(Config::TRACE_DIR);
				if (file_exists($path.$id))
					if (file_exists($path.$gid)) {
						$gui->putMsg(_('Destination file already exist'), Util::CSS_ERR);
						$rc = guiHandler::STOP;
						break;
					}
					if (!rename($path.$id, $path.$gid)) {
						$gui->putMsg(_('Error renaming trace file'), Util::CSS_ERR);
						$rc = guiHandler::STOP;
						break;
					}
				// we need to ensure existing session record is deleted to avoid prolongation
				$db = DB::getInstance();
				foreach ([ 'MAS', 'DAV' ] as $pref)
					$db->Query(DataStore::SESSION, DataStore::DEL, $pref.'-'.$id);
				$gui->updVar('Action', 'Explorer');
				$gui->clearAjax();
				$rc = guiHandler::RESTART;
				break;
			}

			$db = DB::getInstance();

			if (strpos($gid, '-') !== FALSE) {
				$gui->putMsg(_('Record IDs are not allowed to contain \'-\' character...'), Util::CSS_ERR);
				$rc = guiHandler::STOP;
				break;
			}
			if (!($doc = $db->Query($hid, DataStore::RGID, $id))) {
				$gui->putMsg(sprintf(_('Record [%s] not found...'), $id), Util::CSS_ERR);
				$rc = guiHandler::STOP;
				break;
			}
			if ($db->Query($hid, DataStore::RGID, $gid)) {
				$gui->putMsg(sprintf(_('Record [%s] already exist...'), $gid), Util::CSS_ERR);
				$rc = guiHandler::STOP;
				break;
			}
			$doc->updVar('GUID', $gid);
			$db->Query($hid, DataStore::ADD, $doc);
			$db->Query($hid, DataStore::DEL, $id);
			$rc = guiHandler::RESTART;
			$gui->updVar('Action', 'Explorer');
			$gui->clearAjax();
			$rc = guiHandler::RESTART;

		default:
			break;
		}

		// allow only during explorer call
		if (substr($a = $gui->getVar('Action'), 0, 3) == 'Exp' && substr($a, 6, 4) != 'Edit' && $gui->getVar('ExpHID')) {
			$gui->putHidden('ExpNewName', '0');
			$gui->setVal($gui->getVar('Button').$gui->mkButton(_('Rename'), _('Rename'),
					'var v = document.getElementById(\'Action\');'.
					'var n = prompt(\''._('Please enter new name').'\');'.
					'if (n != null) {'.
					   'document.getElementById(\'ExpNewName\').value=n;'.
					   'v.value=\'ExpRename\';'.
					'} else'.
					   'v.value=\'Explorer\';'), TRUE);
		}

		return $rc;
	}

}

?>
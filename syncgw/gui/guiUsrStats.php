<?php
declare(strict_types=1);

/*
 * 	Get user statistics
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiUsrStats {

	// module version
	const VER = 4;

    /**
     * 	Singleton instance of object
     * 	@var guiUsrStats
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiUsrStats {

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

		$xml->addVar('Opt', _('User statistics plugin'));
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

		switch ($action) {
		case 'ExpUserStats':
			$gui->updVar('Action', 'Explorer');
			$gui->putMsg(_('User statistics'), Util::CSS_TITLE);
			$gui->putMsg('');
			$db = DB::getInstance();
			foreach ($db->Query(DataStore::USER, DataStore::RIDS, '') as $gid => $unused) {
				$doc = $db->Query(DataStore::USER, DataStore::RGID, $gid);
				$gui->putMsg('<div style="width: 200px; float: left;">'._('User name').'</div><div style="float:left;">'.
							$doc->getVar('GUID').'</div>');
				$gui->putMsg('<div style="width: 200px; float: left;">'._('User ID').'</div><div style="float:left;">'.
							$doc->getVar('LUID').'</div>');
				$gui->putMsg('<div style="width: 200px; float: left;">'._('Last login').'</div><div style="float:left;">'.gmdate('c',
							intval($doc->getVar('LastMod'))).'</div>');
				$gui->putMsg('<div style="width: 200px; float: left;">'._('Number of logins').'</div><div style="float:left;">'.
							$doc->getVar('Logins').'</div>');
				$gui->putMsg('<div style="width: 200px; float: left;">'._('Active device name').'</div><div style="float:left;">'.
							$doc->getVar('ActiveDevice').'</div>');
				$gui->putMsg('');
			}
			$unused; // disable Eclipse warning

		default:
			break;
		}

		if (substr($a = $gui->getVar('Action'), 0, 3) == 'Exp' && substr($a, 6, 4) != 'Edit' && ($gui->getVar('ExpHID') & DataStore::USER))
			$gui->setVal($gui->getVar('Button').$gui->mkButton(_('Stats'), _('Show user statistics'), 'ExpUserStats'));

		return guiHandler::CONT;
	}

}

?>
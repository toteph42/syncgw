<?php
declare(strict_types=1);

/*
 * 	Sync with external data base
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Debug; //3
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiSync {

	// module version
	const VER = 8;

    /**
     * 	Singleton instance of object
     * 	@var guiSync
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiSync {

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

		$xml->addVar('Opt', _('Synchronize external data store plugin'));
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

		// only allowed for administrators
		if (!$gui->isAdmin())
			return guiHandler::CONT;

		$hid = intval($gui->getVar('ExpHID'));

		switch ($action) {
		case 'ExpSync':
			// clear window
			$gui->clearAjax();

			// sync records
			$gui->putMsg('<br /><hr />');
			$ds = Util::HID(Util::HID_CNAME, $hid);
			$ds = $ds::getInstance();
			$ds->syncDS($gui->getVar('ExpGID'));
			Debug::Mod(FALSE); //3
			$gui->putMsg('<br /><hr />');

			// reload explorer view
			$gui->updVar('Action', 'Explorer');
			return guiHandler::RESTART;

		default:
			break;
		}

		// not available for administrators
		if (substr($a = $gui->getVar('Action'), 0, 3) == 'Exp' && substr($a, 6, 4) != 'Edit' &&
			$hid & DataStore::DATASTORES && $_SESSION[$gui->getVar('SessionID')][guiHandler::TYP] == guiHandler::TYP_USR)
			$gui->setVal($gui->getVar('Button').$gui->mkButton(_('Sync'),
						 _('Synchronize external data store records with internal data store'), 'ExpSync'));

		return guiHandler::CONT;
	}

}

?>
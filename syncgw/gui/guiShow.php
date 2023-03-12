<?php
declare(strict_types=1);

/*
 * 	View record
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\XML;

class guiShow {

	// module version
	const VER = 15;

    /**
     * 	Singleton instance of object
     * 	@var guiShow
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiShow {

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

		$xml->addVar('Opt', _('View record plugin'));
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
		$gid = $gui->getVar('ExpGID');
		$hid = intval($gui->getVar('ExpHID'));
		$db  = DB::getInstance();

		switch ($action) {
		case 'ExpRecShow':

			// load record
			if (!($doc = $db->Query($hid, DataStore::RGID, $gid)))
				break;

			$gui->putQBox('<code>'._('XML document').'</code>', '', $doc->mkHTML(), TRUE, 'Msg');

		default:
			break;
		}

		// allow only during explorer call
		if (substr($a = $gui->getVar('Action'), 0, 3) == 'Exp' && substr($a, 6, 4) != 'Edit' && $gid && !($hid & DataStore::TRACE))
			$gui->setVal($gui->getVar('Button').$gui->mkButton(_('View'), _('View internal record'), 'ExpRecShow'));

		return guiHandler::CONT;
	}

}

?>
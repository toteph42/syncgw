<?php
declare(strict_types=1);

/*
 * 	Show change log and help
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\XML;

class guiHelp {

	// module version
	const VER = 6;

    /**
     * 	Singleton instance of object
     * 	@var guiHelp
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiHelp {

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

		$xml->addVar('Opt', _('Help plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		if ($action == 'Init') {
			$gui = guiHandler::getInstance();
			$gui->setVal($gui->getVar('Button').$gui->mkButton(_('Help'), _('FAQ'),
						 'var w = window.open(\'https://github.com/Toteph42/syncgw/\');w.focus();'));
		}
		return guiHandler::CONT;
	}

}

?>
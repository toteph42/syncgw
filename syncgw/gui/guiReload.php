<?php
declare(strict_types=1);

/*
 * 	Reload cache tables
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\XML;

class guiReload {

	// module version
	const VER = 5;

    /**
     * 	Singleton instance of object
     * 	@var guiReload
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiReload {

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

		$xml->addVar('Opt', _('Reload data store plugin'));
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

		if (substr($a = $gui->getVar('Action'), 0, 3) == 'Exp' && substr($a, 6, 4) != 'Edit')
			$gui->updVar('Button', $gui->getVar('Button').$gui->mkButton(_('Reload'), _('Reload table'), 'Explorer'));

		return guiHandler::CONT;
	}

}

?>
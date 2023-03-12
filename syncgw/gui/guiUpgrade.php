<?php
declare(strict_types=1);

/*
 * 	Automatic upgrade
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\XML;
use syncgw\upgrade\updHandler;

class guiUpgrade {

	// module version
	const VER = 3;

    /**
     * 	Singleton instance of object
     * 	@var guiUpgrade
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiUpgrade {

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

		$xml->addVar('Opt', _('Automatic upgrade plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		if ($action != 'Init')
			return guiHandler::CONT;

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		// is data base connection established?
		if (!$cnf->getVar(Config::DATABASE))
			return guiHandler::CONT;

		$upd = $cnf->getVar(Config::UPGRADE);
		$ver = $cnf->getVar(Config::VERSION);
		if (!version_compare($upd, $ver))
			return guiHandler::CONT;

		$gui->putMsg(sprintf(_('Starting automatic upgrade from <strong>sync&bull;gw</strong> version %s to version %s'), $upd, $ver));
		$up = updHandler::getInstance();
		if ($rc = $up->Process($upd))
			$gui->putMsg(sprintf(_('Automatic upgrade from version %s to version %s finished'), $upd, $ver));

		// save new version
		if ($rc) {
			$cnf->updVar(Config::UPGRADE, $ver);
			$cnf->saveINI();
		}

		return guiHandler::CONT;
	}

}

?>
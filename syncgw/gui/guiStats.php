<?php
declare(strict_types=1);

/*
 * 	Show statistics
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\XML;

class guiStats {

	// module version
	const VER = 4;

    /**
     * 	Singleton instance of object
     * 	@var guiStats
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiStats {

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

		$xml->addVar('Opt', _('Memory statistics plugin'));
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
		$gui->putHidden('Usage', sprintf('Script usage: %.2f MB', memory_get_usage() / (1024*1024)).
						sprintf(' (peak: %.2f MB)', memory_get_peak_usage() / (1024*1024)));
		return guiHandler::CONT;
	}

}

?>
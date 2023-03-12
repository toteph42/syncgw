<?php
declare(strict_types=1);

/*
 * 	Switch data base back end
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiSwitch {

	// module version
	const VER = 11;

    /**
     * 	Singleton instance of object
     * 	@var guiSwitch
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiSwitch {

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

		$xml->addVar('Opt', _('Switch data base plugin'));
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
		$cnf = Config::getInstance();

		switch ($action) {
		case 'SwitchBE':
			$l = [];
			$dir = Util::mkPath('interfaces');
			if (!($d = @opendir($dir))) {
				$gui->putMsg(sprintf(_('Can\'t open \'%s\''), $dir), Util::CSS_ERR);
				break;
			}
			while (($file = @readdir($d)) !== FALSE) {
				if ($file == '.' || $file == '..' || !is_dir($dir.DIRECTORY_SEPARATOR.$file))
					continue;
				$l[] = $file;
			}
			@closedir($d);
			ksort($l);

			$f = ' <select name="IntSwitch">';
			$be = $cnf->getVar(Config::DATABASE);
			foreach ($l as $file) {
				$s = $file == $be ? ' selected="selected"' : '';
				$f .= '<option'.$s.'>'.$file.'</option>';
			}
			$f .= '</select>';

			// clear command window
			$gui->putQBox(_('Application interface data base'), $f, '', FALSE);
			$gui->putMsg(_('Warning: Switching data base may end up with unexpected results, if data base is not properly '.
						 'initialized.'), Util::CSS_WARN);
			$gui->putMsg(_('Please select "new" data base from list and hit button "Switch" again or hit "Cancel" to cancel change'));
			$gui->updVar('Button', $gui->mkButton(_('Cancel'), _('Return to command selection menu'), 'Config').
							$gui->mkButton(_('Run'), _('Switch data base installation without initialization.'), 'SwitchBEDO'));
			$gui->updVar('Action', 'Config');

			return guiHandler::STOP;

		case 'SwitchBEDO':
			$n = $gui->getVar('IntSwitch');
			switch ($n) {
			case 'myapp':
			case 'mysql':
				$be = $n;
				$db = 'myapp';
				break;

			case 'roundcube':
			case 'mail':
				$be = $n;
				$db = 'mail';
				break;

			case 'file':
				$be = $n;
				$db = '';
				break;

			default:
				$gui->putMsg(sprintf(_('Unknown data base handler \'%s\''), $n), Util::CSS_WARN);
				return guiHandler::CONT;
			}
			$cnf->updVar(Config::DATABASE, $be);
			$cnf->updVar(Config::DB_NAME, $db);

			// save .INI file
			$cnf->saveINI();
			$gui->putMsg(sprintf(_('Active data base switched to \'%s\''), $be), Util::CSS_INFO);
			$gui->putMsg(_('Please check enabled data store handlers'), Util::CSS_INFO);
			$gui->updVar('Action', 'Config');

			return guiHandler::RESTART;

		default:
			break;
		}

		if (substr($action, 0, 4) == 'Conf')
			$gui->updVar('Button', $gui->getVar('Button').
	 					 $gui->mkButton(_('Switch'), _('Switch data base installation without initialization.'), 'SwitchBE'));

		return guiHandler::CONT;
	}

}

?>
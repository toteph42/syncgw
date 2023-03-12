<?php
declare(strict_types=1);

/*
 * 	Create feature list
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiFeatures {

	// module version
	const VER = 3;

    /**
     * 	Singleton instance of object
     * 	@var guiFeatures
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiFeatures {

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

		$xml->addVar('Opt', _('Create list of features plugin'));
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
		case 'Init':
			$gui->putCmd('<input id="Features" '.($gui->getVar('LastCommand') == 'Features' ? 'checked ' : '').'type="radio" name="Command" '.
						 'value="Features" onclick="document.syncgw.submit();"/>&nbsp;'.
						 '<label for="Features">'._('Create list of features').'</label>');
			break;

		case 'Features':

			// collect information
			$srv  = Server::getInstance();
			$xml  = $srv->getInfo(FALSE);
			$out  = '<table><tbody>';
			$parm = '';

			$xml->getChild('syncgw');
			while (($val = $xml->getItem()) !== NULL) {
				switch ($xml->getName()) {
				case 'Ver':
					$parm = 'v'.Server::MVER.sprintf('%03d', $val);
					break;

				case 'Opt':
					$cmd = '&raquo; '.$val;
					break;

				case 'Stat':
					$parm = $val;
					break;

				default:
				// case 'Name':
					$cmd  = '<font style="font-size: larger;"><strong>'.$val.'<strong></font>';
					break;
				}
				if ($parm) {
					if (($p = strpos($parm, '<a')) !== FALSE)
						$parm = substr($parm, 0, $p).'<a style="text-decoration:underline;color:blue" '.substr($parm, $p + 3);
					if (($p = strpos($cmd, '<a')) !== FALSE)
						$cmd = substr($cmd, 0, $p).'<a style="text-decoration:underline;color:blue" '.substr($cmd, $p + 3);
					$out .= '<tr><td>'.$cmd.'</td><td>'.$parm.'</td></tr>'."\n";
					$parm = '';
				}
			}

			$out .= '</tbody></table>';

			$file = Util::mkPath('../downloads/').'Features.md';
			file_put_contents($file, $out);

			$gui->putMsg('');
			$gui->tabMsg(sprintf(_('List of features written to [%s]'), $file), Util::CSS_TITLE);
			$gui->putMsg('');
			break;

		default:
			break;
		}

		return guiHandler::CONT;
	}

}

?>
<?php
declare(strict_types=1);

/*
 * 	View log file
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\XML;

class guiLogFile {

	// module version
	const VER = 6;

    /**
     * 	Singleton instance of object
     * 	@var guiLogFile
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiLogFile {

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

		$xml->addVar('Opt', _('<strong>sync&bull;gw</strong> log file plugin'));
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

		// only allowed for administrators
		if (!$gui->isAdmin())
			return guiHandler::CONT;

		switch ($action) {
		case 'Init':
			// data base initialized?
			if (!$cnf->getVar(Config::DATABASE))
				return guiHandler::CONT;

			// are we responsible?
			$mod = strtolower($cnf->getVar(Config::LOG_FILE));
			if (!$mod || $mod == 'off' || $mod == 'syslog')
				return guiHandler::CONT;

			$gui->putCmd('<input id="LogFile" '.($gui->getVar('LastCommand') == 'ShowLog' ? 'checked ' : '').'type="radio" name="Command" '.
						 'value="ShowLog" onclick="document.syncgw.submit();"/>&nbsp;'.
						 '<label for="LogFile">'._('Show <strong>sync&bull;gw</strong> log file').'</label>');
			break;

		case 'ShowLog':
		    // today
		    $td = date('Ymd');
			$log = $cnf->getVar(Config::LOG_FILE);

		    // get active log file
			if (!($num = $gui->getVar('LogDay')))
				$num = $td;

			// check for current log file
			if (!file_exists($n = $log.'.'.$td)) {
                if ($fp = @fopen($n, 'ab'))
        			fclose($fp);
			}

			// set default buttons
			$b = $gui->mkButton(guiHandler::STOP);
			$gui->updVar('Button', $b.($num != $td ? '' : $gui->mkButton(_('Clear'), _('Delete log file content'), 'ClearLog')));

			// get number of log files
			$f = '<select name="LogDay" onchange="'.
				 'document.getElementById(\'Action\').value=\'ShowLog\';sgwAjaxStop(1);document.syncgw.submit();">';
		    $d   = dirname($log);
		    $fn  = substr($log, strlen($d) + 1);
		    $l   = strlen($fn);
            // log rotate?
    		if ($d = @opendir($d)) {
    		    $a = [];
	       		while (($file = @readdir($d)) !== FALSE) {
	       		    if (substr($file, 0, $l) == $fn) {
	       		        $n = substr($file, strrpos($file, '.') + 1);
        				$s = $n == $num ? 'selected="selected"' : '';
	       		        $a[] = '<option '.$s.' value='.$n.'>'.sprintf(_('Logfile from %s'),
	       		              substr($n, 0, 4).'-'.substr($n, 4, 2).'-'.substr($n, 6)).'</option>';
	       		    }
	       		}
	       	    rsort($a);
	       	    $f .= implode('', $a);
    		}
    		closedir($d);
			$f .= '</optgroup></select>';

			if ($num == $td) {
				$gui->setVal($gui->getVar('Button').'<input type="button" id="StopLog" class="sgwBut" value="'._('Stop').
							'" onclick="sgwAjaxToggle(\'StopLog\',\'LogMsg\',\''._('Start').'\',\''.
							_('Start log catching').'\');" '.'title="'._('Suspend log catching').'" /> '.$f);
				// set stop message
				$gui->updVar('Message', '<div id="LogMsg" style="visibility: hidden; display: none; text-align: right;">'.
					  		 XML::cnvStr(_('Log catching has been suspended...')).'</div>');
			} else
				$gui->setVal($gui->getVar('Button').$f);

			// start log display
			$gui->updVar('Script', '<script type="text/javascript">'.
						// maximize message window
						'var e=document.getElementById(\'sgwCmd\');'.
						'e.style.visibility=\'hidden\';'.
						'e.style.display=\'none\';'.
						'document.getElementById(\'sgwMsg\').style.height=\'82%\';'.
						'sgwAjaxStop(1);'.
						'sgwAjaxStart(\''.$gui->getVar('HTML').'Ajax.php?n='.base64_encode($log.'.'.$num).'\',2);'.
						'</script>');
			return guiHandler::STOP;

		case 'ClearLog':
			$gui->updVar('Action', 'ShowLog');
			$log = $cnf->getVar(Config::LOG_FILE).'.'.date('Ymd');
			if (file_exists($log)) {
				// clear file content
				$fp = fopen($log, 'wb');
				fclose($fp);
			}
			return guiHandler::RESTART;

		default:
			break;
		}

		return guiHandler::CONT;
	}

}

?>
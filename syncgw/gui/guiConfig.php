<?php
declare(strict_types=1);

/*
 * 	Configuration
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 *
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\Log;
use syncgw\lib\DataStore;
use syncgw\lib\Encoding;
use syncgw\lib\HTTP;
use syncgw\lib\User; //2
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiConfig {

	// module version
	const VER 			 = 21;

    /**
     * 	Singleton instance of object
     * 	@var guiConfig
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiConfig {

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

		$xml->addVar('Opt', _('Configure <strong>sync&bull;gw</strong> server plugin'));
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
			$gui->putCmd('<input id="Config" '.($gui->getVar('LastCommand') == 'Config' ? 'checked ' : '').'type="radio" name="Command" '.
						 'value="Config" onclick="document.syncgw.submit();"/>&nbsp;'.
						 '<label for="Config">'._('Configure <strong>sync&bull;gw</strong> server').'</label>');
			 break;

		case 'Config':
		case 'ConfDrop':
		case 'ConfSave':

			// set language
			if(($c = $gui->getVar('ConfLanguage')) == NULL) {
				if ($a = $gui->getVar('ConfLang')) {
					$a = unserialize(base64_decode());
					$enc = Encoding::getInstance();
					$enc->setLang($a[$c]);
				}
			}

			// set button
			$gui->updVar('Button', $gui->getVar('Button').$gui->mkButton(guiHandler::STOP, '', 'ConfReturn').
						$gui->mkButton(_('Save'), _('Save configuration'), 'ConfSave'));

			// check data base handler (priority #1)
			if (!self::_dbhandler($action)) {
				$gui->updVar('Action', 'ConfSave');
				return guiHandler::STOP;
			}

			$ok = TRUE;
			foreach ([ '_datastore', '_admin', '_language', '_phperr', '_cron', '_logfile',
					'_debug', //2
					'_trace', '_session',
					// DAV configuration
					'_objsize',
					// ActiveSync configuration
					'_heartbeat',
					 ] as $func) {
				if (!self::$func($action))
					$ok = FALSE;
			}

			// show status message
			if ($action == 'ConfSave') {
				if ($ok) {
					// save base URI
					$http = HTTP::getInstance();
					$uri = $http->getHTTPVar('REQUEST_URI');
					if (($p = stripos($uri, '/sync.php')) !== FALSE) {
						$uri = substr($uri, 0, $p);
						$cnf->updVar(Config::BASEURI, $uri);
					}
					// save .INI file
					$cnf->saveINI();
					// be sure to disable tracing
				    $cnf->updVar(Config::TRACE_MOD, 'Off');
					$gui->putMsg(_('Configuration saved to \'config.ini.php\''));
				} else
					$gui->putMsg(_('Error in configuration - please check'), Util::CSS_ERR);
				$gui->updVar('Action', 'Config');
			}
			return guiHandler::STOP;

		case 'ConfReturn':
			if ($cnf->getVar(Config::DATABASE) && !$cnf->getVar(Config::ENABLED)) {
				$gui->putMsg(_('You must enable at least one data store'), Util::CSS_ERR);
				$gui->updVar('Action', 'Config');
			} else
				$gui->updVar('Action', '');
			return guiHandler::RESTART;

		default:
			break;
		}

		return guiHandler::CONT;
	}

	/**
	 * 	Configure data base handler
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _dbhandler(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		$tit  = _('Data base connection handler');
		$help = _('Select from list which data base handler <strong>sync&bull;gw</strong> should use. '.
				  'If you can\'t select a handler name, a connection is already established. '.
				  'To drop the connection, use the "Drop" button.');

		// load data base handler
		if (!($be = $gui->getVar('ConfBE')))
			$be = $cnf->getVar(Config::DATABASE);

		// back end available?
		if (!$be || $be == '--') {

			// clear enabled data stores
			$cnf->updVar(Config::ENABLED, 0);

			// load list of available data base handler
			$hd = [];
			$dir = Util::mkPath('interfaces');
			if (!($d = @opendir($dir))) {
				$gui->putMsg(sprintf(_('Can\'t open [%s]'), $dir), Util::CSS_ERR);
				return FALSE;
			}
			while (($file = @readdir($d)) !== FALSE) {
				if ($file == '.' || $file == '..' || !is_dir($dir.DIRECTORY_SEPARATOR.$file))
					continue;
				$hd[$file] = TRUE;
			}
			@closedir($d);

			// remove sustainable handler
			if (isset($hd['mail'])) {
				unset($hd['roundcube']);
				unset($hd['mysql']);
				unset($hd['file']);
			} elseif (isset($hd['roundcube'])) {
				unset($hd['mysql']);
				unset($hd['file']);
			}

			// any data base available?
			if (!count($hd)) {
				$gui->putMsg(_('No data base handler found - please install software package'), Util::CSS_ERR);
				return FALSE;
			}

			// create data base selection list
			$f = '<select name="ConfBE" onchange="document.getElementById(\'Action\').value=\'Config\';sgwAjaxStop(1);'.
				 'document.syncgw.submit();"><option>--</option>';
			foreach ($hd as $file => $unused)
				$f .= '<option>'.$file.'</option>';
			$unused; // disable Eclipse warning
			$f .= '</select>';
			$gui->putQBox($tit, $f, $help, FALSE);

			// ready to get handler specific parameters
			$gui->putHidden('ConfGetBEParm', '1');

			return FALSE;
		}

		// allocate handler
		$adm = 'syncgw\\interfaces\\'.$be.'\\Admin';
		$adm = $adm::getInstance();

		// drop data base connection
		if ($action == 'ConfDrop') {
			// disconnect
			if ($adm->DisConnect()) {
				$cnf->updVar(Config::DATABASE, '');
				$gui->updVar('ConfBE', '');
				// rebuild stored trace mode
				$cnf->updVar(Config::TRACE_MOD, $cnf->getVar(Config::TRACE_MOD, TRUE));
				$cnf->saveINI();
				$cnf->updVar(Config::TRACE_MOD, 'Off');
			}
			return self::_dbhandler('Config');
		}

		// show current handler
		$gui->putQBox($tit, '<input type="text" size="20" readonly name="ConfBE" value="'.$be.'" />', $help, FALSE);

		// save configuration?
		if ($action == 'ConfSave') {

			// connect
			if (!$adm->Connect()) {
				$gui->putHidden('ConfGetBEParm', '1');
				return self::_dbhandler('Config');
			}
			$gui->putHidden('ConfGetBEParm', '0');
			$gui->updVar('Action', 'Config');
			$cnf->updVar(Config::DATABASE, $be);
			$cnf->updVar(Config::TRACE_MOD, $cnf->getVar(Config::TRACE_MOD, TRUE));
			$cnf->saveINI();
			$cnf->updVar(Config::TRACE_MOD, 'Off');
		}

		// get configuration parameter
		if ($gui->getVar('ConfGetBEParm')) {
			$adm->getParms();
			return FALSE;
		}

		// set button
		$gui->updVar('Button', $gui->getVar('Button').
						$gui->mkButton(_('Drop'), _('Drop <strong>sync&bull;gw</strong> data base connection'),
						'var v=document.getElementById(\'Action\');'.
						'if (confirm(\''.sprintf(_('Do you really want to drop connection to data base handler >%s<?'), $be).'\') == true) {'.
						'v.value=\'ConfDrop\';'.
						'} else {'.
						'v.value=\'Config\'; } return true;'));

		return TRUE;
	}

	/**
	 * 	Configure enabled data stores
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _datastore(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		if (!($be = $cnf->getVar(Config::DATABASE)))
			return TRUE;

		// get supported data store handlers
		$adm = 'syncgw\\interfaces\\'.$be.'\\Admin';
		$adm = $adm::getInstance();
		$ava = $adm->SupportedHandlers();

		// verify if document handler is available
		foreach (Util::HID(Util::HID_CNAME, DataStore::DATASTORES, TRUE) as $k => $v) {
			if (!file_exists(Util::mkPath('document/'.substr($v, strrpos($v,'\\') + 1).'.php')))
				$ava &= ~$k;
		}

		$ena = $cnf->getVar(Config::ENABLED);
		if ($action == 'ConfSave') {
			$ena = 0;
			foreach (Util::HID(Util::HID_CNAME, DataStore::DATASTORES, TRUE) as $k => $v) {
				if ($c = $gui->getVar('ConfDSName'.$k))
					$ena |= $c;
			}
			if ($ava & DataStore::EXT)
				$ena |= DataStore::EXT;
			$cnf->updVar(Config::ENABLED, $ena);
		}

		// enabled data stores
		$f = '';
		$n = 1;
		foreach (Util::HID(Util::HID_ENAME, DataStore::DATASTORES, TRUE) as $k => $v) {
			if (!($k & $ava))
				$s = 'disabled="disabled"';
			elseif ($k & $ena)
				$s = 'checked="checked"';
			else
				$s = '';
			$f .= '<div style="width: 120px; float: left;">'.
					'<input name="ConfDSName'.$k.'" type="checkbox" '.$s.' value="'.$k.'" /> '.$v.'</div>';
			if (!($n++ % 5))
				$f .= '<br />';
		}

		$gui->putQBox(_('Enabled data store'), $f,
						_('Specify which data stores you want to be enabled and available for synchronization with devices. '.
						'If a handler is not selectable, you have either not purchased the handler modules or your data base '.
						'connection handler do not support this type of data store.'), FALSE);

		if (!$ena)
			$gui->putMsg(_('Please enable at least one data store'), Util::CSS_WARN);

		return TRUE;
	}

	/**
	 * 	Configure administrator
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _admin(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		if (($c = $gui->getVar('ConfPwd')) && $action == 'ConfSave')
			$cnf->updVar(Config::ADMPW, $gui->encrypt($c));

		$gui->putQBox(_('Administrator password'),
				'<input name="ConfPwd" type="password" size="20" maxlength="30" value="'.$c.'" />',
				_('Please enter new <strong>sync&bull;gw</strong> administrator password.'), FALSE);

		return TRUE;
	}

	/**
	 * 	Configure language settings
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _language(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		// load available languages definitions
		$lang = [];
		$dir = Util::mkPath('locales');
		if (is_dir($dir) && function_exists('gettext')) {
			if (!($d = @opendir($dir))) {
				$gui->putMsg(sprintf(_('Can\'t open [%s]'), $dir), Util::CSS_ERR);
				return FALSE;
			}
			$l = [ '.' ];
			while (($file = @readdir($d)) !== FALSE) {
				if ($file == '.' || $file == '..' || !is_dir($dir.DIRECTORY_SEPARATOR.$file))
					continue;
				$l[] = $file;
			}
			@closedir($d);
			foreach ($l as $d) {
				$nam = $dir.DIRECTORY_SEPARATOR.$d.'/language.cfg';
				if (!@file_exists($nam))
					continue;
				$lang[@file_get_contents($nam)] = $d;
			}
		}

		if (!count($lang))
			return TRUE;

		if(($c = $gui->getVar('ConfLanguage')) === NULL)
			list($c,) = explode(';', $cnf->getVar(Config::LANG));

		if ($action == 'ConfSave')
			$cnf->updVar(Config::LANG, $c.';'.$lang[$c]);

		$f = '<select name="ConfLanguage" onchange="'.
			 'document.getElementById(\'Action\').value=\'Config\';sgwAjaxStop(1);document.syncgw.submit();">';
		foreach ($lang as $k => $unused) {
			$s = $k == $c ? 'selected="selected"' : '';
			$f .= '<option '.$s.'>'.$k.'</option>';
		}
		$unused; // disable Eclipse warning
		$f .= '</optgroup></select>';
		$gui->putHidden('ConfLang', base64_encode(serialize($lang)));

		$gui->putQBox(_('Language'), $f,
						_('<strong>sync&bull;gw</strong> server supports <a class="sgwA" href="http://www.gnu.org/software/gettext" '.
						'target="_blank">gettext</a> based native '.
						'language support - all message can be translated to any required language.<br /><br />'.
						'If your required language is not in selection list, you may create your own translation file. For this purpose, '.
						'we recommend using one of translation tools available (e.g. <a class="sgwA" href="http://kbabel.kde.org/" '.
						'target="_blank">KBabel</a>, '.
						'<a class="sgwA" href="http://www.gtranslator.org/" target="_blank">GTranslator</a> or '.
						'<a class="sgwA" href="http://www.poedit.net/" target=_blank">POEdit</a>).<br /><br />'.
						'We would greatly appreciate if you share the translation file within our community. Please '.
						'drop me a note and I will make it available for all users on GitHub.'), FALSE);

		return TRUE;
	}

	/**
	 * 	Configure PHP error logging
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _phperr(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		$yn = [
				_('Yes')	=> 'Y',
				_('No')		=> 'N',
		];

		if(($c = $gui->getVar('ConfPHPError')) === NULL)
			$c = $cnf->getVar(Config::PHPERROR);

		if ($action == 'ConfSave')
			$cnf->updVar(Config::PHPERROR, $c);

		$f = '<select name="ConfPHPError">';
		foreach ($yn as $k => $v) {
			$s = $v == $c ? 'selected="selected"' : '';
			$f .= '<option '.$s.' value="'.$v.'">'.$k.'</option>';
		}
		$f .= '</select>';

		$gui->putQBox(_('Capture PHP error'), $f,
			 		   _('By default <strong>sync&bull;gw</strong> is able to catch all PHP warning and notices. Setting this option to '.
						'<strong>Yes</strong> enables <strong>sync&bull;gw</strong> additionally to capture all PHP fatal errors '.
						'in the log file specified above. Please note <strong>sync&bull;gw</strong> will override locally some PHP.ini '.
						'settings.'), FALSE);

		return TRUE;
	}

	/**
	 * 	Configure cron job
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _cron(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		$yn = [
				_('Yes')	=> 'Y',
				_('No')		=> 'N',
		];

		if(($c = $gui->getVar('ConfCron')) === NULL)
			$c = $cnf->getVar(Config::CRONJOB);

		if ($action == 'ConfSave')
			$cnf->updVar(Config::CRONJOB, $c);

		$f = '<select name="ConfCron">';
		foreach ($yn as $k => $v) {
			$s = $v == $c ? 'selected="selected"' : '';
			$f .= '<option '.$s.' value="'.$v.'">'.$k.'</option>';
		}
		$f .= '</select>';

		$gui->putQBox(_('Use CRON job'), $f,
			 		   _('By default <strong>sync&bull;gw</strong> is handling record expiration internally. This solution may have '.
			 		   	 'impact on synchronization performance. We recommend setup your own '.
			 		   	 '<a href="https://en.wikipedia.org/wiki/Cron" target="_blank">CRON</a> job. '.
			 		   	 'For this purpose please call <strong>sync.php?cleanup</strong> at least every hour. '.
			 		   	 'If you\'re using PLESK, you may call <strong>sync.php</strong> as script with parameter <strong>cleanup</strong>.'), FALSE);

		return TRUE;
	}

	/**
	 * 	Configure log file
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _logfile(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();
		$rc = TRUE;

		if(($c = $gui->getVar('ConfLogFile')) === NULL)
			$c = $cnf->getVar(Config::LOG_FILE);

		if ($action == 'ConfSave') {
			if ($c) {
				$c = str_replace([  '\\', '/' ], [ DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ], $c);
				if (!@is_dir(dirname($c))) {
					$gui->putMsg(sprintf(_('Error accessing log file directory [%s]'), dirname($c)), Util::CSS_ERR);
					$rc = FALSE;
				} else {
					if (stripos($c, 'syslog') === FALSE && strtolower($c) != 'off') {
						if (@file_exists($c))
							$x = file_get_contents($c);
						else
							$x = "\n";
						if ($x === FALSE || @file_put_contents($c.'.'.date('Ymd'), $x) === FALSE) {
							$gui->putMsg(sprintf(_('Error accessing log file directory [%s] for log file'), $c), Util::CSS_ERR);
							$rc = FALSE;
						} else
							$cnf->updVar(Config::LOG_FILE, $c);
					}
				}
			}
		}

		$gui->putQBox(_('Log file name'),
					  '<input name="ConfLogFile" type="text" size="47" maxlength="250" value="'.$c.'" />',
					  _('Specify where to store error, warning and informational messages.<br/><br/>'.
						'<strong>Off</strong><br/>Turn off any logging.<br/><br/>'.
						'<strong>SysLog</strong><br/>Msg messages to system log file.<br/><br/>'.
						'<strong>&lt;name&gt;</strong><br/>create log messages to file. You may specify either a '.
						'relative file name prefix (e.g. "../logs/syncgw-log") or an absolute path (e.g. "/var/logs/syncgw")'), FALSE);

		if(($lvl = $gui->getVar('ConfLogLvl')) === NULL)
			$lvl = $cnf->getVar(Config::LOG_LVL);

		if ($action == 'ConfSave') {
		    $l = 0;
		    $s = FALSE;
		    foreach (Log::MSG_TYP as $k => $v) {
		    	if ($gui->getVar('ConLLogLvl'.$k) !== NULL) {
					$l |= $k;
					$s = TRUE;
		    	}
		    }
		    if ($s)
			    $cnf->updVar(Config::LOG_LVL, $l);
		}

		$f = '';
		foreach (Log::MSG_TYP as $k => $v) {
			$s = (intval($lvl) & $k) ? 'checked="checked"' : '';
			$f .= '<div style="width: 120px; float: left;">'.
					'<input name="ConLLogLvl'.$k.'" type="checkbox" '.$s.' value="'.$k.'" /> '.$v.'</div>';
		}

		$gui->putQBox(_('Logging level'), $f,
					  _('<strong>sync&bull;gw</strong> server may write errors, warnings and other messages to log file. Depending '.
						'on your setting, your log '.
						'file will use more or less <a class="sgwA" href="http://www.logwatch.org/" target="_blank">disk space</a>.<br/><br/>'.
						'<strong>Error</strong><br/>'.
						'Show errors which <strong>sync&bull;gw</strong> either cannot handle or were unexpected (will always be logged).'.
						'<br/><br/>'.
						'<strong>Warn</strong><br/>Show additional warnings which <strong>sync&bull;gw</strong> can cover.<br/><br/>'.
						'<strong>Info</strong><br/>Show additional informational messages.<br/><br/>'.
						'<strong>Application</strong><br/>Additional application processing messages.<br /><br />'.
					    '<strong>Debug</strong><br />More detailed processing messages.'), FALSE);

		if(($exp = $gui->getVar('ConfLogExpiration')) === NULL)
			$exp = $cnf->getVar(Config::LOG_EXP);

		if ($action == 'ConfSave') {
			if (!is_numeric($exp)) {
				$gui->putMsg(sprintf(_('Invalid value \'%s\' for log file expiration'), $exp), Util::CSS_ERR);
				$rc = FALSE;
			} else
				$cnf->updVar(Config::LOG_EXP, $exp);
		}

		$gui->putQBox(_('Log file expiration'),
				 	  '<input name="ConfLogExpiration" type="text" size="5" maxlength="10" value="'.$exp.'" />',
					  _('Specify how many log files should be kept before the eldest file will be deleted.'), FALSE);

		return $rc;
	}

	/**
	 * 	Configure debug user
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _debug(string $action): bool { //2

		$gui = guiHandler::getInstance(); //2
		$cnf = Config::getInstance(); //2

		if(($uid = $gui->getVar('ConfDbgUsr')) === NULL) //2
			$uid = $cnf->getVar(Config::DBG_USR); //2

		$gui->putQBox(_('Debug user'), //2
					  '<input name="ConfDbgUsr" type="text" size="20" maxlength="30" value="'.$uid.'" />', //2
					  _('This user is used to access the internal and external data bases (e.g. during debugging traces). '. //2
					    'Debug user must be authorized to access internal and external data base. To disable debugging, please '. //2
						'leave this field empty. Please note, a couple of additional functions in "Explore data" panel will only be '. //2
						'available, if you have specified a debug user.'), FALSE); //2

		if(($upw = $gui->getVar('ConfDbgUpw')) === NULL) //2
			$upw = $cnf->getVar(Config::DBG_UPW); //2

		$gui->putQBox(_('Debug user password'), //2
					  '<input name="ConfDbgUpw" type="password" size="20" maxlength="30" value="'.$upw.'" />', //2
					  _('Password for debug user.'), FALSE); //2

		if ($action == 'ConfSave') { //2

			// user id set?
			if (!$uid) //2
				return TRUE; //2

			// password set?
			if (!strlen($upw)) { //2
				$gui->putMsg(_('Password for debug user not set'), Util::CSS_ERR);	//2
				return FALSE; //2
			} //2

			// authorize debug user
			$usr = User::getInstance(); //2
			if (!$usr->Login($uid, $upw)) { //2
				$gui->putMsg(_('Unable to authorize debug user'), Util::CSS_ERR); //2
				return FALSE; //2
			} //2

			// save data
			$cnf->updVar(Config::DBG_USR, $uid); //2
			$cnf->updVar(Config::DBG_UPW, $upw); //2

		} //2

		return TRUE; //2
	} //2

	/**
	 * 	Configure trace
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _trace(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();
		$rc  = TRUE;

		if (!$cnf->getVar(Config::DATABASE))
			return TRUE;

		if(($c = $gui->getVar('ConfTraceMode')) === NULL)
			$c = $cnf->getVar(Config::TRACE_MOD, TRUE);

		if ($action == 'ConfSave')
			$cnf->updVar(Config::TRACE_MOD, strtolower($c) == 'on' ? 'On' : 'Off');

		$gui->putQBox(_('Trace'),
						'<input name="ConfTraceMode" type="text" size="20" maxlength="255" value="'.$c.'" />',
						_('Trace data is used to enable debugging of any misbehavior of <strong>sync&bull;gw</strong> server. If you encounter '.
						'any problems, we need such a trace to analyze the situation. Available options:<br /><br />'.
						'<strong>On</strong><br />'.
						'Activate tracing for all users.<br /><br />'.
						'<strong>IP</strong><br />'.
						'Enable tracing for specific IP address.<br /><br />'.
						'<strong>User name</strong><br />'.
						'Enable tracing only for specific user name.<br /><br />'.
						'<strong>Off</strong><br />'.
						'Disable tracing for all users.'), FALSE);

		if(($c = $gui->getVar('ConfTraceDir')) === NULL)
			$c = $cnf->getVar(Config::TRACE_DIR);

		if ($action == 'ConfSave') {
			if ($c) {
				$c = realpath($c);
				if (!$c || !@is_dir($c) || !@is_writeable($c)) {
					$gui->putMsg(sprintf(_('Error accessing trace directory [%s]'), $c), Util::CSS_ERR);
					$rc = FALSE;
				}
				$c = str_replace([  '\\', '/' ], [ DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ], $c);
				if (substr($c, -1) != DIRECTORY_SEPARATOR)
					$c .= DIRECTORY_SEPARATOR;
				$cnf->updVar(Config::TRACE_DIR, $c);
			}
		}

		$gui->putQBox(_('Trace directory'),
					  '<input name="ConfTraceDir" type="text" size="47" maxlength="250" value="'.$c.'" />',
					  _('Specify where to store trace files. You may specify either a '.
						'relative directory name prefix (e.g. "../traces") or an absolute path (e.g. "/var/traces")<br />'), FALSE);

		if(($c = $gui->getVar('ConfTraceExpiration')) === NULL)
			$c = $cnf->getVar(Config::TRACE_EXP);

		if ($action == 'ConfSave') {
			if (!is_numeric($c)) {
				$gui->putMsg(sprintf(_('Invalid value \'%s\' for trace file expiration'), $c), Util::CSS_ERR);
				$rc = FALSE;
			} else
				$cnf->updVar(Config::TRACE_EXP, $c);
		}

		$gui->putQBox(_('Trace file expiration (in hours)'),
					  '<input name="ConfTraceExpiration" type="text" size="5" maxlength="10" value="'.$c.'" />',
					  _('After the given number of hours <strong>sync&bull;gw</strong> automatically removes expired trace files from '.
					    'trace directory. If you want to disable automatic file deletion, please enter a value of <strong>0</strong>.'), FALSE);

		return $rc;
	}

	/**
	 * 	Configure session
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _session(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();
		$rc = TRUE;

		if (($c = $gui->getVar('ConfSessionMax')) === NULL)
			$c = $cnf->getVar(Config::SESSION_TIMEOUT);

		if ($action == 'ConfSave') {
			if (!is_numeric($c) || $c < 1) {
				$gui->putMsg(sprintf(_('Invalid value \'%s\' for session timeout - minimum of 1 seconds required'), $c), Util::CSS_ERR);
				$rc = FALSE;
			} else
				$cnf->updVar(Config::SESSION_TIMEOUT, $c);
		}

		$gui->putQBox(_('Session timeout (in seconds)'),
					  '<input name="ConfSessionMax" type="text" size="5" maxlength="10" value="'.$c.'" />',
					  _('Session between devices and <strong>sync&bull;gw</strong> server requires exchange of multiple packages '.
						'send over connection. '.
						'Each package depends on the previous package. During this operation data has to be temporary saved ensuring '.
						'synchronization integrity across the session. Depending on the performance of the connection and processing power '.
						'of server or device, delay between packages exchanged may vary. This parameter specifies how many seconds '.
						'between different session <strong>sync&bull;gw</strong> server should keep session data active.'), FALSE);

		if(($c = $gui->getVar('ConfSessionExp')) === NULL)
			$c = $cnf->getVar(Config::SESSION_EXP);

		if ($action == 'ConfSave') {
			if (!is_numeric($c)) {
				$gui->putMsg(sprintf(_('Invalid value \'%s\' for record expiration'), $c), Util::CSS_ERR);
				$rc = FALSE;
			} else
				$cnf->updVar(Config::SESSION_EXP, $c);
		}

		$gui->putQBox(_('Session record expiration (in hours)'),
					  '<input name="ConfExpiration" type="text" size="5" maxlength="10" value="'.$c.'" />',
					  _('<strong>sync&bull;gw</strong> stores record for managing synchronization sessions records in internal data stores. '.
					    'After the given number of hours <strong>sync&bull;gw</strong> automatically removes records expired from '.
					    'internal data stores. If you want to disable automatic record deletion, please enter a value of <strong>0</strong>.'), FALSE);

					  return $rc;
	}

	/**
	 * 	Configure max. object size
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _objsize(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();
		$rc = TRUE;

		if(($c = $gui->getVar('ConfMaxObj')) === NULL)
			$c = self::_cnvBytes2String($cnf->getVar(Config::MAXOBJSIZE));

		if ($action == 'ConfSave') {
			if ($c = self::_cnvString2Bytes($c)) {
			    $rc = TRUE;
				if ($c < 1024) {
					$gui->putMsg(sprintf(_('Maximum object size - %d bytes is too small'), $c), Util::CSS_ERR);
					$rc = FALSE;
				}
				if ($rc)
					$cnf->updVar(Config::MAXOBJSIZE, $c);
			}
		}

		if (is_numeric($c))
			$c = self::_cnvBytes2String($c);

		$gui->putQBox(_('Maximum object size in bytes for DAV synchronization'),
						'<input name="ConfMaxObj" type="text" size="20" maxlength="20" value="'.$c.'" />',
						_('This is the maximum size object <strong>sync&bull;gw</strong> server accepts (in bytes, "KB", "MB" or "GB") '.
						'for DAV synchronization.<br /><br />'.
						'Please note the size is limited by two factors:<br />'.
						'<ul><li>The PHP <a class="sgwA" href="http://php.net/manual/en/ini.core.php" '.
						'target="_blank">maximum excution time</a>.</li>'.
						'<li>The PHP <a class="sgwA" href="http://php.net/manual/en/ini.core.php" target="_blank">memory_limit</a> size.'.
						'</li></ul>'.
						'We highly recommend if you want to use a bigger value, you should make some testing before taking over '.
						'value over to production system.<br /><br />'.
						'Default: 1000 KB'), FALSE);
		return $rc;
	}

	/**
	 * 	Configure ActiveSync heartbeat
	 *
	 * 	@param	- Action to perform
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	private function _heartbeat(string $action): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();
		$rc = TRUE;

		if (($hb = $gui->getVar('ConfHeartBeat')) === NULL)
			$hb = $cnf->getVar(Config::HEARTBEAT);
		if (($sw = $gui->getVar('ConfPingSleep')) === NULL)
			$sw = $cnf->getVar(Config::PING_SLEEP);

		if ($action == 'ConfSave') {
			if (!is_numeric($hb) || $hb < 10) {
				$gui->putMsg(sprintf(_('Invalid value \'%s\' for heartbeat - minimum of 10 seconds required'), $hb), Util::CSS_ERR);
				$rc = FALSE;
			} else
				$cnf->updVar(Config::HEARTBEAT, $hb);
			if (!is_numeric($sw) || $sw > $hb) {
				$gui->putMsg(sprintf(_('Invalid value \'%s\' for sleep time - it cannot be larger than heartbeat windows'), $sw), Util::CSS_ERR);
				$rc = FALSE;
			} else
				$cnf->updVar(Config::PING_SLEEP, $sw);
		}

		$gui->putQBox(_('ActiveSync Heartbeat window (in seconds)'),
					  '<input name="ConfHeartBeat" type="text" size="5" maxlength="10" value="'.$hb.'" />',
					  _('Using ActiveSync protocol, devices send a request to <strong>sync&bull;gw</strong> server '.
					    'asking server to check for changes on server. If a change is recognized in this time window, device '.
					    'is notified immediately. If no changes could be notified, <strong>sync&bull;gw</strong> server will send '.
					    'a notification after the heartbeat has expired. '.
                        'You can override the heartbeat client suggests to lower traffic between server and device. '.
					    'This parameter specifies how many seconds <strong>sync&bull;gw</strong> server will check for changes before '.
					    'client is notified nothing has changed.'), FALSE);

		$gui->putQBox(_('ActiveSync Sleep time (in seconds)'),
					  '<input name="ConfPingWin" type="text" size="5" maxlength="10" value="'.$sw.'" />',
					  _('Within the heartbeat window, <strong>sync&bull;gw</strong> will not constantly check for changes. '.
					    'This parameter specifies how many seconds <strong>sync&bull;gw</strong> will sleep '.
					    'before checking for changes.'), FALSE);

		return $rc;
	}

   /**
	 * 	Convert a number to human readable format
	 *
	 * 	@param	- Value to convert
	 * 	@return	- Display string
	 */
	private function _cnvBytes2String(int $val): string {
	    static $_fmt = [ '', 'KB', 'MB', 'GB' ];

		$o = 0;
		while ($val >= 1023) {
			$o++;
			$val = $val / 1024;
		}
		return number_format($val, 0, ',', '.').' '.$_fmt[$o];
	}

	/**
	 * 	Convert a human readable string to a number
	 *
	 * 	@param	- Value to convert
	 * 	@return	- Display string
	 */
	private function _cnvString2Bytes(string $val): int {

		$val = str_replace('.', '', $val);
		if (($p = stripos($val, 'K')))
			return intval(trim(substr($val, 0, $p)) * 1024);
		if (($p = stripos($val, 'M')))
			return intval(trim(substr($val, 0, $p)) * 1048576);
		if (($p = stripos($val, 'G')))
			return intval(trim(substr($val, 0, $p)) * 1073741824);

			return $val;
	}

}

?>
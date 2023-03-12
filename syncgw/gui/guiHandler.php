<?php
declare(strict_types=1);

/*
 * 	User interface handler class
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

/**
 * 	Session variable used:
 *
 *	$_SESSION[parent::getVar('SessionID')][self::TYP]	= self::TYP_USR=User / self::TYPADM=Admin
 *	$_SESSION[parent::getVar('SessionID')][self::UID]	= User ID
 *	$_SESSION[parent::getVar('SessionID')][self::PWD]	= User password
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\HTTP;
use syncgw\lib\User;
use syncgw\lib\XML;
use syncgw\lib\Log;
use syncgw\lib\Util;

class guiHandler extends XML {

	// module version number
	const VER 	  = 16;

	// handler status
	const CONT	  = '0';
	const STOP	  = '1';
	const RESTART = '2';

	const UID	  = 'UserID';				// userid field in session
	const PWD	  = 'UserPW';				// password field in session
	const TYP	  = 'Type';					// type field in session
	const AJAX	  = 'Ajax';					// ajax file in session
	const BACKUP  = 'Backup';				// ajax backup file in session

	const TYP_USR = '1';					// user
	const TYP_ADM = '2';					// administrator

	// color mapping table
    const COLOR   = [
			Log::ERR 	=> Util::CSS_ERR,
			Log::WARN	=> Util::CSS_WARN,
			Log::INFO	=> Util::CSS_INFO,
			Log::APP	=> Util::CSS_APP,
    		Log::DEBUG	=> Util::CSS_DBG,
	];

    // modules
    const MODS    = [
        'guiHelp',
        'guiUpgrade',
        'guiCheck',
        'guiConfig',
        'guiLogFile',
        'guiExplorer',
        'guiDelete',
        'guiCleanUp',
    	'guiReload',
        'guiUsrStats',		//2
        'guiSync',			//2
        'guiRename',		//2
        'guiShow',
        'guiTrace',
    	'guiEdit',			//2
        'guiDownload',
        'guiUpload',		//2
        'guiSetUsr',
        'guiStats',			//3
        'guiTraceExport',	//3
    	'guiSwitch',		//3
        'guiTrunc',			//3
    	'guiSoftware',		//3
        'guiForceTrace',	//3
        'guiFeatures',		//3
    ];

    /**
	 * 	Ajax file pointer
	 * 	@var resource
	 */
	private $_fp   = NULL;

	/**
	 * 	Max. record length
	 * 	@var integer
	 */
	private $_max  = 1048576*3;				// get max record length (3 MB)

	/**
	 * 	Output window scroll position
	 * 	@var array
	 */
	private $_win  = [];

	/**
	 * 	Plugin array
	 * 	@var array
	 */
	private $_plug = [];

	/**
	 * 	Q-Box counter
	 * 	@var int
	 */
	private $_cnt  = 0;

	/**
	 * 	Command window counter
	 * 	@var int
	 */
	private $_cmd;

    /**
     * 	Singleton instance of object
     * 	@var guiHandler
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiHandler {

		if (!self::$_obj) {
            self::$_obj = new self();

			$log = Log::getInstance();
			$log->Plugin('readLog', self::$_obj);

			$cnf  = Config::getInstance();
			$http = HTTP::getInstance();
			$p    = $cnf->getVar(Config::BASEURI);

			// disable tracing
			$cnf->updVar(Config::TRACE_MOD, 'Off');

			// setup variables
			self::$_obj->loadXML(
					'<syncgw>'.
					// URL to HTML directory
					'<HTML>'.($p ? $p : '.').'/syncgw/gui/html/</HTML>'.
					// the script we're serving
					'<ScriptURL>'.base64_encode($http->getHTTPVar('PHP_SELF').'?'.$http->getHTTPVar('QUERY_STRING')).'</ScriptURL>'.
					// missing JavaScript message (translation)
					'<NoJavaScript>'._('You need to turn on Javascript to access these pages!').'</NoJavaScript>'.
					// browser window height (0=full screen)
					'<WinHeight>0</WinHeight>'.
					// browser window width  (0=full screen)
					'<WinWidth>0</WinWidth>'.
					// hidden variables
					'<Hidden/>'.
					// additional script file data
					'<Script/>'.
					// buttons
					'<Button/>'.
					// message field
					'<Message/>'.
					// action to  perform
					'<Action/>'.
					// last command performed
					'<LastCommand/>'.
					// memory statistics
					'<Usage/>'.
					// login variables
					'<Logout>'.XML::cnvStr(self::$_obj->mkButton(_('Logout'),
							_('Logout from <strong>sync&bull;gw</strong>'), 'LogOff')).'</Logout>'.
					'<UserText/><UserDisabled/><UserID/><PasswordText/><UserPW/><AdminText/><AdminFlag/>'.
			        '<LoginButton/><ExtButton/><LoginMsg/>'.
	    		    // syncgw version
					'<Version>'.$cnf->getVar(Config::FULLVERSION).'</Version>'.
					// HTML skeleton
					'<Skeleton>'.Util::mkPath('gui/html/interface.html').'</Skeleton>'.
					// set size of Q-box "icon"
					'<QBoxStyle>width:18px; padding:0px; font-size:9px;float:inline-start;margin-right:5px;</QBoxStyle>'.
					'</syncgw>');

			// start session
			if (($stat = session_status()) == PHP_SESSION_NONE)
	       		ini_set('session.cookie_lifetime', '0');
			if ($stat != PHP_SESSION_ACTIVE) {
				if (!isset($_POST['SessionID'])) {
					$id = session_create_id();
					self::$_obj->putHidden('SessionID', $id);
				} else
					self::$_obj->putHidden('SessionID', $id = $_POST['SessionID']);
				session_id($id);
				session_start();
			}

			// create ajax file
			if (!isset($_SESSION[self::$_obj->getVar('SessionID')][self::AJAX])) {
				$_SESSION[self::$_obj->getVar('SessionID')][self::AJAX] = Util::getTmpFile('ajax');
				$_SESSION[self::$_obj->getVar('SessionID')][self::BACKUP] = Util::getTmpFile('back');
			} elseif (file_exists($_SESSION[self::$_obj->getVar('SessionID')][self::AJAX]))
				file_put_contents($_SESSION[self::$_obj->getVar('SessionID')][self::BACKUP],
						file_get_contents($_SESSION[self::$_obj->getVar('SessionID')][self::AJAX]));

			// load extensions
			foreach (self::MODS as $mod) {
			    if (!file_exists($file = Util::mkPath('gui/').$mod.'.php'))
			        continue;
				$class = 'syncgw\\gui\\'.$mod;
				self::$_obj->_plug[$file] = $class::getInstance();
			}

			// swap window size
			if (isset($_GET['heigth']))
			    self::$_obj->updVar('WinHeight', $_GET['heigth']);
			if (isset($_GET['width']))
			    self::$_obj->updVar('WinWidth', $_GET['width']);

			// swap POST data
			foreach ($_POST as $k => $v)
				self::$_obj->updVar($k, is_array($v) ? base64_encode(serialize($v)) : $v);

			// get scroll position
			if (!(self::$_obj->_win['Cmd'] = self::$_obj->getVar('sgwCmdPos')))
				self::$_obj->_win['Cmd'] = -1;
			if (!(self::$_obj->_win['Msg'] = self::$_obj->getVar('sgwMsgPos')))
				self::$_obj->_win['Msg'] = -1;
		}

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

    	$xml->addVar('Name', _('<strong>sync&bull;gw</strong> user interface'));
		$xml->addVar('Ver', strval(self::VER));

		foreach ($this->_plug as $api)
			$api->getInfo($xml, $status);
	}

	/**
	 * 	Process client request
	 */
	public function Process(): void {

		// check user login
		if (!self::Login()) {
			self::_flush();
			return;
		}

		$run = TRUE;
		do {

			// clear buttons
			parent::updVar('Button', '');
			parent::updVar('Message', '');

			if (!($cmd = parent::getVar('Command')))
				$cmd = parent::getVar('Action');
			else
				parent::updVar('LastCommand', $cmd);

			// any command to execute?
			$stop = 0;
			if ($cmd) {
				// check for commands
				foreach ($this->_plug as $obj) {
					switch ($obj->Action($cmd)) {
					case self::CONT:
						$stop = 1;
					 	break;

					case self::STOP:
						$stop = 2;
						break;

					case self::RESTART:
						$stop = 3;
						break;

					default:
						break;
					}
					if ($stop > 1)
						break;
				}
				if ($stop == 2)
					$run = FALSE;
			}
			if ($stop == 3)
				continue;

			if (substr($cmd, 0, 3) == 'Exp')
				$run = FALSE;

			if ($run) {
				$this->_cmd = 0;
				foreach ($this->_plug as $obj) {
					switch ($obj->Action('Init')) {
					case self::CONT:
					 	break;

					case self::STOP:
						$run = FALSE;
						break;

					default:
						break;
					}
					if (!$run)
						break;
				}
				if ($this->_cmd)
					parent::updVar('Button', parent::getVar('Button').self::mkButton(_('Run'), _('Execute selected command from list above')));
				$run = FALSE;
			}

		} while ($run);;

		// set ajax data
		parent::updVar('Ajax', '<input type="hidden" id="sgwCmdPos" name="sgwCmdPos" value="'.$this->_win['Cmd'].'" />'.
					   '<input type="hidden" id="sgwMsgPos" name="sgwMsgPos" value="'.$this->_win['Msg'].'" />'.
					   '<script type="text/javascript">sgwAjaxStart(\''.parent::getVar('HTML').'Ajax.php?n='.
					   base64_encode($_SESSION[parent::getVar('SessionID')][self::AJAX]).'\',1);</script>');

		self::_flush();
	}

	/**
	 * 	Check server configuration status
	 *
	 * 	@return	- TRUE=Ok; FALSE=Unavailable
	 */
	public function isConfigured(): bool {

		if (!@file_exists(Util::mkPath('config.ini.php')))
			return FALSE;

		// is syncgw initalized?
		$cnf = Config::getInstance();
		if (!$cnf->getVar(Config::DATABASE))
			return FALSE;

		return TRUE;
	}

	/**
	 * 	Read log messages and display
	 *
	 * 	@param	- Log color
	 * 	@param	- Message text
	 */
	public function readLog(int $typ, string $data): void {

		$pre = $typ & Log::ERR ? '+++' : '---';
		self::_writeMsg('Msg', '<code><div style="width:423px; float:left;">'.$pre.' '.date('Y M d H:i:s').'</div>'.
							   '<div>'.$data.'</div></code>', self::COLOR[$typ & ~Log::ONETIME]);
	}

	/**
	 * 	Set scroll position in window
	 *
	 * 	@param	- Window ID
	 * 	@param 	- Pixel scroll position in winow
	 */
	public function setScrollPos(string $w, int $pos): void {

		$this->_win[$w] = $pos;
	}

	/**
	 * 	Save hidden variable
	 *
	 * 	@param	- Variable name
	 * 	@param	- Value
	 */
	public function putHidden(string $var, string $val): void {

		$v = '';
		if (($org = parent::getVar('Hidden')) !== NULL)
			$v = preg_replace('/(.*name="'.$var.'" value=")(.*)(".*)/', '${1}'.$val.'${3}', $org);
	    // anything changed?
		if (!$org || strpos($org, 'name="'.$var.'"') === FALSE)
			$v = $org.'<input type="hidden" id="'.$var.'" name="'.$var.'" value="'.$val.'" />';
		parent::updVar('Hidden', $v);
		parent::getVar('syncgw');
		parent::updVar($var, $val, FALSE);
	}

	/**
	 * 	Add tabbed message
	 *
	 * 	@param	- Message left
	 * 	@param	- Message color left; defaults to CSS_NONE
	 * 	@param	- Message right; defaults to none
	 * 	@param	- Message color right; defaults to CSS_NONE
	 */
	public function tabMsg(string $lmsg, string $lcss = Util::CSS_NONE, string $rmsg = '', string $rcss = Util::CSS_NONE): void {

		if (!$lmsg)
			$lmsg = '&nbsp;';
		self::_writeMsg('Msg', '<div style="width:70%;float:left;white-space:pre-wrap;'.$lcss.'">'.
				$lmsg.'</div><div style="width: 27%; float: left; '.$rcss.'">'.$rmsg.'</div>');
	}

	/**
	 * 	Show message in message window
	 *
	 *	@param	- Message text
	 * 	@param	- Message color; defaults to Util::CSS_NONE
	 */
	public function putMsg(string $msg, string $css = Util::CSS_NONE): void {

		self::_writeMsg('Msg', $msg, $css);
	}

	/**
	 * 	Create button
	 *
	 * 	@param	- Button name
	 * 	@param	- Button help text
	 * 	@param	- "Action" value or JavaScript code
	 * 	@param	- TRUE=Delete message <DIV> (default); FALSE=Do not delete
	 *  @return - HTML string
	 */
	public function mkButton(string $name, string $help = '', string $action = '', bool $del = TRUE): string {

		if ($name == self::STOP) {
			$name = _('Return');
			if ($action == 'Explorer')
				$help = _('Return to explorer');
			else
				$help = _('Return to command selection menu');
		}
		if (!stripos($action, ';'))
			$js = 'document.getElementById(\'Action\').value=\''.$action.'\'';
		else
			$js = $action;
		return '<input type="submit" class="sgwBut" value="'.$name.'" '.
				'onclick="'.$js.';sgwAjaxStop('.($del ? '1' : '0').');" title="'.$help.'" />';
	}

	/**
	 * 	Clear message file
	 */
	public function clearAjax(): void {

		if ($this->_fp)
			ftruncate($this->_fp, 0);
	}

	/**
	 * 	Show message in command window
	 *
	 *  @param	- Message text
	 */
	public function putCmd(string $msg): void {

		$this->_cmd++;
		self::_writeMsg('Cmd', $msg);
	}

	/**
	 * 	Create Q-box
	 *
	 * 	@param	- Title
	 * 	@param	- HTML input field definition
	 * 	@param	- Help text
	 * 	@param	- TRUE=Show open Q-box; FALSE=Close Q-box
	 * 	@param	- Window ID (defaults to "Cmd" window)
	 */
	public function putQBox(string $title, string $input, string $cont, bool $open, string $win = 'Cmd'): void {

		if ($open) {
			$n = '-';
			$s = 'visibility: visible; ';
		} else {
			$n = '+';
			$s = 'visibility: hidden; display: none; ';
		}
		if (!$input) {
			$input = '<br style="clear: both;"/>';
			$dw = '';
		} else
			$dw = 'width: 50%;';

		$msg = '<div style="'.$dw.'float:left;">'.
				'<input id="QBox'.$this->_cnt.'B" type="button" value="'.$n.'" style="'.parent::getVar('QBoxStyle').
				'" onclick="QBox(\'QBox'.$this->_cnt.'\');" /> '.$title.'</div>'.
				'<div style="float:left;">'.$input.'</div><div id="QBox'.$this->_cnt.'" style="'.$s.Util::CSS_QBG.
				'padding: 3px 5px 5px 5px; overflow: auto; margin: 0 0 10px 0; '.
				'border: 1px solid; clear:left;" />'.$cont.'</div>'.
				'<div style="clear:left;"></div>';
		$this->_cnt++;

		self::_writeMsg($win, $msg, '', FALSE);
	}

	/**
	 * 	Check wheter we are logged in as administrator
	 *
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function isAdmin(): bool {

		return (bool)($_SESSION[parent::getVar('SessionID')][self::TYP] & intval(self::TYP_ADM));
	}

	/**
	 * 	Login user
	 *
	 *	@param	- force user ID to be loaded (none=default)
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	function Login(string $uid = ''): bool {

		// force user id? - this is not a real login!
		if ($uid) {
			// normalize user id
			if (strpos($uid, '@') !== FALSE)
				list($uid,) = explode('@', $uid);
			// get selected user
			$db = DB::getInstance();
			if (!($doc = $db->Query(DataStore::USER, DataStore::RGID, $uid)))
				return FALSE;
			// load user object
			$usr = User::getInstance();
			$usr->loadXML($doc->saveXML());
			return TRUE;
		}

		// get action to perform
		$action = parent::getVar('Action');

		// check for logoff
		if ($action == 'LogOff') {
			unset($_SESSION[parent::getVar('SessionID')][self::UID]);
		    unset($_SESSION[parent::getVar('SessionID')][self::PWD]);
			if ($this->_fp) {
				fclose($this->_fp);
				$this->_fp = NULL;
			}
			unlink($_SESSION[parent::getVar('SessionID')][self::AJAX]);
		    unset($_SESSION[parent::getVar('SessionID')][self::AJAX]);
		}

		$cnf = Config::getInstance();
		// get administrator password
		$apw = $cnf->getVar(Config::ADMPW);

		// user already logged in?
		if (isset($_SESSION[parent::getVar('SessionID')][self::PWD]))
			return TRUE;

		// check login data?
		if ($action == 'Login') {

			// first time login?
			if (!$cnf->getVar(Config::ADMPW)) {

				// save admin password
				if (($pw = parent::getVar('UserPW')) == parent::getVar('UserID')) {
					$cnf->updVar(Config::ADMPW, $pw = self::encrypt($pw));
					// save to .INI file
					$cnf->saveINI();
					$_SESSION[parent::getVar('SessionID')][self::TYP] = self::TYP_ADM;
					$_SESSION[parent::getVar('SessionID')][self::UID] = '';
					$_SESSION[parent::getVar('SessionID')][self::PWD] = $pw;
					return TRUE;
				}

				parent::updVar('LoginMsg', _('Password does not match - please retry'));
			}
			// admin login?
			elseif (parent::getVar('AdminFlag')) {
			    if (!($upw = parent::getVar('UserPW')))
				    parent::updVar('LoginMsg', _('Please enter administrator password'));
			    elseif (self::encrypt($upw) == $apw) {
					$_SESSION[parent::getVar('SessionID')][self::TYP] = self::TYP_ADM;
					$_SESSION[parent::getVar('SessionID')][self::UID] = '';
					$_SESSION[parent::getVar('SessionID')][self::PWD] = $apw;
					return TRUE;
				} else
    				parent::updVar('LoginMsg', _('Invalid administrator password'));
			}
			// check user login parameter
			else {
				if ($uid = parent::getVar('UserID')) {

					// normalize user id
					if (strpos($uid, '@') !== FALSE)
						list($uid,) = explode('@', $uid);

					if ($pw = parent::getVar('UserPW')) {
						// perform first time login (we have a clear passwort available)
						$usr = User::getInstance();
						if (!$usr->Login($uid, $pw))
							parent::updVar('LoginMsg', _('Invalid password'));
						else {
							$_SESSION[parent::getVar('SessionID')][self::TYP] = self::TYP_USR;
							$_SESSION[parent::getVar('SessionID')][self::UID] = base64_encode($uid);
							$_SESSION[parent::getVar('SessionID')][self::PWD] = self::encrypt($pw);
							return TRUE;
						}
					} else
						parent::updVar('LoginMsg', _('Please enter password'));
				} else
					parent::updVar('LoginMsg', _('Please enter user name'));
			}
		}

		// set login skeleton
		if (isset($_GET['adm'])) {
    		parent::updVar('Skeleton', Util::mkPath('gui/html/admin.html'));
    		if (strstr($_SERVER['REQUEST_URI'], 'adm=plesk') !== FALSE)
    		    parent::updVar('ExtButton', self::mkButton(_('External'),
    		    			_('Login to <strong>sync&bull;gw</strong> in a new browser window'),
        		            'var w = window.open(\''.(isset($_SERVER['HTTPS']) ? 'https://' : 'http://').
		                    $_SERVER['REMOTE_ADDR'].$_SERVER['SCRIPT_NAME'].
		                   '?sess='.uniqid('sgw').(strpos($_SERVER['REQUEST_URI'], 'adm') ? '&adm' : '').'\');w.focus();'));
		} elseif (!$apw)
    		parent::updVar('Skeleton', Util::mkPath('gui/html/init.html'));
        else
    		parent::updVar('Skeleton', Util::mkPath('gui/html/login.html'));
        parent::updVar('LoginButton', self::mkButton(_('Login'), _('Login to <strong>sync&bull;gw</strong>'), 'Login'));

		// is administrator password defined?
		if (!$apw) {
		    parent::updVar('UserText', _('Administrator password'));
			parent::updVar('PasswordText', _('Reenter password'));
		} else {
		    // admin login status?
    		$adm = parent::getVar('AdminFlag');
    		parent::updVar('UserText', _('User name'));
        	parent::updVar('UserDisabled', $adm ? 'disabled="disabled"' : '');
    		parent::updVar('PasswordText', _('Password'));
    		parent::updVar('AdminText', _('Login as administrator'));
            parent::updVar('AdminFlag', $adm ? '1' : '0');
		}

		return FALSE;
	}

	/**
	 * 	Encrypt password
	 *
	 * 	@param	- Password
	 * 	@return	- Encrpted password
	 */
	public function encrypt(string $pw): string {

		for ($i=0; $i < 1000; $i++)
			$pw = md5($pw);
		return base64_encode($pw);
	}

	/**
	 * 	Write message to window
	 *
	 * 	@param 	- Window ID
	 *  @param	- Message text
	 *  @param	- Message color; defaults to CSS_NONE
	 *  @param	- TRUE= Add line break at end of message (default) - used by putQBox()
	 */
	private function _writeMsg(string $w, string $msg, string $css = Util::CSS_NONE, bool $lbr = TRUE): void {

		// everything flushed?
		if ($this->_fp == -1 || !isset($_SESSION[parent::getVar('SessionID')][self::AJAX]))
			return;

		if (!$this->_fp) {
			if (!($this->_fp = @fopen($_SESSION[parent::getVar('SessionID')][self::AJAX], 'ab'))) {
			    $this->_fp = -1;
			    return;
		    } else
       			ftruncate($this->_fp, 0);
		}

		if (strlen($msg) > $this->_max)
			$msg = substr($msg, 0, $this->_max).' CUT@'.$this->_max;

		// write data
		fwrite($this->_fp, ($w == 'Cmd' ? '6' : '7').
			   '<font class="sgwDiv"'.($css != Util::CSS_NONE ? ' style="'.$css.'float:left;width:max-content;"' : '').'>'.$msg.
			   '</font>'.($lbr ? '<br style="clear: both;"/>' : '')."\n");
	}

	/**
	 * 	Flush output to browser window
	 */
	private function _flush(): void {

			// replace data - do it this way to prevent memory exhausting
		$rk = [];
		$rv = [];

		$http = HTTP::getInstance();

		parent::getChild('syncgw');
		while (($v = parent::getItem()) !== NULL) {
			if (is_array($v))
				continue;
			$n = parent::getName();
			$rk[] = '{'.$n.'}';
			$rv[] = $n == 'ScriptURL' ? base64_decode($v) : $v;
		}

		// close message file
		if ($this->_fp) {
			fclose($this->_fp);
			$this->_fp = -1;
		}

		// send data
		$http->addHeader('Content-Type', 'text/html; charset=UTF-8');
		$http->addBody(str_replace($rk, $rv, file_get_contents(parent::getVar('Skeleton'))));
		$http->send(200);
	}

}

?>
<?php
declare(strict_types=1);

/*
 * 	Log handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class Log {

	// module version number
	const VER 	  	= 12;

	// message log level definition
	const ERR	  	= 0x01;
	const WARN	  	= 0x02;
	const INFO	  	= 0x04;
	const APP	  	= 0x08;
	const DEBUG	  	= 0x10;

	const ONETIME 	= 0x80;

	// log status
	const UNDEF   	=  0;
	const OFF	  	= -1;
	const SYSL	  	= -2;
	const FILE	  	= -3;

	// message tags
	const MSG_TYP 	=	[
			self::ERR 	        => 'Error',
            self::WARN 	        => 'Warn ',
            self::INFO 	        => 'Info ',
            self::APP	        => 'Apps ',
			self::DEBUG	        => 'Debug',
	];

	/**
	 * 	Log message buffer
	 * 	@var array
	 */
	private $_msg;

	/**
	 * 	Log file pointer or logging status
	 * 	@var mixed
	 */
	private $_log		= self::UNDEF;

	/**
	 * 	Log file name
	 * 	@var string
	 */
	private $_file;

	/**
	 * 	Log level
	 * 	@var int
	 */
	private $_loglvl	= self::ERR;

	/**
	 * 	Log plugin buffer
	 * 	@var array
	 */
	private $_plugin	= [];

	/**
	 * 	PHP Error filter
	 * 	@var array
	 */
	private $_filter	= [];

	/**
	 * 	Log catching status
	 * 	@var boolean
	 */
	private $_ob		= FALSE;

    /**
     * 	Singleton instance of object
     * 	@var Log
     */
    static private $_obj = NULL;

    /**
     * 	Initialization status
     * 	@var bool
     */
    private $_init 		 = FALSE;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Log {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages 10001-10100
			self::$_obj->_msg = [
		    	10001 => '%s',
				10002 => _('Error opening file [%s]'),
				10003 => _('Error writing file [%s]'),
				10004 => _('Cleanup %d log files'),
			];

			// catch console output
			if (!Debug::$Conf['Script']) //3
				self::$_obj->catchConsole(TRUE);
		} elseif (!self::$_obj->_init) {

			self::$_obj->_init = TRUE;

			// register shutdown function
			if (!Debug::$Conf['Script']) { //3
				$srv = Server::getInstance();
				$srv->regShutdown(__CLASS__);
			} //3
		}

	    return self::$_obj;
	}

    /**
	 * 	Shutdown function
	 */
	public function delInstance(): void {

		if (!self::$_obj)
			return;

		// stop logging?
		if (self::$_obj->_log != self::OFF) {
			if (is_resource(self::$_obj->_log))
				fclose(self::$_obj->_log);
			self::$_obj->_log = self::OFF;
		}

		// do not delete object, since all message gets lost
		// self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('Log handler'));
		$xml->addVar('Ver', strval(self::VER));

		if (!$status)
			return;

		$cnf = Config::getInstance();
		$xml->addVar('Opt', _('Log file base name'));
		$xml->addVar('Stat', '"'.$cnf->getVar(Config::LOG_FILE).'"');

		$xml->addVar('Opt', _('Log level'));
		$stat = '';
		if ($this->_loglvl & self::ERR)
			$stat .= 'Error ';
		if ($this->_loglvl & self::WARN)
			$stat .= 'Warning ';
		if ($this->_loglvl & self::INFO)
			$stat .= 'Information ';
		if ($this->_loglvl & self::APP)
			$stat .= 'Application ';
		if ($this->_loglvl & self::DEBUG)
			$stat .= 'Debug ';
		$xml->addVar('Stat', $stat);
	}

	/**
	 * 	Catch all console output
	 *
	 * 	@param	- TRUE = Start catching; FALSE = Stop
	 */
	public function catchConsole(bool $start): void {

		Debug::Msg(($start ? 'Enable' : 'Disable').' console output catching'); //3

		if ($start && !$this->_ob) {
			ob_start();
			$this->_ob = TRUE;
			return;
		}

		// catching active?
		if (!$this->_ob)
			return;

		$this->_ob = FALSE;

		if ($msg = ob_get_clean()) {
			$recs = explode("\n", str_replace('<br />', "\n", strip_tags($msg, '<font><b><i>')));
			foreach ($recs as $rec) {
				if (strlen(trim($rec)))
					self::Msg(self::WARN, 10001, '+++ '.$rec);
			}
		}
	}

	/**
	 * 	Add message definition
	 *
	 * 	@param	- Message definition [ num => message ] or [ num => num ]
	 */
	public function setMsg(array $msg): void {

		foreach ($msg as $c => $m) {
			if (isset($this->_msg[$c]) && strcmp(strval($this->_msg[$c]), strval($m))) //3
				Debug::Err('Message "'.$c.'" already in use with "'.$this->_msg[$c].'" (new: "'.$m.'")!'); //3
			$this->_msg[$c] = $m;
		}
	}

	/**
	 * 	Get message definition
	 *
	 *	@return - All defined messages
	 */
	public function getMsg(): array {
		return $this->_msg;
	}

	/**
	 * 	Add log plugin handler
	 *
	 *  @Param  - Function name
	 * 	@param	- Class object or NULL
	 */
	public function Plugin(string $func, $class = NULL): void {

		$c = $class ? get_class($class) : '';
		$k = $c.':'.$func;
		Debug::Msg('Add output reader "'.$k.'"'); //3
		$this->_plugin[$k] = [ $c, $func ];
	}

	/**
	 * 	Suspend logging
	 *
	 * 	@return	- Saved status
	 */
	public function Suspend(): array {

		$stat          = [ $this->_log, $this->_plugin ];
		$this->_log    = self::OFF;
		$this->_plugin = [];

		return $stat;
	}

	/**
	 * 	Resume logging
	 *
	 * 	@param	- [ Saved status ]
	 */
	public function Resume(array $stat): void {
		$this->_log 	= $stat[0];
		$this->_plugin 	= $stat[1];
	}

	/**
	 * 	Create log message
	 *
	 * 	@param	- Message typ (ERR, WARN, INFO, APP, DEBUG)
	 * 	@param	- Message number
	 * 	@param	- Additional parameter
	 * 	@return	- Log message
	 */
	public function Msg(int $typ, int $no, ...$parm): string {

		$cnf  = Config::getInstance();
		$http = HTTP::getInstance();

		// logging status checked?
		if ($this->_log == self::UNDEF) {
			// check configuration
			switch (strtolower($v = $cnf->getVar(Config::LOG_FILE))) {
			case 'off':
				$this->_log = self::OFF;
				break;

			case 'syslog':
				$this->_log = self::SYSL;
				break;

			default:
				$this->_log = self::FILE;
				$this->_file = $v;
				break;
			}
		}

		// load log level
		if (!Debug::$Conf['Script']) //3
		    $this->_loglvl = $cnf->getVar(Config::LOG_LVL);

		// message available?
		if (!isset($this->_msg[$no]))
			ErrorHandler::Raise($no);

		// unfold special message
		if (!$typ && is_array($parm)) {
			$parm = $parm[0];
			$typ = self::ERR;
		}

		// set message number
		$msg = self::MSG_TYP[$typ & ~self::ONETIME].sprintf(' %04d', $no).' ';

		if (isset($this->_msg[$no]))
			$msg .= is_array($parm) ? vsprintf(is_numeric($this->_msg[$no]) ? $this->_msg[$this->_msg[$no]] : $this->_msg[$no], $parm) : $parm;
		else
			$msg .= is_array($parm) ? implode(' ', $parm) : $parm;

		// limit output length
		if (strlen($msg) > 10240)
			$msg = substr($msg, 0, 10240).sprintf(_('[CUT@%d]'), 10240);

		// one time message?
		if ($typ & self::ONETIME) {
		    $sess = Session::getInstance();
   		    $sess->xpath('//OneTimeMessage');
   			if (($v = $sess->getItem()) !== NULL) {
    			if (strpos($v, strval($no)) !== FALSE)
            		return $msg;
           		$sess->setVal($v.','.$no);
		    }
		}

		// call plugin handler
		foreach ($this->_plugin as $func) {
		    if ($func[0]) {
		        $func[0] = $func[0]::getInstance();
		        $func[0]->{$func[1]}($typ, $msg);
		    } else
		        !$func[1]($typ, $msg);
		}

		// logging disabled
		if ($this->_log == self::OFF || !($this->_loglvl & $typ))
			return $msg;

		// syslog only
		if ($this->_log == self::SYSL) {
			syslog(LOG_INFO, $msg);
			return $msg;
		}

		if ($this->_log == self::FILE) {
 			// open file
			if (!($this->_log = fopen($this->_file.'.'.date('Ymd'), 'a+b'))) {
				$this->_log = self::OFF;
				return self::Error(10002, $this->_file.'.'.date('Ymd'));
			}
		}

		if (!($ip = $http->getHTTPVar('REMOTE_ADDR')))
			$ip = '127.0.0.1';
		if (fwrite($this->_log, date('Y M d H:i:s').' '.str_pad('['.$ip.']', 18, ' ').$msg."\n") === FALSE) {
			fclose($this->_log);
			$this->_log = self::OFF;
			return self::Error(10003, $this->_file, '0');
		}

		return $msg;
	}

	/**
	 *  Force a message to syncgw log file
	 *
	 * 	@param	- Message typ (ERR, WARN, INFO, APP, DEBUG)
	 * 	@param	- Message number
	 * 	@param	- Additional parameter
	 * 	@return	- Log message
	 */
	public function ForceMsg(int $typ, int $no, ...$parm): void {

	    $cnf = Config::getInstance();

	    $mod = $this->_log;
	    $this->_log = self::UNDEF;
	    $file = $cnf->updVar(Config::LOG_FILE, $cnf->getVar(Config::LOG_FILE, TRUE));
		$stat = $cnf->updVar(Config::LOG_LVL, self::ERR|self::WARN);
		self::Msg($typ, $no, ...$parm);

		$cnf->updVar(Config::LOG_LVL, $stat);
		$cnf->updVar(Config::LOG_FILE, $file);
		$this->_log = $mod;
	}

	/**
	 * 	Perform log expiration
	 */
	public function Expiration(): void {

		$cnf = Config::getInstance();

		if (!($path = $cnf->getVar(Config::LOG_FILE)))
			return;

        // check existing file count
		$p = dirname($path);
		$f = substr($path, strlen($p) + 1);
		$l = strlen($f);
		$a = [];
        // log rotate?
    	if ($d = opendir($p)) {
	    	while (($file = readdir($d)) !== FALSE) {
	       	    if (substr($file, 0, $l) == $f)
	       			$a[] = $file;
	       	}
    	}
   		closedir($d);

   		sort($a);

   		if (count($a) <= ($cnt = $cnf->getVar(Config::LOG_EXP)))
   			return;

   		for($n=$cnt; $n < count($a); $n++)
   		    unlink($p.DIRECTORY_SEPARATOR.array_shift($a));

		self::Msg(self::DEBUG, 10004, $n - $cnt);
	}

 }

?>
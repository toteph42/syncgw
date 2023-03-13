<?php
declare(strict_types=1);

/*
 * 	Error handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class ErrorHandler {

	// module version number
	const VER = 4;

	// PHP Error
	const PHP_ERR 		 = [
	        E_ERROR             => 'E_ERROR',
			E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERRO',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
		    E_USER_DEPRECATED   => 'E_USER_DEPRECATEDd',
			E_STRICT            => 'E_STRICT',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERRO',
            E_DEPRECATED        => 'E_DEPRECATE',
    ];

 	/**
	 * 	PHP Error filter
	 * 	@var array
	 */
	private static $_filter = [];

    /**
     * 	Singleton instance of object
     * 	@var ErrorHandler
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): ErrorHandler {

		if (!self::$_obj) {
            self::$_obj = new self();

			// set messages 11601-11700
			$log = Log::getInstance();
			$log->setMsg([
	            11601 => '%s',
				11602 => _('Unknown message code [%s]'),
			]);

	    	// set error handler
			register_shutdown_function([ self::$_obj, 'catchLastError' ]);
			set_error_handler([ self::$_obj, 'catchError' ]);

			// handle XML erros internaly
			libxml_use_internal_errors(TRUE);

			self::resetReporting();
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

		$xml->addVar('Name', _('PHP Error handler'));
		$xml->addVar('Ver', strval(self::VER));

		if (!$status)
			return;

		$cnf = Config::getInstance();
		$xml->addVar('Opt', _('Capture PHP error'));
		$xml->addVar('Stat', $cnf->getVar(Config::PHPERROR) == 'Y' ? _('Yes') : _('No'));
	}

	/**
	 * 	Tell error handler to filter out specifix types of message
	 *
	 *  @param  - PHP error type
	 *  @param 	- File name fragment
	 *  @param 	- PHP function name
	 */
	static function filter(int $typ = E_WARNING, ?string $file = NULL, ?string $func = NULL): void {
		self::$_filter[] = [ $typ, $file, $func ];
	}

	/**
	 * 	Catch last PHP error
	 */
	public function catchLastError() {
		if ($msg = error_get_last())
			return self::catchError($msg['type'], $msg['message'], $msg['file'], $msg['line']);
	}

	/**
	 * 	Error catching function
	 *
	 *  @param  - PHP error code
	 *  @param 	- Error Message
	 *  @param 	- File name
	 *  @param  - Line nmber
	 *  @return - TRUE
	 */
	public function catchError(int $typ, string $errmsg, string $file, int $line) {

		// check filters
		foreach (self::$_filter as $fld) {
			if ($typ & $fld[0] &&
				($fld[1] && stripos($file, $fld[1]) !== FALSE) ||
				($fld[2] && strpos($errmsg, $fld[2]) !== FALSE)) {
				// be sure to clear possible XML errors
				libxml_clear_errors();
				// prevent PHP error handling to appear
    			return TRUE;
			}
		}

		// do not catch errors?
		$cnf = Config::getInstance();
		if ($cnf->getVar(Config::PHPERROR) != 'Y')
			return TRUE;

		// stack trace
		$stack = [];

		// extract back trace stack from fatal error

		switch($typ) {
		case E_ERROR:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
			$msgs   = explode("\n", $errmsg);
			$errmsg = NULL;
			foreach ($msgs as $msg) {
				if (!$errmsg)
					$errmsg = $msg;
				if (substr($msg, 0, 1) == '#')
					$stack[] = '# '.substr($msg, strpos($msg, ' ') + 1);
			}
			$mtyp = Log::ERR;
			break;

		// case E_WARNING:
		// case E_PARSE:
		// case E_CORE_WARNING:
		// case E_COMPILE_WARNING:
		// case E_USER_WARNING:
		// case E_NOTICE:
		// case E_USER_NOTICE:
		// case E_STRICT:
		// case E_DEPRECATED:
		// case E_USER_DEPRECATED:
		default:
			$mtyp = Log::WARN;
			break;
		}

		if (!count($stack))
			$stack = self::Stack();

		//strip off file location
		if ($p = strpos($errmsg, ' in ')) {
			array_unshift($stack, '# '.substr($errmsg, $p + 4));
			$errmsg = substr($errmsg, 0, $p);
		}

		// catch error messages
		$msgs = [ '+++ '.(isset(self::PHP_ERR[$typ]) ? self::PHP_ERR[$typ] : 'Exception').': '.$errmsg, ];
    	error_clear_last();

		// catch XML error
		foreach (libxml_get_errors() as $err) {
			switch ($err->level) {
			case LIBXML_ERR_WARNING:
				$msg = 'Warning';
				break;

			case LIBXML_ERR_ERROR:
				$msg = 'Error';
				break;

			case LIBXML_ERR_FATAL:
			default:
				$msg = 'Fatal Error';
				break;
			}
			$msgs[] = 'XML '.$msg;
			if ($err->file)
				$msgs[] = 'File: '.$err->file.' (line: '.$err->line.', column: '.$err->column.')';
			$msgs[] = 'Message: '.$err->message;
		}
		libxml_clear_errors();

		$log  = Log::getInstance();

		// write messages to log file
		foreach ($msgs as $msg)
			$log->ForceMsg($mtyp, 11601, $msg);

		// write stack trace to log file
		foreach ($stack as $msg)
			$log->ForceMsg($mtyp, 11601, $msg);

		// send header if we're not debugging
		if ($typ != E_USER_ERROR
			&& !Debug::$Conf['Script']  //3
			) {
			$cnf = Config::getInstance(); //2
			if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_OFF) { //2
				header('HTTP/1.0 500 Internal Serer Error');
			   	echo '<font style="'.Util::CSS_TITLE.Util::CSS_ERR.'"><br /><br />'.
					 'Unrecoverable PHP error: <br /><br />';
				foreach ($msgs as $msg)
					echo $msg.'<br />';
			   	echo '<br />Please check log file for more information</font>';
				exit();
			} //2
		}

    	// prevent PHP error handling to appear
    	return TRUE;
	}

	/**
     * 	Get call stack
     *
     *	@param  - Optional stack position
     * 	@return - Stack array
     */
    static public function Stack(int $pos = 0): array {

    	$stack = [];
        $skip  = TRUE;

    	foreach (debug_backtrace() as $call) {

    	    if ($skip) {
    	        $skip = FALSE;
    	        continue;
    	    }

    	    // class available?
    		if (isset($call['class']))
    			$msg = $call['class'].$call['type'];
    		else
    			$msg = NULL;

        	$msg = '#'.($pos++).' '.$msg.$call['function'].'(';
        	if (isset($call['args']) && is_array($call['args'])) {
	    		foreach ($call['args'] as $arg) {
	    			if (is_null($arg)) {
	    				$msg .= 'NULL, ';
	    				continue;
	    			}
	    			if (is_object($arg)) {
	    				$msg .= 'class:'.get_class($arg).', ';
	    				continue;
	    			}
	    			if (is_array($arg)) {
	    				$msg .= 'ARRAY(), ';
	    				continue;
	    			}
	    			if (is_bool($arg)) {
	    				$msg .= $arg ? 'TRUE, ' : 'FALSE, ';
	    				continue;
	    			}
	    			if (is_string($arg)) {
	    				$msg .= '"'.substr($arg, 0, 40).'", ';
	    				continue;
	    			}
	    			if (is_string($arg) && strlen($arg) > 20)
	    				$msg .= substr($arg, 0, 20).'[...], ';
	    			else
	    				$msg .= $arg.', ';
	    		}
				$msg = trim($msg);
    		}
    		if (isset($call['file']))
    			$msg .= ') called at ['.$call['file'].':'.$call['line'].']';
    		else
    			$msg .= ')';
    		$stack[] = $msg;
    	}

    	return $stack;
    }

	/**
	 *  Raise user error and exit
	 *
	 * 	@param	- Message number
	 * 	@param	- Additional parameter
	 */
	static public function Raise(int $no, ...$parm): void {

		$log  = Log::getInstance();
		$msgs = $log->getMsg();

		// get message
		if (isset($msgs[$no]))
			$msg = sprintf($msgs[$no], $parm[0]);
		else
			$msg = sprintf($msgs[11602], $no);

		trigger_error($msg, E_USER_ERROR);

		// send header if we're not debugging
		if (!Debug::$Conf['Script'])  //3
			exit();
	}

	/**
	 * 	Reset PHP error reposting
	 */
	static public function resetReporting(): void {

		$parms = [
			'log_errors' 				=> 'Off',
			'html_errors' 				=> 'Off',
			'ignore_repeated_errors' 	=> 'On',
			'display_errors' 			=> 'Off',
			'error_reporting'			=> E_ALL,
		];
		if (Debug::$Conf['Script']) //3
		$parms = [  //3
			'log_errors' 				=> 'On',  	//3
   			'html_errors' 				=> 'On',	//3
   			'ignore_repeated_errors' 	=> 'Off',  	//3
   			'display_errors' 			=> 'On',  	//3
            'display_startup_errors'    => 'On',  	//3
   			'error_reporting'			=> E_ALL,  	//3
		];  //3

		foreach ($parms as $k => $v) {
			if ($k == 'error_reporting')
				@error_reporting($v);
			else
				@ini_set($k, $v);
			$n = @ini_get($k);
			if ($n != $v) {
				$log = Log::getInstance();
				$log->Msg(Log::WARN, 10709, $k, $n, $v);
			}
		}
	}

}

?>
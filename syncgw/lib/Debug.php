<?php
declare(strict_types=1);

/*
 *  Debug functions
 *
 *	@package	sync*gw
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 *
 */

namespace syncgw\lib;

use syncgw\gui\guiHandler;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

class Debug {

    // message level
    const ColorMsg		 = 0x01;
    // warning level
    const ColorWarn      = 0x02;
    // error level
    const ColorErr       = 0x04;

    /**
     * Debug data array
     * @var array
     */
    static public $Conf = [];

    /**
     * PHP error_reporting status
     * @var int
     */
    private static $_error = -1;

    # -------------------------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * 	Initialize object
     */
    static public function getInstance(): void {

    	// already initialized?
    	if (count(self::$Conf))
    		return;

    	self::$Conf = [

			'stdOut'		=> -1,

	        // force debug output to log file
	        // 'stdOut' 	=> Log::getInstance(),

   			'Status'    	=> FALSE,              		// debug output is off by default
		    'Exclude'       => [],                 		// classes/functions to exclude from debugging messages
		    'Include'		=> [],						// classes/functions to include in debugging messages
		    'IncludeMsg'	=> 0, 				  		// included messages

		    self::ColorMsg  => Util::CSS_CODE,			// set color codes
		    self::ColorWarn => Util::CSS_WARN,
		    self::ColorErr  => Util::CSS_ERR,

		    'DirDeleted'	=> 0,						// flag if file direcory is deleted

 			'DebugUpload'	=> 0,						// debug upload of pouction files (see GUI_FTP.php)
			'DebugExplorer'	=> 0,						// debug explorer (see GUI_Explorer.php)

		    'UseMercury'	=> 1,						// specify whether to use Mercury-Mail or not (0)

			'RC-Dir'		=> 'D:\www\mail',			// local roundcube base directory

    		'Script'		=> '',						// name of running test script

		    'DB-Host'		=> '127.0.0.1',				// data base host
			'DB-Port'		=> '3306',					// data abse port

			'DB-Name'		=> 'mail',					// data base
			'DB-UID'		=> 'User',					// data base user
			'DB-UPW'		=> 'user',					// data base password
    	];

 		if (self::$Conf['UseMercury'])
			self::$Conf += [
				'ScriptUID'		=> 't1@dev.fd',			// user id for testing scripts
##				'ScriptUID'		=> 'debug@dev.fd',		// user id for testing scripts
				'ScriptUPW'		=> 'mamma',				// password for testing script user id

				'DebugUID'		=> 'debug@dev.fd',		// debug user id
				'DebugUPW'		=> 'mamma',				// password fpr debug user id

				'Imap-Host'		=> 'mail.fd',
				'Imap-Port'		=> '143',
				'Imap-Enc'		=> '',
				'Imap-Cert'		=> 'N',
				'SMTP-Host'		=> 'mail.fd',
				'SMTP-Port'		=> '25',
				'SMTP-Enc'		=> '',
				'SMTP-Auth'		=> 'N',
			];
		else
			self::$Conf += [
				'ScriptUID'		=> 'i329108_0-t1',		// user id for testing scripts
				'ScriptUPW'		=> 'ku@hhdK@8h&nfKg',	// password for testing script user id
##				'ScriptUID'		=> 'i329108_0-jam',		// user id for testing scripts
##				'ScriptUPW'		=> '8(b4H&4(Uw!%U2v',	// password for testing script user id

				'DebugUID'		=> 'i329108_0-debug',	// debug user id
				'DebugUPW'		=> 'wo@HzM4@hS7G?nZ',	// password fpr debug user id

				'Imap-Host'		=> 'imap.1blu.de',
				'Imap-Port'		=> '993',
				'Imap-Enc'		=> 'SSL',
				'Imap-Cert'		=> 'Y',
				'SMTP-Host'		=> 'smtp.1blu.de',
				'SMTP-Port'		=> '465',
				'SMTP-Enc'		=> 'SSL',
				'SMTP-Auth'		=> 'Y',
			];
	}

	/**
     * 	Debug object
     *
     * 	@param	- object, [] or string
     * 	@param	- Title
     * 	@param	- Position in string
     * 	@param	- Limit length to show
     *  @param  - Output color
     */
    static public function Msg($obj, string $title = '', int $pos = -1, int $limit = 0, int $color = self::ColorMsg): void {

        // debug off?
        if (!self::$Conf['Status'])
        	return;

        if (is_int(self::$Conf['stdOut']))
        	self::$Conf['stdOut'] = self::$Conf['Script'] ? NULL : guiHandler::getInstance();

        // get stack information
        $call = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // check for class / functions exclusions from debugging
	    $ok = FALSE;
        if (!($color & Debug::ColorErr)) {
	        foreach ($call as $k => $c) {

	            // normalize array
	            if (!isset($c['class']))
	                $c['class'] = $call[$k]['class'] = '';
	            if (!isset($c['function']))
	                $c['class'] = $call[$k]['function'] = '';

	            foreach (self::$Conf['Include'] as $chk => $unused) {
	            	if (strpos($c['class'], $chk) !== FALSE || strpos($c['function'], $chk) !== FALSE) {
	            		$ok = TRUE;
	            		break;
	            	}
	            }
	            if ($ok)
	            	break;

	            $unused; // disable Eclipse warning

	            if (((isset(self::$Conf['Exclude'][$c['class']]) && self::$Conf['Exclude'][$c['class']]) ||
	                (isset(self::$Conf['Exclude'][$c['class'].':'.$c['function']]) && self::$Conf['Exclude'][$c['class'].':'.$c['function']]) ||
	                (isset(self::$Conf['Exclude'][$c['function']]) && self::$Conf['Exclude'][$c['function']])))
	                return;
	        }
        }

        // check exluded message types from debugging
        if (!$ok && !(self::$Conf['IncludeMsg'] & $color))
            return;

        // get caller
        while (count($call) > 1 && !strcmp(substr($call[0]['file'], -9), 'Debug.php'))
            array_shift($call);
        $call = $call[0];
        $call['file'] = substr($call['file'], strlen($_SERVER['DOCUMENT_ROOT']) + 1);

        // build output string
      	$msgs = NULL;

       // array?
        if (is_array($obj)) {
            ob_start();
       		print_r($obj);
       		$msg = ob_get_contents();
       		ob_end_clean();
            $msgs = explode("\n", str_replace("\r", '', $msg));
            array_pop($msgs);
        }
        // object?
        elseif (is_object($obj)) {
        	if (strpos(get_class($obj), 'syncgw\\') !== FALSE)
    	        $msgs = explode("\n", $obj->saveXML(FALSE, TRUE));
        	else
        		$msgs = explode("\n", print_r($obj, TRUE));
        }
		// it must be string
        else {

	       // dump string
	        if ($pos > -1 || $limit > 0) {

	           	// set limit
	       		$l = strlen($obj);
	           	if ($limit)
	                $limit = $l > $limit ? $limit : $l;
	       		else
	           		$limit = $l;

	       		$wrk = sprintf('%08X', $pos);
	       		for ($str='', $hex='', $i=0; $i < $limit; $i++) {
	       			$c    = $obj[$pos++];
	           		$hex .= sprintf('%02X ', ord($c));
	       			if (preg_replace('/[\p{Cc}]/u', '', $c))
	       				$str .= $c == ' ' ? '.' : $c;
	       			else
	           			$str .= $c == "\n" ? '.' : ($c == '0' ? $c : '.');
	       			if (!(($i + 1) % 4)) {
	           			$hex .= '| ';
	    		     	$str .= ' | ';
	       			}
	           		if (!(($i + 1) % 20)) {
	           			$msgs[] = $wrk.'  '.$hex.'  '.$str;
	       				$wrk = sprintf('%08X', $pos);
	       				$hex = '';
	       				$str = '';
	       			}
	       		}

	       		// fill up
	       		while ($i++ % 20) {
	           		$hex .= '   ';
	    		    if (!($i % 4))
	       				$hex .= '| ';
	           	}
	           	$msgs[] = $wrk.'  '.$hex.'  '.$str;
	        } elseif (strpos(strval($obj), '<code') !== FALSE)
	        	$msgs = explode('<br />', $obj);
	        else
	        	$msgs[] = str_replace([ "\r", "\n" ], [ '', '<br />' ], strval($obj));
        }

        // build prefix (filename:line no)
	    $pref = '['.(isset($call['file']) ? $call['file'].':'.$call['line'] : '').']';

	    if ($color & (self::ColorErr|self::ColorWarn)) {
	    	if ($title)
		    	$title = '+++ '.$title;
			else
				$msgs[0] = '+++ '.$msgs[0];
	    }

	    if (is_object(self::$Conf['stdOut']) && get_class(self::$Conf['stdOut']) != 'Log') {

			if (!$title)
       			self::$Conf['stdOut']->putMsg('<div style="width:423px;float:left;">'.XML::cnvStr($pref).'</div>'.
       						 '<div style="float:left;">'.XML::cnvStr($msgs[0]).'</div>', self::$Conf[$color]);
       		else {
       			$wrk = '';
       			foreach ($msgs as $msg) {
       				if (strpos($msg, '<code') !== FALSE) {
       					$wrk = implode('<br />', $msgs);
       					break;
      				}
       				$wrk .= XML::cnvStr($msg).'<br />';
       			}
         		self::$Conf['stdOut']->putQBox('<div style="float:left;"><div style="float:left;'.self::$Conf[$color].'width:400px;">'.XML::cnvStr($pref).'</div>'.
   			    			  '<div style="'.self::$Conf[$color].'">'.XML::cnvStr($title).'</div></div>', '',
   			                  '<code style="'.self::$Conf[$color].'">'.$wrk.'</code>', FALSE, 'Msg');
       		}
       		return;
		}

	    // inject title?
	    if ($title) {
            $title = str_repeat('-', 15).' '.$title.' ';
			if (($l = 116 - strlen($title)) && $l > 0)
               	$title .= str_repeat('-', $l);
	    }

		if ($title)
			array_unshift($msgs, $title);

		// show messages
		foreach ($msgs as $msg) {

			if (strlen($msg) > 512)
				$msg = substr($msg, 0, 512).'['.strlen($msg).'-CUT@512]';

    	   	// show message
            if (!self::$Conf['stdOut']) {
  				echo '<div><div style="'.self::$Conf[$color].'width:400px;float:left;">'.XML::cnvStr($pref).'</div>'.
				   	 '<div style="'.self::$Conf[$color].'"> '.XML::cnvStr($msg).'</div>'.'</div>';
            } elseif (get_class(self::$Conf['stdOut']) == 'Log')
            	self::$Conf['stdOut']->Msg(Log::INFO, 10001, $pref.$msg);
	    }
	}

    /**
     * 	Debug object (warning)
     *
     * 	@param	- object, [] or string
     * 	@param	- Title
     * 	@param	- Position in string
     * 	@param	- Limit length to show
     */
    static public function Warn($obj, string $title = '', int $pos = -1, int $limit = 0): void {
        self::Msg($obj, $title, $pos, $limit, self::ColorWarn);
    }

    /**
     * 	Debug object (error)
     *
     * 	@param	- object, [] or string
     * 	@param	- Title
     * 	@param	- Position in string
     * 	@param	- Limit length to show
     */
    static public function Err($obj, string $title = '', int $pos = -1, int $limit = 0): void {
        self::Msg($obj, $title, $pos, $limit, self::ColorErr);
    }

    /**
     * 	Turn output on/off
     *
     * 	@param  - TRUE=on; FALSE=off
     *  @param  - Messages to show (defaults to all)
     *  @return - Old selected messages
     */
    static public function Mod(bool $mod = FALSE, int $msg = (self::ColorMsg|self::ColorWarn|self::ColorErr)): int {
    	$stat = self::$Conf['IncludeMsg'];
    	if (self::$Conf['Status'] = $mod)
    		self::$Conf['IncludeMsg'] = $msg;
    	return $stat;
    }

	/**
	 * 	Cleanup debug directory
	 *
	 * 	@param 	- File name pattern
	 */
    static public function CleanDir(string $file): void {

		$cnf = Config::getInstance();
		array_map('unlink', glob($cnf->getVar(Config::DBG_DIR).DIRECTORY_SEPARATOR.$file));
    }

    /**
     * 	Save content to file
     *
     * 	@param	- File name skeleton (e.g. "Raw%d.xml")
     * 	@param	- Data to store
     *  @param  - TRUE = Whole XML; FALSE = From current position
     * 	@return	- Debug file name or NULL on error
     */
    static public function Save(string $fnam, $data, bool $top = TRUE): ?string {

    	$cnf = Config::getInstance();
    	$d   = $cnf->getVar(Config::DBG_DIR);
    	if (strpos($fnam, '%') !== FALSE) {
        	$n = 1;
        	do {
        		$name = $d.sprintf($fnam, $n);
        		if ($n++ > 999)
        		    break;
        	} while (file_exists($name));
        	if ($n > 999)
        	    return NULL;
    	} else
    	    $name = $d.$fnam;

        if (is_array($data)) {
            ob_start();
       		print_r($data);
       		$data = ob_get_contents();
       		ob_end_clean();
            $data = str_replace("\r", '', $data);
        }
        // XML object?
        elseif (is_object($data))
            $data = $data->saveXML($top, TRUE);

    	file_put_contents($name, $data);

    	if (!Debug::$Conf['Script']) {
    		$log = Log::getInstance();
    		$log->Msg(Log::INFO, 10001, 'Debug data saved to "'.$name.'"');
    	} else
            Debug::Msg('Data saved to "'.$name.'"');

        return $name;
    }

}

?>
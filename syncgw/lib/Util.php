<?php
declare(strict_types=1);

/*
 * 	Utility functions class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

use DateTime;

class Util {

	// module version number
	const VER 		  = 26;

	// CSS message types definition
	const CSS_NONE	  = '';
	const CSS_TITLE	  = 'font-weight: bold; font-weight: bold;';
	const CSS_ERR	  = 'color: #DF0101; font-weight: bold;';		// red
	const CSS_WARN	  = 'color: #FF8000; font-weight: bold;';		// yellow
	const CSS_INFO	  = 'color: #01DF01;';							// green
	const CSS_APP	  = 'color: #06D5F7;';							// ligth blue
	const CSS_DBG	  = 'color: #FF00FB;';							// turquoise

	const CSS_CODE	  = 'color: #0040FF;';							// blue
	const CSS_QBG	  = 'background-color: #F2F2F2;';				// back ground color for Q-boxed
	const CSS_SBG	  = 'background-color: #E6E6E6;';				// back ground color for selected record

	const UTC_TIME    = 'Ymd\THis\Z';								// UTC date/time format
	const STD_TIME    = 'Ymd\THis';									// standard date/time format
	const STD_DATE    = 'Ymd';										// standard day format
																	// Activesync date/time format
																	// YYYY-MM-DDTHH:MM:SS.MSSZ where
																	// YYYY = Year (Gregorian calendar year)
																	// MM = Month (01 - 12)
																	// DD = Day (01 - 31)
																	// HH = Number of complete hours since midnight (00 - 24)
																	// MM = Number of complete minutes since start of hour (00 - 59)
																	// SS = Number of seconds since start of minute (00 - 59)
																	// MSS = Number of milliseconds. This portion of the string is optional.
																	// The T serves as a separator, and the Z indicates that this time is in UTC
	const masTIME   = 'Y-m-d\TH:i:s.\0\0\0\Z';
	const RFC_TIME	 = 'D, M Y G:i:s e';							// Fri, 30 Sep 2022 09:03:21 GMT

	const DBG_PREF 	 = 'Dbg-';										// debug device record prefix //2
	const DBG_PLEN   = 4;											// debug prefix length //2

	// picture formats
	const PIC_FMT     = [
			'jpeg'	=> 'imagejpeg',
			'jpg'	=> 'imagejpeg',
			'gd2'	=> 'imagegd2',
			'gd'	=> 'imagegd',
			'gif'	=> 'imagegif',
			'png'	=> 'imagepng',
			'wmbp'	=> 'imagewbmp',
			'xbm'	=> 'imagexbm',
	];

	// HID() parameter
	const HID_TAB    = 0;											// get internal table names
	const HID_ENAME  = 1;											// get external names
	const HID_CNAME  = 2;											// get handler class name
	const HID_PREF   = 3;											// get short names (GUID prefix)

 	/**
	 * 	Build valid path relative to sync*gw root directory
	 *
	 * 	@param	- Relative path to sync*gw root directory
	 * 	@return	- Converted path
	 */
	static function mkPath(?string $rel = NULL): string {

	    // path to syncgw base directory
        static $_path = '';

        if (!$_path) {
            $p = str_replace('/', DIRECTORY_SEPARATOR, __DIR__);
            $a = explode(DIRECTORY_SEPARATOR, $p);
            array_pop($a);
            $_path = implode(DIRECTORY_SEPARATOR, $a).DIRECTORY_SEPARATOR;
        }

        return $_path.str_replace('/', DIRECTORY_SEPARATOR, $rel ? $rel : '');
	}

	/**
	 * 	Get unused file name in tmp. directory
	 *
	 * 	@param	- Optional file extension (defaults to "tmp")
	 * 	@return	- Normalized full file name
	 */
	static function getTmpFile(string $ext = 'tmp'): string {

		$cnf = Config::getInstance();
		$dir = $cnf->getVar(Config::TMP_DIR);

		// create unique file name
		do {
			$name = $dir.uniqid().'.'.$ext;
		} while (file_exists($name));

		return $name;
	}

	/**
	 *  Replace any non a-z, A-Z and 0-9 character with "-" in file name
	 *
	 * 	@param	- File name
	 * 	@return	- Converted name
	 */
	static function normFileName(string $name): string {

		if ($p = strrpos($name, '.')) {
			$ext = substr($name, $p);
			$nam = substr($name, 0, $p - 1);
		} else {
			$nam = $name;
			$ext = '';
		}

   		$nam = preg_replace('|[^a-zA-Z0-9]+|', '-', $nam);

   		return $nam.$ext;
	}

	/**
	 *  Convert MIME type to file name extension
	 *
	 *  @param  - MIME Type
	 *  @return - File name extension or NULL if not found
	 */
	static function getFileExt(string $mime): ?string {
        static $_mime = NULL;

		// load mime mapping table
	    if (!$_mime) {
            $_mime = new XML();
            $_mime->loadFile(Util::mkPath('source'.DIRECTORY_SEPARATOR.'mime_types.xml'));
	    }

	    $_mime->xpath('//Name[text()="'.strtolower($mime).'"]/..');
	    if ($_mime->getItem() === FALSE)
	         return NULL;

	    return '.'.$_mime->getVar('Ext', FALSE);
	}

	/**
	 * 	Delete directory (and content)
	 *
	 * 	@param 	- Direcory path
	 */
	static function rmDir(string $dir): bool {
	    static $_lvl = 0;
	    static $_err;

	    if (substr($dir, -1) != DIRECTORY_SEPARATOR)
	    	$dir .= DIRECTORY_SEPARATOR;

		if (!file_exists($dir) || !is_dir($dir) || !($h = opendir($dir))) {
		    Debug::Warn('Error deleting file "'.$dir.'"'); //3
		    return FALSE;
		}

		if (!$_lvl)
		    $_err = FALSE;

		while($file = readdir($h)) {
			if ($file != '.' && $file != '..') {
			    if (!is_dir($dir.$file)) {
					if (!unlink($dir.$file))
					    $_err = TRUE;
			    } else {
			        $_lvl++;
			        if (!self::rmDir($dir.$file.DIRECTORY_SEPARATOR)) {
			            closedir($h);
            		    Debug::Warn('Error deleting file "'.$dir.'"'); //3
			            return FALSE;
			        }
			    }
			}
		}
		closedir($h);
		if (!$_err) {
			// ignore '.' and '..'
			if (count(scandir($dir)) == 2)
		    	rmdir($dir);
		}

		return TRUE;
	}

	/**
	 * 	Fold array to string
	 *
	 * 	@param 	- Input array
	 * 	@param	- Seperator character
	 *	@return - Output string
	 */
	static function foldStr(array $recs, string $sep): string {

		$str = '';
		foreach ($recs as $r) {
		    if (is_null($r))
		        $r = '';
   			$str .= strval($r).$sep;
		}

		return strlen($str) ? substr($str, 0, -1) : $str;
	}

	/**
	 * 	Unfold string
	 *
	 * 	@param 	- Input string
	 * 	@param	- Seperator string
	 * 	@param	- # of parameter or 0 for any (Default)
	 * 	@return - Output array
	 */
	static function unfoldStr(string $str, string $sep, int $cnt = 0): array {

		$str = str_replace('\\'.$sep, '\x01', $str);
		$out = [];
		$n = 0;
		foreach (explode($sep, $str) as $v) {
			$v = str_replace('\x01', '\\'.$sep, $v);
			$out[] = $v ? $v : '';
			$n++;
		}
		while ($cnt && $n++ < $cnt)
			$out[] = '';

		return $out;
	}

	/**
	 *  Sleep for a while
	 *
	 *  @param  - > 0 = seconds; else < 1 second
	 */
	static function Sleep(int $sec = 0): void {

	    if (!$sec)
    	    // get value < 1 second
	        usleep(rand(500000, 1000000));
	    else
	        sleep($sec);
	}

	/**
	 * 	Get datastore handler array
	 *
	 * 	@param	- Data typ to obtain<fieldset>
	 * 			  Util::HID_TAB    Internal table names<br>
	 * 			  Util::HID_ENAME  External names<br>
	 * 			  Util::HID_PREF   Short name (GUID prefix)<br>
	 * 			  Util::HID_CNAME  Handler class name
	 * 	@param	- Bit map to decode (defaults to DataStore::ALL)
	 * 	@param	- TRUE=All defined; FALSE=All Config::ENABLED (default)
	 * 	@return	- [ Config => name ] or name (if one bit is only set in $map)
	 */
	static public function HID(int $mod, int $map = DataStore::ALL, bool $all = FALSE) {
	    static $_hd = NULL;

	    // remove internal / external bit
    	$map &= ~(DataStore::EXT);

	    if (!$_hd)
	    	// must be static variable, since we use _() function
            $_hd = [
				// Datastore               Util::HID_TAB   		Util::HID_ENAME   	 	  	Util::HID_CNAME					Util::HID_PREF
				DataStore::SESSION  	   => [ 'Session',	    _('Session'),	     	 	'syncgw\lib\Session',		    'S', ],
				DataStore::TRACE    	   => [ 'Trace',		_('Trace'),		     	  	'syncgw\lib\Trace',			    'X', ],
				DataStore::DEVICE   	   => [ 'Device',		_('Device'),	     	  	'syncgw\lib\Device',		    'D', ],
				DataStore::USER		       => [ 'User',		    _('User'),		     	  	'syncgw\lib\User',			    'U', ],
                DataStore::ATTACHMENT      => [ 'Attachments',  _('Attachments'),    	  	'syncgw\lib\Attachment',        'Z', ],

				DataStore::CONTACT  	   => [ 'Contact',	    _('Contact'),	     	  	'syncgw\document\docContact',	'A', ],
				DataStore::CALENDAR 	   => [ 'Calendar',	    _('Calendar'),	     	  	'syncgw\document\docCalendar',	'C', ],
				DataStore::TASK    	       => [ 'Task',		    _('Task'),		     	  	'syncgw\document\docTask',		'T', ],
				DataStore::NOTE     	   => [ 'Note',		    _('Note'),		     	  	'syncgw\document\docNote',		'N', ],
				DataStore::MAIL		  	   => [ 'Mail',			_('Mail'),	       	 	  	'syncgw\document\docMail',		'M', ],
				DataStore::SMS		  	   => [ 'SMS',			_('SMS'),	       	 	  	'syncgw\document\DocSMS',		'F', ],
				DataStore::docLib	  	   => [ 'docLib',		_('docLib'),	     	  	'syncgw\document\DocDoc',		'L', ],
				DataStore::GAL		  	   => [ 'GAL',			_('Global Address Book'),	'syncgw\document\docGAL',		'A', ],
            ];

		// count # of bits to check
		$b = 0;
		for ($n=$map; $n; $n >>= 1) {
			if ($n & 1)
				$b++;
		}

		$rc = [];

		// get enabled data stores only
		if (!$all) {
		    $cnf = Config::getInstance();
			$map &= ($cnf->getVar(Config::ENABLED)|DataStore::SYSTEM);
		}

		// swap data
  		foreach ($_hd as $k => $v) {
			if ($v[$mod] && $map & $k)
    			$rc[$k] = $v[$mod];
		}

		return $b == 1 ? array_pop($rc) : $rc;
	}

	/**
	 *  Compare array - based on http://code.iamkate.com/php/diff-implementation/
	 *
	 *  @param  - [ lines ]
	 *  @param  - [ lines ]
	 *  @param  - [ exclusions ]
	 *  @return - [ # of differences (in lower case) ] [ output string ]
	 */
	static function diffArray(array $arr1, array $arr2, ?array $ex = NULL): array {

	    // change counter
	    $cnt = 0;
	    // output buffer
	    $out = '';

	    // Debug::Save('%d-arr1', $arr1); //3
	    // Debug::Save('%d-arr2', $arr2); //3

	    // check array type
	    if (!isset($arr1[0])) {
	        $tab = [];
	        foreach ($arr1 as $k => $v)
	            $tab[] = $k.': '.$v;
	        $arr1 = $tab;
	    }
	    if (!isset($arr2[0])) {
	        $tab = [];
	        foreach ($arr2 as $k => $v)
	            $tab[] = $k.': '.$v;
	        $arr2 = $tab;
	    }

	    // check exlusion
	    if (is_null($ex))
	        $ex = [];

        // initialise the sequences and comparison start and end positions
        $pos = 0;

        $e1 = count($arr1) - 1;
        $e2 = count($arr2) - 1;

        // skip any common prefix
        while ($pos <= $e1 && $pos <= $e2 && $arr1[$pos] == $arr2[$pos])
            $pos++;

        // skip any common suffix
        while ($e1 >= $pos && $e2 >= $pos && $arr1[$e1] == $arr2[$e2]) {
            $e1--;
            $e2--;
        }

        // determine the lengths to be compared
        $l1 = $e1 - $pos + 1;
        $l2 = $e2 - $pos + 1;

        // initialise the table
        $tab = [ array_fill (0, $l2 + 1, 0) ];

        // loop over the rows
        for ($i1=1; $i1 <= $l1; $i1++) {

            // create the new row
            $tab[$i1] = [ 0 ];

            // loop over the columns
            for ($i2=1; $i2 <= $l2; $i2++) {
                if ($arr1[$i1 + $pos - 1] == $arr2[$i2 + $pos -1])
                    $tab[$i1][$i2] = $tab[$i1 - 1][$i2 - 1] + 1;
                else
                    $tab[$i1][$i2] = max($tab[$i1 - 1][$i2], $tab[$i1][$i2 - 1]);
            }
        }

        // partual differences
        $diff = []; //2

        // initialise the indices
        $i1 = count($tab) - 1;
        $i2 = count($tab[0]) - 1;

        // loop until there are no items remaining in either sequence
        while ($i1 > 0 || $i2 > 0) {

            // check what has happened to the items at these indices

            // on exlusion list?
            $line = strtolower(isset($arr1[$i1 + $pos - 1]) ? $arr1[$i1 + $pos - 1] : '');
	        $f    = 0;
	        foreach ($ex as $tag) {
    	       if (strpos($line, $tag) !== FALSE) {
	               $f++;
	               break;
    	       }
            }
            $line = strtolower(isset($arr2[$i2 + $pos - 1]) ? $arr2[$i2 + $pos - 1] : '');
	        foreach ($ex as $tag) {
    	       if (strpos($line, $tag) !== FALSE) {
	               $f++;
	               break;
    	       }
            }

            if ($f == 2) {
                $c = strpos($arr1[$i1 + $pos - 1], '<!--') !== FALSE ? Util::CSS_INFO : Util::CSS_CODE; //2
                $diff[] = '<code style="'.$c.'">'.XML::cnvStr('X '.$arr1[$i1 + $pos - 1]).'</code><br />'; //2
                if ($i1 > 0)
	                $i1--;
                $i2--;
            } elseif ($i1 > 0 && $i2 > 0 && $arr1[$i1 + $pos - 1] == $arr2[$i2 + $pos - 1]) {
                // update the diff and the indices
                $c = strpos($arr1[$i1 + $pos - 1], '<!--') !== FALSE ? Util::CSS_INFO : Util::CSS_CODE; //2
                $diff[] = '<code style="'.$c.'">'.XML::cnvStr('= '.$arr1[$i1 + $pos - 1]).'</code><br />'; //2
                if ($i1 > 0)
	                $i1--;
                $i2--;
            } elseif ($i2 > 0 && $tab[$i1][$i2] == $tab[$i1][$i2 - 1]) {
                // update the diff and the indices
                $diff[] = '<code style="'.self::CSS_WARN.'">'.XML::cnvStr('+ '.$arr2[$i2 + $pos - 1]).'</code><br />'; //2
                $i2--;
                $cnt++;
            } else {
                // update the diff and the indices
                $diff[] = '<code style="'.self::CSS_WARN.'">'.XML::cnvStr('- '.$arr1[$i1 + $pos - 1 ]).'</code><br />'; //2
                $cnt++;
                if ($i1 > 0)
	                $i1--;
            }
        }

        if (Debug::$Conf['Script']) //3
	        return [ $cnt, '' ]; //3

        // generate the full diff

        for ($i=0; $i < $pos; $i++) { //2
            $c = strpos($arr1[$i], '<!--') !== FALSE ? Util::CSS_INFO : Util::CSS_CODE; //2
            $out .= '<code style="'.$c.'">'.XML::cnvStr('= '.$arr1[$i]).'</code><br />'; //2
        } //2

        while (count($diff) > 0) //2
        	$out .= array_pop($diff); //2

        for ($i=$e1+1; $i < count($arr1); $i++) { //2
        	$c = strpos($arr1[$i], '<!--') !== FALSE ? Util::CSS_INFO : Util::CSS_CODE; //2
            $out .= '<code style="'.$c.'">'.XML::cnvStr('= '.$arr1[$i]).'</code><br />'; //2
        } //2

        return [ $cnt, $out ];
	}

	/**
	 * 	Convert date / time string to UTC UNIX time stamp
	 *
	 *  @param	- Date / time string
	 *  @param	- Optional time zone ID (or '' = default)
	 *  @return	- UNIX time stamp as string or NULL
	 */
	static function unxTime(string $str, ?string $tzid = NULL): ?string {

		if (!strlen($str))
			return NULL;

		if (($l = strlen($str)) == 8)
			$str .= 'T000000Z';
		elseif ($l == 10)
		    $str .= ' 00:00:00';

		// check for time zone conversion
		if ($tzid) {
			try {
			    $t = new \DateTime($str, new \DateTimeZone($tzid));
			} catch(\Exception $e) {
				$t = '';
			}
			if ($t) {
				$t->setTimezone(new \DateTimeZone('UTC'));
				return $t->format('U');
			}
		}

		if (($v = strtotime($str)) === FALSE) {
			// try to reformat string
			// 2022 Jan 14 09:29:22
			if (($v = DateTime::createFromFormat("Y M d H:i:s", $str)) !== FALSE)
				$v = $v->getTimestamp();
			else {
				// special hack for non existing date
				if ($str == '0000-00-00 00:00:00')
					$t = '19700101T000000Z';
				else {
					$t = '20380101T000000Z';
					if (Debug::$Conf['Script']) { //3
	     				Debug::Err('Invalid time stamp "'.$str.'" - using "'.$t.'"'); //3
	    				foreach (ErrorHandler::Stack() as $rec) //3
	    				    Debug::Msg($rec); //3
	    			} //3
				}
				$v = strtotime($t);
			}
		}

		return strval($v);
	}

	/**
	 * 	Convert any date / time string to UTC time stamp
	 *
	 *  @param	- Date / time string
	 *  @param	- Optional time zone ID (or NULL for default)
	 *  @param  - TRUE=Date only; FALSE=Date/Time (Default)
	 *  @return	- UTC time / date
	 */
	static function utcTime(string $str, ?string $tzid = NULL, bool $mod = FALSE): string {

		$f = $mod ? self::STD_TIME: self::UTC_TIME;

		return gmdate($f, intval(Util::unxTime($str, $tzid)));
	}

	/**
	 * 	Get PHP time zone based on offset
	 *
	 * 	@param 	- Time zone name (Australia/Perth) or Short name (GMT) or UTC- / Daylight-offset (-3600/-3600)
	 * 	@return	- Time zone name or NULL
	 */
	static function getTZName(string $name): ?string {

		$name = trim($name);

		// sepecial hack to catch "Etc/UTC" and "Etc/GMT"
		if (stripos($name, "etc") !== FALSE)
			$name = "UTC";

		// validate time zone name (e.g. Australia/Perth)
		if (in_array($name, timezone_identifiers_list())) {
            Debug::Msg('Time zone "'.$name.'" validated'); //3
            return $name;
	   	}

	   	$tzn = strtolower($name);
		$tz  = timezone_abbreviations_list();

		// find time zone by abbreviation (GMT)
		if (isset($tz[$tzn])) {
			// we take the first one
            $tzid = $tz[$tzn][0]['timezone_id'];
            Debug::Msg('Time zone "'.$name.'" converted to "'.$tzid.'"'); //3
            return $tzid;
		}

        // get time zone name by offset (28800/-3600)
		if (strpos($name, '/') === FALSE) {
        	Debug::Msg('Invalid time zone "'.$name.'"'); //3
			return NULL;
		}

		list($utc, $dst) = explode('/', $name);
       	$tzid = NULL;
		$sb = [];

		// save any time zone with proper offset
        foreach ($tz as $a) {
            foreach ($a as $c) {
            	if ($utc == $c['offset'] && !$c['dst'] && $c['timezone_id']) {
            		$sb[] = $c['timezone_id'];
            	}
            }
        }

        // try to locate proper dst
        foreach ($tz as $a) {
            foreach ($a as $c) {
            	if ($dst == $c['offset'] && $c['dst'] && in_array($c['timezone_id'], $sb)) {
                    $tzid = $c['timezone_id'];
                    break;
            	}
            }
        }

        // did we find?
        if (!$tzid && count($sb))
        	$tzid = $sb[0];

		if ($tzid) //3
	        Debug::Msg('Time zone "'.$name.'" converted to "'.$tzid.'"'); //3
		else //3
	        Debug::Warn('Time zone "'.$name.'" not found!'); //3

       	return $tzid;
	}

	/**
	 * 	Get time zone changes in a given period
	 *
	 * 	@param 	- Name of time zone
	 * 	@param 	- Start Time
	 * 	@param 	- End Time
	 * 	@return - Transition buffer
	 */
	static function getTransitions(string $name, int $start, int $end): array {

		$start = mktime(0, 0, 0, 1, 1, intval(gmdate('Y', $start)));
		Debug::Msg('Checking time between "'.gmdate(Util::UTC_TIME, intval($start)).'" and "'.gmdate(Util::UTC_TIME, intval($end)).'"'); //3

		$trans = [ 'STANDARD' => [], 'DAYLIGHT' => [] ];
		$tz = new \DateTimeZone($name);
		$tz = $tz->getTransitions();

		for($i=0; isset($tz[$i]); $i++) {
	   		if ($tz[$i]['ts'] > $end)
	   			break;
			if ($tz[$i]['ts'] > $start) {
				$trans[ $tz[$i]['isdst'] ? 'DAYLIGHT' : 'STANDARD' ][] = $tz[$i];
				if (count($trans['STANDARD']) < count($trans['DAYLIGHT']))
					$trans['STANDARD'][] = $tz[$i - 1];
	   		}
	   	}

	   	if (!count($trans['STANDARD'])) {
			$d = new \DateTime(gmdate(\DateTimeInterface::ISO8601, $start), new \DateTimeZone('UTC'));
			$d->setTimezone(new \DateTimeZone($name));
			$trans['STANDARD'][] = [ 'ts' => $start, 'time' => $d->format(\DateTimeInterface::ISO8601),
									 'offset' => $d->format('Z'), 'isdst' => '', 'abbr' => $d->format('T') ];
	   	}

		Debug::Msg($trans, 'Time zone "'.$name.'" contains '.count($trans['STANDARD']).' "STANDARD" and '. //3
						   count($trans['DAYLIGHT']).' "DAYLIGHT" entries'); //3

	   	return $trans;
	}

	/**
	 *  Convert ISO-8601 duration
	 *  https://tools.ietf.org/html/rfc5545#section-3.3.6
	 *
	 *  @param  - TRUE = String to seconds; FALSE = Seconds to string
	 *  @param  - Duration parameter
	 *  @return - TRUE = Seconds / String or NULL on error
	 */
	static function cnvDuration(bool $mod, $str): ?string {

	    if (is_null($str))
	    	return '0';

	    if ($mod) {
    	    if (strpos($str, 'P') === FALSE)
    	        return NULL;
    	    $sec = strtotime('19700101UTC+'.str_replace(
    	               [ 'P', 'T', 'W', 'D', 'H', 'M', 'S' ], [ '', '', 'Week', 'Day', 'Hours', 'Minute', 'Second' ], $str));
    	    if (!$sec)
    	    	$sec = 0;
    	    Debug::Msg('"'.$str.'" converted to second "'.$sec.'"'); //3
    	    return strval($sec);
	    }
	    $old = $sec = intval($str);
	    $d1  = $sec < 0 ? '-P' : 'P';
	    $sec = abs($sec);
	    // 60*60*24*7
	    if ($n = floor($sec / 604800))
	        $d1 .= sprintf('%dW', $n);
	    $sec %= 604800;
	    // 60*60*24
	    if ($n = floor($sec / 86400))
	        $d1 .= sprintf('%dD', $n);
	    $d2 = '';
        $sec %= 86400;
        // 60*60
        if ($n = floor($sec / 3600))
	        $d2 .= sprintf('%dH', $n);
        $sec %= 3600;
        if ($n = $sec / 60)
	        $d2 .= sprintf('%dM', $n);
        if (($sec %= 60) || !$old)
    	    $d2 .= sprintf('%dS', $sec);

    	$dur = $d1.($d2 ? 'T' : '').$d2;

        Debug::Msg('"'.$str.'" converted to duration "'.$dur.'"'); //3

        return $dur;
	}

	/**
	 * 	Check for binary string
	 *
	 * 	@param	- String
	 * 	@return	- True or False
	 */
	static function isBinary(string $data): bool {
		// https://stackoverflow.com/questions/25343508/detect-if-string-is-binary
		return !preg_match('//u', $data); // the string is binary
	}

	/**
	 * 	Convert picture
	 *
	 * 	@param	- Binary image data
	 * 	@param	- Output format (e.g. PNG)
	 * 	@param	- Optional new width (or 0)
	 * 	@param	- Optional new height (or 0)
	 * 	@return	- Image information array or NULL on error
	 */
	static function cnvImg(string $data, string $ofmt, int $w = 0, int $h = 0) {

	    // empty data?
	    if (!$data)
	        return NULL;

		$t = Util::getTmpFile();
		if (file_put_contents($t, $data) === FALSE) {
		    unlink($t);
			return NULL;
		}

		if (($inf = getimagesize($t)) === FALSE) {
		    unlink($t);
			return NULL;
		}

		$ofmt = strtolower($ofmt);

		// do we support requested output format
		if (!isset(self::PIC_FMT[$ofmt])) {
		    unlink($t);
			return NULL;
		}

		// get function name to convert
		$ofunc = self::PIC_FMT[$ofmt];

		// get conversion functions
		switch($inf[2]) {
		case IMAGETYPE_GIF:
			$ifunc = 'imagecreatefromgif';
			$ifmt  = 'gif';
			break;

		case IMAGETYPE_JPEG:
			$ifunc = 'imagecreatefromjpeg';
			$ifmt  = 'jpeg';
			break;

		case IMAGETYPE_PNG:
			$ifunc = 'imagecreatefrompng';
			$ifmt  = 'png';
			break;

		case IMAGETYPE_WBMP:
			$ifunc = 'imagecreatefromwbmp';
			$ifmt  = 'wbmp';
			break;

		case IMAGETYPE_XBM:
			$ifunc = 'imagecreatefromxbm';
			$ifmt  = 'xbm';
			break;

		case IMAGETYPE_SWF:
		case IMAGETYPE_PSD:
		case IMAGETYPE_BMP:
		case IMAGETYPE_TIFF_II:
		case IMAGETYPE_TIFF_MM:
		case IMAGETYPE_JPC:
		case IMAGETYPE_JP2:
		case IMAGETYPE_JPX:
		case IMAGETYPE_JB2:
		case IMAGETYPE_SWC:
		case IMAGETYPE_IFF:
		default:
		    unlink($t);
			return NULL;
		}

		// new picture size given?
		if ($w && $h && ($inf[0] != $w || $inf[1] != $h)) {
			// get ratio
			$r1 = $w / $inf[0];
			$r2 = $h / $inf[1];
			$r = $r1 < $r2 ? $r1 : $r2;
			// resize image
			$w = ceil($inf[0] * $r);
			$h = ceil($inf[1] * $r);
		} else {
			// same size, same format?
			if ($ofmt == $ifmt) {
				$inf['newdata'] = $data;
				return $inf;
			}

			// we only convert format
			$w = $inf[0];
			$h = $inf[1];
		}

		// create working picture
		$np = imagecreateTRUEcolor($w, $h);

		// convert image to internal format
		if (!($s = @$ifunc($t))) {
		    unlink($t);
			imagedestroy($np);
			return NULL;
		}

		// resample image
		@imagecopyresampled($np, $s, 0, 0, 0, 0, $w, $h, $inf[0], $inf[1]);
		// convert image to new format
		@$ofunc($np, $t);

		// get new image data
		$inf = @getimagesize($t);

		// cleanup memory
		imagedestroy($np);

		// load new picture
		$inf['newdata'] = @file_get_contents($t);
   		unlink($t);

		// save input information
		$inf['old_format'] 	= $ifmt; //3
		$inf['old_with'] 	= $w; //3
		$inf['old_height']	= $h; //3

		Debug::Msg($inf, 'Converting picture'); //3

		return $inf;
	}

	/**
	 * 	Create unique hash value
	 *
	 * 	@param 	- String to hash
	 * 	@return - Hash value
	 */
	static function Hash(string $str): string {
	    return hash('adler32', $str);
	}

	/**
	 * 	Returns a GUIDv4 string
	 *
	 * @param 	- Encode Windows like in brackets
	 * @return 	- New GUID
	 */
	static function WinGUID(bool $trim = TRUE): string {

		// Windows
    	if (function_exists('com_create_guid') === true) {
    	    if ($trim === true)
        	    return trim(com_create_guid(), '{}');
        	else
        	    return com_create_guid();
    	}

    	// OSX/Linux
    	if (function_exists('openssl_random_pseudo_bytes') === true) {
    	    $data = openssl_random_pseudo_bytes(16);
    	    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
    	    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
    	    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    	}

	    // Fallback (PHP 4.2+)
	    mt_srand((double)microtime() * 10000);
	    $charid = strtolower(md5(uniqid(rand(), true)));
	    $hyphen = chr(45);                  // "-"
	    $lbrace = $trim ? "" : chr(123);    // "{"
	    $rbrace = $trim ? "" : chr(125);    // "}"
	    $guidv4 = $lbrace.
	              substr($charid,  0,  8).$hyphen.
	              substr($charid,  8,  4).$hyphen.
	              substr($charid, 12,  4).$hyphen.
	              substr($charid, 16,  4).$hyphen.
	              substr($charid, 20, 12).
	              $rbrace;


		return $guidv4;
	}

}

?>
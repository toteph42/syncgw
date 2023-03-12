<?php
declare(strict_types=1);

/*
 *  Recurrence date field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\Util;
use syncgw\lib\XML;

class fldRecurrenceDate extends fldHandler {

	// module version number
	const VER = 9;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'RecurrenceDate';

	/*
	 rdate      = "RDATE" rdtparam ":" rdtval *("," rdtval) CRLF

     rdtparam   = *(

                ; the following are optional,
                ; but MUST NOT occur more than once

                (";" "VALUE" "=" ("DATE-TIME" / "DATE" / "PERIOD")) /
                (";" tzidparam) /

                ; the following is optional,
                ; and MAY occur more than once

                (";" xparam)

                )

     rdtval     = date-time / date / period
     ;Value MUST match value type
	 */
	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'date-time'			=> [
		  'VALUE'			=> [ 1, 'date-time ' ],
		  'TZID'			=> [ 0 ],
		  '[ANY]'			=> [ 0 ],
		],
		'date'			 	=> [
		  'VALUE'			=> [ 1, 'date ' ],
		  'TZID'			=> [ 0 ],
		  '[ANY]'			=> [ 0 ],
		],
		'period'		   	=> [
		  'VALUE'			=> [ 1, 'period ' ],
		  'TZID'			=> [ 0 ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldRecurrenceDate
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldRecurrenceDate {
		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			unset(parent::$Deleted[self::TAG]);
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
		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), self::TAG));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Import field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
	 *	@param  - External path
	 *  @param  - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or external document
	 *  @param  - Internal path
	 * 	@param 	- Internal document
	 *  @return - TRUE = Ok; FALSE = Skipped
	 */
	public function import(string $typ, float $ver, string $xpath, $ext, string $ipath, XML &$int): bool {
		$rc    = FALSE;
		$ipath .= self::TAG;

		switch ($typ) {
		case 'text/calendar':
		case 'text/x-vcalendar':
			// get time zone
			$p = $int->savePos();
			if (!($tzid = $int->getVar(fldTimezone::TAG)))
				$tzid = 'UTC';
			$int->restorePos($p);

			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// time zone set?
				if (isset($rec['P']['TZID']))
					if ($tzid = Util::getTZName($rec['P']['TZID'])) {
						$int->updVar(fldTimezone::TAG, $tzid);
						unset($rec['P']['TZID']);
					} else
						// delete unknown time zone
						unset($rec['P']['TZID']);
				foreach (explode($ver == 1.0 ? ';' : ',', $rec['D']) as $val) {
					// check type
					$var = 'text';
					if (strpos($val, '/') !== FALSE)
						$var = 'period';
					$p = date_parse($val);
					if (!$p['warning_count'] && !$p['error_count']) {
						if ($p['year'] !== FALSE && $p['hour'] !== FALSE)
							$var = 'date-time';
						elseif ($p['year'] !== FALSE)
							$var = 'date';
						elseif ($p['hour'] !== FALSE)
							$var = 'timestamp';
						elseif (isset($p['zone']) || isset($p['tz_id']))
							$var = 'utc-offset';
						else
							$var = 'date-and-or-time';
					}
					if (strpos('date-time date period', $var) === FALSE) {
						Debug::Msg('['.$rec['D'].'] "'.$rec['D'].'" wrong type "'.$var.'" - dropping record'); //3
						$val = '';
						break;
					}
					// check parameter
					parent::check($rec, self::RFCC_PARM[$var]);
					parent::delTag($int, $ipath);
					if ($var == 'period') {
						list ($s, $e) = explode('/', $val);
						$s   = Util::unxTime($s, $tzid);
						$e   = Util::unxTime($e, $tzid);
						$val = $s.'/'.$e;
					} else
						$val = Util::unxTime($val, $tzid);
					if ($var != 'date-time')
						$rec['P']['VALUE'] = $var;
					$int->addVar(self::TAG, $val, FALSE, $rec['P']);
				}
				$rc = TRUE;
			}
			break;

		default:
			break;
		}

		return $rc;
	}

	/**
	 * 	Export field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
 	 *	@param  - Internal path
	 * 	@param 	- Internal document
	 *  @param  - External path
	 *  @param  - External document
	 *  @return - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or FALSE=Not found
	 */
	public function export(string $typ, float $ver, string $ipath, XML &$int, string $xpath, ?XML $ext = NULL) {
		$rc   = FALSE;
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/calendar':
		case 'text/x-vcalendar':
			$p = $int->savePos();
			if (!($tzid = $int->getVar(fldTimezone::TAG)))
				$tzid = 'UTC';
			$int->restorePos($p);

			$d= new \DateTime('', new \DateTimeZone($tzid));
	   		$v = '';
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				if ($ver != 1.0) {
					if ($tzid && $tzid != 'UTC')
	   					$a['TZID'] = $tzid;
  					if (isset($a['VALUE']))
   						$a['VALUE'] = strtoupper($a['VALUE']);
				} else
					unset($a['VALUE']);
				if (isset($a['VALUE']) && $a['VALUE'] == 'period') {
   					list ($s, $e) = explode('/', $val);
	   				$d->setTimestamp(intval($s));
   					$val = $d->format($tzid != 'UTC' ? Util::STD_TIME : Util::UTC_TIME);
	   				$d->setTimestamp(intval($e));
   					$val .= '/'.$d->format($tzid != 'UTC' ? Util::STD_TIME : Util::UTC_TIME);
   				} else {
	   				$d->setTimestamp(intval($val));
	   				$val = $d->format($tzid != 'UTC' ? Util::STD_TIME : Util::UTC_TIME);
   				}
   				$v .= ($v ? ($ver == 1.0 ? ';' : ',') : '').$val;
			}
			if ($v) {
				unset($a['VALUE']);
		   		$rc = [[ 'T' => $tag, 'P' => $a, 'D' => $v ]];
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
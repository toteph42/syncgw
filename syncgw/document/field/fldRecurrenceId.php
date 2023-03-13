<?php
declare(strict_types=1);

/*
 *  DateRecurrenceId field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\Util;
use syncgw\lib\XML;

class fldRecurrenceId extends fldHandler {

	// module version number
	const VER = 7;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'RecurrenceId';

	/*
	 recurid    = "RECURRENCE-ID" ridparam ":" ridval CRLF

     ridparam   = *(

                ; the following are optional,
                ; but MUST NOT occur more than once

                (";" "VALUE" "=" ("DATE-TIME" / "DATE)) /
                (";" tzidparam) / (";" rangeparam) /
                ; the following is optional,
                ; and MAY occur more than once

                (";" xparam)

                )

     ridval     = date-time / date
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
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldRecurrenceId
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldRecurrenceId {
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
				 $var = 'text';
				$p = date_parse($rec['D']);
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
				if (strpos('date-time date', $var) === FALSE) {
					Debug::Msg('['.$rec['D'].'] "'.$rec['D'].'" wrong type "'.$var.'" - dropping record'); //3
					continue;
				}
				// check parameter
				parent::check($rec, self::RFCC_PARM[$var]);
				parent::delTag($int, $ipath);
				// time zone set?
				if (isset($rec['P']['TZID']))
					if ($tzid = Util::getTZName($rec['P']['TZID'])) {
						$int->updVar(fldTimezone::TAG, $tzid);
						unset($rec['P']['TZID']);
					} else
						// delete unknown time zone
						unset($rec['P']['TZID']);
				$rec['P']['VALUE'] = $var;
				$int->addVar(self::TAG, Util::unxTime($rec['D'], $tzid), FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'application/activesync.mail+xml':
			if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath);

			while (($val = $ext->getItem()) !== NULL) {
				$int->addVar(self::TAG, Util::unxTime($val));
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
			$d = new \DateTime('', new \DateTimeZone($tzid));
			$int->restorePos($p);

			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				if ($ver != 1.0 && $tzid && $tzid != 'UTC')
   				   $a['TZID'] = $tzid;
				$d->setTimestamp(intval($val));
	   			$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => $d->format($a['VALUE'] == 'date' ? Util::STD_DATE : Util::UTC_TIME) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.mail+xml':
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, gmdate(Util::masTIME, intval($val)), FALSE, $ext->setCP(XML::AS_MAIL));
				$rc	= TRUE;
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
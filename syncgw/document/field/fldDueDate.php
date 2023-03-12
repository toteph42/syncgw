<?php
declare(strict_types=1);

/*
 *  Due date field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\Debug; //3
use syncgw\lib\Util;
use syncgw\lib\XML;

class fldDueDate extends fldHandler {

	// module version number
	const VER = 10;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'DueDate';

	/*
	 due        = "DUE" dueparam":" dueval CRLF

     dueparam   = *(
                ; the following are optional,
                ; but MUST NOT occur more than once

                (";" "VALUE" "=" ("DATE-TIME" / "DATE")) /
                (";" tzidparam) /

                ; the following is optional,
                ; and MAY occur more than once
                 *(";" xparam)

                )
     dueval     = date-time / date
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
     * 	@var fldDueDate
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldDueDate {

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
				// check type
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
				unset($rec['P']['VALUE']);
				$int->addVar(self::TAG, Util::unxTime($rec['D'], $tzid), FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'application/activesync.mail+xml':
		case 'application/activesync.task+xml':
			if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath);
			while (($val = $ext->getItem()) !== NULL) {
				if ($val) {
					if ($int->getVar(self::TAG) && $xpath == 'UtcDueDate')
						$int->updVar(self::TAG, Util::unxTime($val));
					else
						$int->addVar(self::TAG, Util::unxTime($val));
					$rc = TRUE;
				}
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

			$recs = [];
	   		while (($val = $int->getItem()) !== NULL) {
		   		$d = new \DateTime('', new \DateTimeZone($tzid));
				$d->setTimestamp(intval($val));
	   			$a = $int->getAttr();
	   			if ($ver != 1.0 && $tzid && $tzid != 'UTC')
   				   $a['TZID'] = $tzid;
	   			$recs[] = [ 'T' => $tag, 'P' => $a, 'D' =>
	   					  $d->format(isset($a['VALUE']) && $a['VALUE'] == 'date' ? Util::STD_DATE : Util::UTC_TIME) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.mail+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 12.0)
				break;

		case 'application/activesync.task+xml':
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, gmdate(Util::masTIME, intval($val)), FALSE,$ext->setCP(XML::AS_TASK));
				$rc = TRUE;
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
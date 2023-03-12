<?php
declare(strict_types=1);

/*
 *  Summary text field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldSummary extends fldHandler {

	// module version number
	const VER = 6;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Summary';

	/*
	   summary    = "SUMMARY" summparam ":" text CRLF

       summparam  = *(
                  ;
                  ; The following are OPTIONAL,
                  ; but MUST NOT occur more than once.
                  ;
                  (";" altrepparam) / (";" languageparam) /
                  ;
                  ; The following is OPTIONAL,
                  ; and MAY occur more than once.
                  ;
                  (";" other-param)
                  ;
                  )
	 */
	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'ALTREP'		   	=> [ 5 ],
		  'LANGUAGE'		=> [ 6 ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldSummary
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldSummary {

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
		$mver  = '';

		switch ($typ) {
		case 'text/x-vnote':
		case 'text/plain':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				parent::delTag($int, $ipath);
   				$int->addVar(self::TAG, $rec['D'], FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// check parameter
				parent::check($rec, self::RFCC_PARM['text']);
				parent::delTag($int, $ipath);
				$int->addVar(self::TAG, parent::rfc5545($rec['D']), FALSE, $rec['P']);
				$rc = TRUE;
	  		}
			break;

		case 'application/activesync.calendar+xml':
		case 'application/activesync.task+xml':
			$mver = '16.0';

		case 'application/activesync.note+xml':
		case 'application/activesync.mail+xml':
			if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath, $mver);
			while (($val = $ext->getItem()) !== NULL) {
				$int->addVar(self::TAG, $val);
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
		$cp   = NULL;
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);
		$mas  = masHandler::getInstance();

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/x-vnote':
		case 'text/plain':
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => $val ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => parent::rfc5545($val, FALSE) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.note+xml':
			if ($mas->callParm('BinVer') < 14.0)
				break;

			$cp = XML::AS_NOTE;

		case 'application/activesync.calendar+xml':
			if (!$cp)
				$cp = XML::AS_CALENDAR;

		case 'application/activesync.task+xml':
			if (!$cp)
				$cp = XML::AS_TASK;

		case 'application/activesync.mail+xml':
			if (!$cp)
				$cp = XML::AS_MAIL;

			if (!$cp) {
				if (isset($tags[0]) && $tags[0] == fldFlag::TAG && $mas->callParm('BinVer') < 12.0)
					break;

				$cp = XML::AS_MAIL;
			}

			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, $val, FALSE, $ext->setCP($cp));
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
<?php
declare(strict_types=1);

/*
 *  Refresh field handler
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

class fldRefreshInterval extends fldHandler {

	// module version number
	const VER = 3;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 					= 'RefreshInterval';

	/*
	   refresh      = "REFRESH-INTERVAL" refreshparam
                    ":" dur-value CRLF
                    ;consisting of a positive duration of time.

       refreshparam = *(
                   ;
                   ; The following is REQUIRED,
                   ; but MUST NOT occur more than once.
                   ;
                   (";" "VALUE" "=" "DURATION") /
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
	    'duration'			=> [
		  'VALUE'			=> [ 1, 'duration ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldRefreshInterval
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldRefreshInterval {
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
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// check type
				$var = 'text';
				if (($val = Util::cnvDuration(TRUE, $rec['D'])) !== NULL)
					$var = 'duration';
				if ($var != 'duration') {
					Debug::Msg('['.$rec['D'].'] "'.$rec['D'].'" wrong type "'.$var.'" - dropping record'); //3
					continue;
				}
				// check parameter
				parent::check($rec, self::RFCC_PARM[$var]);
				parent::delTag($int, $ipath);
				$rec['P']['VALUE'] = $var;
				$int->addVar(self::TAG, $val, FALSE, $rec['P']);
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
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				if ($ver == 1.0)
					unset($a['VALUE']);
				else
					$a['VALUE'] = strtoupper($a['VALUE']);
	   			$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => Util::cnvDuration(FALSE, $val) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
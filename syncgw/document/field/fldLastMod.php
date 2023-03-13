<?php
declare(strict_types=1);

/*
 *  Last modified field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\Util;
use syncgw\lib\XML;

class fldLastMod extends fldHandler {

	// module version number
	const VER = 6;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			= 'LastMod';

	/*
	 last-mod   = "LAST-MODIFIED" lstparam ":" date-time CRLF

     lstparam   = *(";" xparam)
	 */
	const RFCC_PARM	  	= [
		// description see fldHandler:check()
	    'date-time'			=> [
		  'VALUE'			=> [ 1, 'date-time ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldLastMod
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldLastMod {

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
		return FALSE;
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
		$ip   = $int->savePos();
		$val  = gmdate(Util::UTC_TIME, intval($int->getVar(self::TAG)));
		$int->restorePos($ip);

		switch ($typ) {
		case 'text/x-vnote':
		case 'text/calendar':
		case 'text/x-vcalendar':
			$rc = [[ 'T' => $tag, 'P' => [], 'D' => $val ]];
			break;

		case 'application/activesync.note+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 14.0)
				break;
			$ext->addVar($tag, $val, FALSE, $ext->setCP(XML::AS_NOTE));
  		  	$rc = TRUE;
			break;

		case 'application/activesync.docLib+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 12.0)
				break;
			$ext->addVar($tag, $val, FALSE, $ext->setCP(XML::AS_docLib));
  		  	$rc = TRUE;
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
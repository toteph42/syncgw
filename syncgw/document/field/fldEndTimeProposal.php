<?php
declare(strict_types=1);

/*
 *  Proposed end date field handler
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

class fldEndTimeProposal extends fldHandler {

	// module version number
	const VER = 4;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			 = 'EndTimeProposal';

   	/**
     * 	Singleton instance of object
     * 	@var fldEndTimeProposal
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldEndTimeProposal {

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
		case 'application/activesync.mail+xml':
	   		if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath);

			while (($val = $ext->getItem()) !== NULL) {
				if ($val) {
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
		case 'application/activesync.mail+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 16.1)
				break;

			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, gmdate(Util::UTC_TIME, intval($val)), FALSE, $ext->setCP(XML::AS_MRESPONSE));
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
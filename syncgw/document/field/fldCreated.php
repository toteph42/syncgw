<?php
declare(strict_types=1);

/*
 *  Date created field handler
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

class fldCreated extends fldHandler {

	// module version number
	const VER  = 6;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			 = 'Created';

	/*
	 REV-param = "VALUE=timestamp" / any-param
     REV-value = timestamp
	 */

    /**
     * 	Singleton instance of object
     * 	@var fldCreated
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldCreated {

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
		case 'application/activesync.doclib+xml':
			foreach (explode(',', $xpath) as $t) {
				if ($ext->xpath($t, FALSE))
					parent::delTag($int, $ipath);
				while (($val = $ext->getItem()) !== NULL && $val) {
					$int->addVar(self::TAG, Util::unxTime($val));
					$rc = TRUE;
				}
				if ($rc)
					break;
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
		$ip   = $int->savePos();
		$val  = gmdate(Util::UTC_TIME, intval($int->getVar(self::TAG)));
		$int->restorePos($ip);
		$cp   = NULL;

		switch ($typ) {
		case 'text/x-vnote':
		case 'text/vcard':
		case 'text/x-vcard':
			$rc = [[ 'T' => $tag, 'P' => [], 'D' => $val ]];
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			$tags    = explode(',', $xpath);
			$tags[0] = explode('/', $tags[0]);
			$tags[1] = explode('/', $tags[1]);
			$rc = [[ 'T' => strpos($tag, 'VEVENT') && $ver == 1.0 ? array_pop($tags[1]) : array_pop($tags[0]),
					 'P' => [], 'D' => $val ]];
			break;

		case 'application/activesync.doclib+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 12.0)
				break;
			$cp = XML::AS_DocLib;

		case 'application/activesync.mail+xml':
			if (!$cp)
				$cp = XML::AS_MAIL;

			if (!$int->xpath($ipath.self::TAG, FALSE))
				return $rc;
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, gmdate(Util::masTIME, intval($val)), FALSE, $ext->setCP($cp));
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
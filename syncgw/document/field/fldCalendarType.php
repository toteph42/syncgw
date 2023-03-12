<?php
declare(strict_types=1);

/*
 *  Calendar typ field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldCalendarType extends fldHandler {

	// module version number
	const VER = 2;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			 = 'CalendarType';

   /**
     * 	Singleton instance of object
     * 	@var fldCalendarType
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldCalendarType {

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
		case 'application/activesync.calendar+xml':
		case 'application/activesync.task+xml':
			if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath);
		//					 0				Default
		//					 1				Gregorian
		//					 2				Gregorian (United States)
		//					 3				Japanese Emperor Era
		//					 4				Taiwan
		//					 5				Korean Tangun Era
		//					 6				Hijri (Arabic Lunar)
		//					 7				Thai
		//					 8				Hebrew Lunar
		//					 9				Gregorian (Middle East French)
		//					 10			   	Gregorian (Arabic)
		//					 11			   	Gregorian (Transliterated English)
		//					 12			   	Gregorian (Transliterated French)
		//					 14			   	Japanese Lunar
		//					 15			   	Chinese Lunar
		//					 16			   	Saka Era. Reserved. MUST NOT be used.
		//					 17			   	Chinese Lunar (Eto). Reserved. MUST NOT be used.
		//					 18			   	Korean Lunar (Eto). Reserved. MUST NOT be used.
		//					 19			   	Japanese Rokuyou Lunar. Reserved. MUST NOT be used.
		//					 20			   	Korean Lunar
			while (($val = $ext->getItem()) !== NULL) {
				if ($val > -1 || $val < 21) {
					$int->addVar(self::TAG, $val);
					$rc = TRUE;
				}
				else //3
					Debug::Msg('['.$xpath.'] - invalid value "'.$val.'"'); //3
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
		case 'application/activesync.mail+xml':
			$cp = XML::AS_MAIL2;

		case 'application/activesync.task+xml':
			if (!$cp)
				$cp = XML::AS_TASK;

		case 'application/activesync.calendar+xml':
			if (!$cp)
				$cp = XML::AS_CALENDAR;

			if ($mas->callParm('BinVer') < 14.0)
				break;

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
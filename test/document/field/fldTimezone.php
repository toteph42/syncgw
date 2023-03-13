<?php
declare(strict_types=1);

/*
 *  Timezone id field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldTimezone extends \syncgw\document\field\fldTimezone {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldTimezone {

		if (!self::$_obj)
            self::$_obj = new self();

		return self::$_obj;
	}

	/**
	 *  Test this class
	 *
	 *	@param  - MIME type
	 *  @param  - MIME version
	 *  $param  - External path
	 */
	public function testClass(string $typ, float $ver, string $xpath): void {

		$ext = NULL;
		$int = new XML();
		$obj = new fldHandler;

		if ($typ == 'text/x-vcard' || $typ == 'text/vcard') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => '+0800' ]];
			$cmp1 = '<Data><'.self::TAG.'>Australia/Perth</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['DUMMY']);
		}

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$t    = explode(',', $xpath);
			if ($t[count($t)-1])
				$t = $t[count($t)-1];
			else
				$t = $t[count($t)-2];
			$ext  = [[ 'T' => $t, 'P' => [ ], 'D' => 'America/New_York' ]];
			$cmp1 = '<Data><'.self::TAG.'>America/New_York</'.self::TAG.'></Data>';
			if ($ver == 1.0)
				$cmp2 = [[ 'T' => 'TZ', 'P' => [], 'D' => '-0500' ],
						[  'T' => 'DAYLIGHT', 'P' => [], 'D' => 'FALSE' ]];
			else
				$cmp2 = [[ 'T' => 'BEGIN', 'P' => [], 'D' => 'VTIMEZONE' ],
						[  'T' => 'TZID', 'P' => [], 'D' => 'America/New_York' ],
						[  'T' => 'END', 'P' => [], 'D' => 'VTIMEZONE' ]];
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData>'.
					'<Timezone>AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==</Timezone>'.
					'<Timezone>LAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAsAAAABAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAACAAIAAAAAAAAAxP///w==</Timezone>'.
					'<Timezone>xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==</Timezone>'.
					'<Timezone>qP3//0hTVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAADmBwsAAAABAAEAAAAAAAAAHAIAAEhEVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAADmBwMAAAACAAMAAAAAAAAA5P3//w==</Timezone>'.
					'<Timezone>0AIAAE5aU1QAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAADmBwQABgABAAIAAAAAAAAA9Pz//05aRFQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAADmBwkABgAFAAMAAAAAAAAADAMAAA==</Timezone>'.
					'<Timezone>mP7//0NTVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAADlBwsAAAABAAEAAAAAAAAAmP7//0NEVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAADmBwMAAAACAAMAAAAAAAAA1P7//w==</Timezone>'.
					'</ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>Europe/Dublin</'.self::TAG.'>'.
					'<'.self::TAG.'>Asia/Karachi</'.self::TAG.'>'.
					'<'.self::TAG.'>Pacific/Honolulu</'.self::TAG.'>'.
					'<'.self::TAG.'>Pacific/Auckland</'.self::TAG.'>'.
					'<'.self::TAG.'>America/Chicago</'.self::TAG.'>'.
					'</Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data>'.
					'<Timezone xml-ns="activesync:Calendar">PAAAAElTVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADnBwMAAAAFAAIAAAAAAAAAAAAAAEdNVAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADnBwoAAAAFAAEAAAAAAAAAAAAAAA==</Timezone>'.
					'<Timezone>LAEAAFBLVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAADnBwEAAAABAAUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==</Timezone>'.
					'<Timezone>qP3//0hTVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAADnBwEAAAABAA4AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==</Timezone>'.
					'<Timezone>0AIAAE5aU1QAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAADnBwQAAAABAAIAAAAAAAAAAAAAAE5aRFQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAADnBwkAAAAFAAMAAAAAAAAAAAAAAA==</Timezone>'.
					'<Timezone>mP7//0NTVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAADmBwsAAAABAAEAAAAAAAAAAAAAAENEVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'.
					'AAAAAAAAAAAAAAAAAAAAAAAAAADnBwMAAAACAAMAAAAAAAAAAAAAAA==</Timezone>'.
					'</Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
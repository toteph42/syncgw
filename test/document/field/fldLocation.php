<?php
declare(strict_types=1);

/*
 *  Location field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldLocation extends \syncgw\document\field\fldLocation {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldLocation {

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

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'ALTREP' => 'droo' ], 'D' => 'any valze' ]];
			$cmp1 = '<Data><'.self::TAG.'><'.self::SUB_TAG.' ALTREP="droo">any valze</'.
					self::SUB_TAG.'></'.self::TAG.'></Data>';
			$cmp2 = $ext;
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><DisplayName>somewhere</DisplayName><Street>Wanko. 17</Street>'.
					'</'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><DisplayName>somewhere</DisplayName><'.fldAddressOther::TAG.'><Street>'.
					'Wanko. 17</Street></'.fldAddressOther::TAG.'></'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:AirSyncBase"><DisplayName>somewhere</DisplayName><Street>Wanko. 17</Street>'.
					'</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
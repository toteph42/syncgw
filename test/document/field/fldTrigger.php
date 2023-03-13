<?php
declare(strict_types=1);

/*
 *  Trigger date status field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldTrigger extends \syncgw\document\field\fldTrigger {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldTrigger {

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
		$obj = new fldHandler;

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => '3607' ]];
			$cmp1 = '<Data><'.self::TAG.'>3607</'.self::TAG.'></Data>';
			$cmp2 = $ext;
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
					'</Body><'.$xpath.'>11</'.$xpath.'><WebPage>http://www.contoso.com/</WebPage>'.
					'<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>0</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail">0</'.$xpath.'></Data>');
		}

		if ($typ == 'application/activesync.task+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
					'</Body><'.$xpath.'>2019-03-28 12:11:37</'.$xpath.'><WebPage>http://www.contoso.com/</WebPage>'.
					'<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>0</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail">0</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
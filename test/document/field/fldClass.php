<?php
declare(strict_types=1);

/*
 *  Class field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldClass extends \syncgw\document\field\fldClass {

  	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldClass {

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

		if ($typ == 'text/x-vnote' || $typ == 'text/vcard' || $typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'any' => 'value' ], 'D' => 'bad value' ]];
			if ($ext && ($int = $obj->testImport($this, FALSE, $typ, $ver, $xpath, $ext, '')))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);
			$ext = [[ 'T' => $xpath, 'P' => [ 'any' => 'value' ], 'D' => 'PUBLIC' ]];
			$cmp1 = '<Data><'.self::TAG.'>PUBLIC</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['any']);
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
		   	$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '</Body><'.$xpath.'>2</'.$xpath.'><WebPage>http://www.contoso.com/</WebPage>'.
						  '<BusinessAddressCountry>United States of America</BusinessAddressCountry>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'>PRIVATE</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Calendar">2</'.$xpath.'></Data>');
		}

		if ($typ == 'application/activesync.task+xml') {
			$ext = new XML();
		   	$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '</Body><'.$xpath.'>2</'.$xpath.'><WebPage>http://www.contoso.com/</WebPage>'.
						  '<BusinessAddressCountry>United States of America</BusinessAddressCountry>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'>PRIVATE</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Tasks">2</'.$xpath.'></Data>');
		}

		if ($typ == 'application/activesync.mail+xml') {
			$ext = new XML();
		   	$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '</Body><'.$xpath.'>2</'.$xpath.'><WebPage>http://www.contoso.com/</WebPage>'.
						  '<BusinessAddressCountry>United States of America</BusinessAddressCountry>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'>PRIVATE</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail">2</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
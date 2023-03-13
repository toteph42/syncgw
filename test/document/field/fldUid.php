<?php
declare(strict_types=1);

/*
 *  Uid field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldUid extends \syncgw\document\field\fldUid {

  	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldUid {

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

		if ($typ == 'text/x-vnote') {
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'dsjfuewfcnkjewhuweu2783637862377532' ]];
   			$cmp1 = '<Data><'.self::TAG.'>dsjfuewfcnkjewhuweu2783637862377532</'.self::TAG.'></Data>';
   			$cmp2 = $ext;
		}

		if ($typ == 'text/vcard') {
			$ext = [[ 'T' => $xpath, 'P' => [],  'D' => 'dsjfuewfcnkjewhuweu2783637862377532' ]];
   			$cmp1 = '<Data><'.self::TAG.' VALUE="text">dsjfuewfcnkjewhuweu2783637862377532</'.self::TAG.'></Data>';
   			$cmp2 = $ext;
   			$cmp2[0]['P']['VALUE'] = 'text';
		}

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$xpath = explode(',', $xpath);
			$xpath = $xpath[1];
			$ext = [[ 'T' => $xpath, 'P' => [ 'any' => 'value' ], 'D' => 'value123' ]];
	   		$cmp1 = '<Data><'.self::TAG.'>value123</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['any']);
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'>ed3j3j3jbcj388</'.$xpath.'></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'>ed3j3j3jbcj388</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Calendar">ed3j3j3jbcj388</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
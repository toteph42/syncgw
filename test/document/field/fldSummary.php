<?php
declare(strict_types=1);

/*
 *  Summary text field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldSummary extends \syncgw\document\field\fldSummary {

  	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldSummary {

		if (!self::$_obj)
            self::$_obj = new self();

		return self::$_obj;
	}

	/**
	 *  Test this class
	 *
	 *	@param  - MIME type
	 *  $param  - External path
	 */
	public function testClass(string $typ, float $ver, string $xpath): void {

		$ext = NULL;
		$int = new XML();
		$obj = new fldHandler;

		if ($typ == 'text/plain' || $typ == 'text/x-vnote' || $typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'Summary text' ]];
			$cmp1 = '<Data><'.self::TAG.'>Summary text</'.self::TAG.'></Data>';
			$cmp2 = $ext;
		}

		if ($typ == 'application/activesync.note+xml') {
			$ext = new XML();
		 	$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						  '</Body><Categories><Category>Business</Category></Categories><Subject>New subject</Subject>'.
						  '<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>New subject</'.self::TAG.'></Data>';
			$cmp2 = new XML();
		 	$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Notes">New subject</'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
		 	$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						  '</Body><Categories><Category>Business</Category></Categories><Subject>New subject</Subject>'.
						  '<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>New subject</'.self::TAG.'></Data>';
			$cmp2 = new XML();
		 	$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Calendar">New subject</'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.task+xml') {
			$ext = new XML();
		 	$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						  '</Body><Categories><Category>Business</Category></Categories><Subject>New subject</Subject>'.
						  '<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>New subject</'.self::TAG.'></Data>';
			$cmp2 = new XML();
		 	$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Tasks">New subject</'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.mail+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '<'.$xpath.'>Subject of e-mail</'.$xpath.'></ApplicationData></syncgw>');
		 	$cmp1 = '<Data><'.self::TAG.'>Subject of e-mail</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail">Subject of e-mail</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
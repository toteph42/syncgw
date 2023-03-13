<?php
declare(strict_types=1);

/*
 *  MessageClass field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldMessageClass extends \syncgw\document\field\fldMessageClass {

  	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldMessageClass {

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

		if ($typ == 'application/activesync.note+xml') {
			$ext = new XML();
	   		$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						  '</Body><Categories><Category>Business</Category></Categories><Subject>New note</Subject>'.
						  '<'.$xpath.'>IPM.StickyNote.SGW</'.$xpath.'></ApplicationData></syncgw>');
   			$cmp1 = '<Data><'.self::TAG.'>IPM.StickyNote.SGW</'.self::TAG.'></Data>';
   			$cmp2 = new XML();
		 	$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Notes">IPM.StickyNote.SGW</'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.mail+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '<'.$xpath.'>IPM.Voicenotes</'.$xpath.'></ApplicationData></syncgw>');
		 	$cmp1 = '<Data><'.self::TAG.'>IPM.Voicenotes</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail">IPM.Voicenotes</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
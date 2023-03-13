<?php
declare(strict_types=1);

/*
 *  Meeting URL field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldConference extends \syncgw\document\field\fldConference {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldConference {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'VALUE' => 'URI', 'FEATURE' => 'PHONE,MODERATOR',
					'LABEL' => 'Moderator dial-in' ], 'D' => 'tel:+1-412-555-0123,,,654321' ]];
			$cmp1 = '<Data><'.self::TAG.' FEATURE="phone,moderator" LABEL="Moderator dial-in">tel:+1-412-555-0123,,,654321</'.
					self::TAG.'></Data>';
			$cmp2 = $ext;
			$cmp2[0]['P']['FEATURE'] = 'phone,moderator';
			unset($cmp2[0]['P']['VALUE']);
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'>tel:+49 22222</'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>tel:+49 22222</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Calendar">tel:+49 22222</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
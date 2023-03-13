<?php
declare(strict_types=1);

/*
 *  Name field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\document\field\fldPrefix;
use syncgw\lib\XML;

class fldName extends \syncgw\document\field\fldName {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldName {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' =>
					  'NameLast;NameFirst;NameMiddle;NamePrefix;NameSuffix' ]];
	   		$cmp1 = '<Data><'.fldLastName::TAG.'>NameLast</'.fldLastName::TAG.'><'.
	 	   		   fldFirstName::TAG.'>NameFirst</'.fldFirstName::TAG.'><'.
	   			   fldMiddleName::TAG.'>NameMiddle</'.fldMiddleName::TAG.'><'.
	   			   fldPrefix::TAG.'>NamePrefix</'.fldPrefix::TAG.'><'.
	   			   fldSuffix::TAG.'>NameSuffix</'.fldSuffix::TAG.'><'.
	   			   fldFullName::TAG.'>NameFirst NameLast</'.fldFullName::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		unset($cmp2[0]['P']['DUMMY']);
			if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	   		$ext = [[ 'T' => $xpath, 'P' => [ 'ALTID' => '4711' ], 'D' =>
				  'NameLast;NameFirst;NameMiddle;NamePrefix;NameSuffix' ]];
	   		$cmp1 = '<Data><'.fldLastName::TAG.' ALTID="4711">NameLast</'.fldLastName::TAG.'><'.
	 	   		   fldFirstName::TAG.' ALTID="4711">NameFirst</'.fldFirstName::TAG.'><'.
	   			   fldMiddleName::TAG.' ALTID="4711">NameMiddle</'.fldMiddleName::TAG.'><'.
	   			   fldPrefix::TAG.' ALTID="4711">NamePrefix</'.fldPrefix::TAG.'><'.
	   			   fldSuffix::TAG.' ALTID="4711">NameSuffix</'.fldSuffix::TAG.'><'.
	   			   fldFullName::TAG.'>NameFirst NameLast</'.fldFullName::TAG.'></Data>';
	   		$cmp2 = $ext;
		}

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
		   	$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'Company Vacation Days1' ]];
	   		$cmp1 = '<Data><'.fldPrefix::TAG.'>Company Vacation Days1</'.fldPrefix::TAG.'></Data>';
	   		$cmp2 = $ext;
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
<?php
declare(strict_types=1);

/*
 *  Nick name field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldNickName extends \syncgw\document\field\fldNickName {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldNickName {

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
			$tst = explode(',', $xpath);
			if ($ver == 4.0) $tst = $tst[0];
			if ($ver == 3.0) $tst = $tst[1];
			if ($ver == 2.1) $tst = $tst[2];
			$ext = [[ 'T' => $tst, 'P' => [ 'DUMMY' => 'error' ], 'D' => 'nick name' ]];
	   		$cmp1 = '<Data><'.self::TAG.'>nick name</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		unset($cmp2[0]['P']['DUMMY']);
	   		if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
			$ext = [[ 'T' => $tst, 'P' => [ 'TYPE' => 'work' ], 'D' => 'nick name' ]];
	   		$cmp1 = '<Data><'.self::TAG.' TYPE="work">nick name</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
		}

		if ($typ == 'application/activesync.contact+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '<Truncated>1</Truncated></Body><WebPage>http://www.contoso.com/</WebPage>'.
						  '<NickName>Bunny</NickName>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
		 	$cmp1 = '<Data><'.self::TAG.'>Bunny</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Contacts2">Bunny</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
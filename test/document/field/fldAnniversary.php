<?php
declare(strict_types=1);

/*
 *  Anniversary field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldAnniversary extends \syncgw\document\field\fldAnniversary {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAnniversary {

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

		if ($typ == 'text/x-vcard' || $typ == 'text/vcard') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => '20061029' ]];
	   		$cmp1 = '<Data><'.self::TAG.' VALUE="date">1162080000</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		unset($cmp2[0]['P']['DUMMY']);
	   		$cmp2[0]['D']= substr($cmp2[0]['D'], 0, 8);
	   		if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

				$ext = [[ 'T' => $xpath, 'P' => [ 'CALSCALE' => 'gregorian' ], 'D' => '20061029' ]];
	   		$cmp1 = '<Data><'.self::TAG.' CALSCALE="gregorian" VALUE="date">1162080000</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		$cmp2[0]['D']= substr($cmp2[0]['D'], 0, 8);
		}

		if ($typ == 'application/activesync.contact+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '<Truncated>1</Truncated></Body><WebPage>http://www.contoso.com/</WebPage>'.
						  '<'.$xpath.'>2033-09-25T00:00:00Z</'.$xpath.'>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.' VALUE=\'date\'>2011219200</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Contacts">2033-09-25T00:00:00.000Z</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
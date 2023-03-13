<?php
declare(strict_types=1);

/*
 *  Geographical position field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldGeoPosition extends \syncgw\document\field\fldGeoPosition {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldGeoPosition {

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
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'bad value' ]];
			if ($int = $obj->testImport($this, FALSE, $typ, $ver, $xpath, $ext, ''))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);

			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'droo' ], 'D' => 'geo:37.386013,-122.082932' ]];
			$cmp1 = '<Data><'.self::TAG.'><'.self::RFC_SUB[0].'>37.386013</'.self::RFC_SUB[0].'><'.
					self::RFC_SUB[1].'>-122.082932</'.self::RFC_SUB[1].'></'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['DUMMY']);
			if ($ver != 4.0)
				$cmp2[0]['D'] = substr(str_replace(',', ';', $cmp2[0]['D']), 4);
		}

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'droo' ], 'D' => 'geo:37.386013,-122.082932' ]];
			$cmp1 = '<Data><'.self::TAG.'><'.self::RFC_SUB[0].'>37.386013</'.self::RFC_SUB[0].'><'.
					self::RFC_SUB[1].'>-122.082932</'.self::RFC_SUB[1].'></'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['DUMMY']);
			if ($ver == 1.0)
				$cmp2[0]['D'] = str_replace(',', ';', substr($cmp2[0]['D'], 4));
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
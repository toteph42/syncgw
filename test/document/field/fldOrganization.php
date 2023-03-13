<?php
declare(strict_types=1);

/*
 *  Org field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldOrganization extends \syncgw\document\field\fldOrganization {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldOrganization {

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
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'Organization;Company;Department;Location' ]];
			$cmp = '<Data><'.self::TAG.'>Organization<'.
					fldCompany::TAG.'>Company</'.fldCompany::TAG.'><'.
					fldDepartment::TAG.'>Department</'.fldDepartment::TAG.'><'.
					fldOffice::TAG.'>Location</'.fldOffice::TAG.'></'.self::TAG.'></Data>';
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'Organ' ]];
			$cmp = '<Data><'.self::TAG.'>Organ</'.self::TAG.'></Data>';
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);
	}

}

?>
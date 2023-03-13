<?php
declare(strict_types=1);

/*
 *  Recurrence date field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

class fldRecurrenceDate extends \syncgw\document\field\fldRecurrenceDate {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldRecurrenceDate {

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

		if (($typ == 'text/calendar' || $typ == 'text/x-vcalendar') && $ver == 2.0) {
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ],
					'D' => '19960402T010000Z,19960403T010000Z,19960404T010000Z' ]];
			$cmp1 = '<Data><'.self::TAG.'>828406800</'.self::TAG.'><'.self::TAG.'>828493200</'.
					self::TAG.'><'.self::TAG.'>828579600</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['DUMMY']);
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'CALSCALE' => 'gregorian' ],
					'D' => '19960402T010000Z,19960403T010000Z,19960404T010000Z' ]];
			$cmp1 = '<Data><'.self::TAG.'>828406800</'.self::TAG.'><'.self::TAG.'>828493200</'.
					self::TAG.'><'.self::TAG.'>828579600</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['CALSCALE']);
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
<?php
declare(strict_types=1);

/*
 *  Organizer field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldOrganizer extends \syncgw\document\field\fldOrganizer {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldOrganizer {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'CN' => 'John Smith' ], 'D' => 'MAILTO:joecool@example.com' ]];
			$cmp1 = '<Data><'.self::TAG.' CN="John Smith">mailto:joecool@example.com</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			if ($typ == 'text/calendar') $cmp2[0]['D'] = 'mailto:joecool@example.com';
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><OrganizerEmail>chris@fourthcoffee.com</OrganizerEmail>'.
					'<OrganizerName>Chris Gray</OrganizerName></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.' CN="Chris Gray">mailto:chris@fourthcoffee.com</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$tags = explode(',', $xpath);
			$cmp2->loadXML('<Data><'.$tags[0].' xml-ns="activesync:Calendar">Chris Gray</'.$tags[0].'><'.
					$tags[1].'>chris@fourthcoffee.com</'.$tags[1].'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
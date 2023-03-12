<?php
declare(strict_types=1);

/*
 *  Attendee name field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldAttendee extends \syncgw\document\field\fldAttendee {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAttendee {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'MEMBER' => '"mailto:DEV-GROUP@example.com"' ],
						'D' => 'mailto:joecool@example.com' ]];
			$cmp1 = '<Data><'.self::TAG.' MEMBER="&quot;mailto:DEV-GROUP@example.com&quot;">mailto:joecool@example.com</'.
					self::TAG.'></Data>';
			$cmp2 = $ext;
			if ($typ == 'text/x-vcalendar')
				$cmp2[0]['D'] = 'MAILTO:joecool@example.com';
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><Attendee><Email>chris@fourthcoffee.com</Email><Name>Chris Gray</Name>'.
						'<AttendeeStatus>0</AttendeeStatus><AttendeeType>1</AttendeeType></Attendee></'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.' RSVP="FALSE" CN="Chris Gray" CUTYPE="INDIVIDUAL" ROLE="REQ-PARTICIPANT">'.
					'mailto:chris@fourthcoffee.com</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Calendar"><Attendee><AttendeeStatus>0</AttendeeStatus><AttendeeType>1</AttendeeType>'.
						'<Name>Chris Gray</Name><Email>chris@fourthcoffee.com</Email></Attendee></'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
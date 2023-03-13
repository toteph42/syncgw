<?php
declare(strict_types=1);

/*
 *  Alarm field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldAlarm extends \syncgw\document\field\fldAlarm {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAlarm {

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
	    	if (strpos($xpath, 'VTODO')) $id = 'VTODO';
	    	else $id = 'VEVENT';
	    	if ($ver == 1.0) {
            	$ext = [ [ 'T' => 'VCALENDAR/'.$id.'/DALARM',
            			'P' => [], 'D' => '19960415T235000;PT5M;2;Your Taxes Are Due !!!' ] ];
		   		$cmp1 = '<Data><'.self::TAG.'>'.
				   		'<'.self::SUB_TAG['VCALENDAR/%s/VALARM/ACTION'].'>DISPLAY</'.self::SUB_TAG['VCALENDAR/%s/VALARM/ACTION'].'>'.
		   				'<'.fldTrigger::TAG.' VALUE="date-time">829612200</'.fldTrigger::TAG.'>'.
		   				'<'.fldDuration::TAG.'>300</'.fldDuration::TAG.'>'.
		   				'<'.fldRepeat::TAG.'>2</'.fldRepeat::TAG.'>'.
		   				'<'.fldBody::TAG.' X-TYP="1">Your Taxes Are Due !!!</'.fldBody::TAG.'>'.
		   				'</'.self::TAG.'><'.fldStartTime::TAG.'>829612200</'.fldStartTime::TAG.'></Data>';
	       		$cmp2 = $ext;
	    	} else {
                $ext = [ [ 'T' => 'VCALENDAR/'.$id.'/VALARM/BEGIN',       'P' => [], 'D' => 'VALARM' ],
                         [ 'T' => 'VCALENDAR/'.$id.'/VALARM/ACTION',      'P' => [], 'D' => 'DISPLAY' ],
                         [ 'T' => 'VCALENDAR/'.$id.'/VALARM/REPEAT',      'P' => [], 'D' => '2' ],
                         [ 'T' => 'VCALENDAR/'.$id.'/VALARM/TRIGGER',     'P' => [], 'D' => '-PT30M' ],
                         [ 'T' => 'VCALENDAR/'.$id.'/VALARM/DESCRIPTION', 'P' => [], 'D' => 'Breakfast meeting with executive\nteam at 8:30 AM EST.' ],
                         [ 'T' => 'VCALENDAR/'.$id.'/VALARM/DURATION',    'P' => [], 'D' => 'PT15M' ],
                         [ 'T' => 'VCALENDAR/'.$id.'/VALARM/END',         'P' => [], 'D' => 'VALARM' ],
		        ];
		   		$cmp1 = '<Data><'.self::TAG.'>'.
				   		'<'.self::SUB_TAG['VCALENDAR/%s/VALARM/ACTION'].'>DISPLAY</'.self::SUB_TAG['VCALENDAR/%s/VALARM/ACTION'].'>'.
		   				'<'.fldRepeat::TAG.'>2</'.fldRepeat::TAG.'>'.
		   				'<'.fldTrigger::TAG.' RELATED="start" VALUE="duration">-1800</'.fldTrigger::TAG.'>'.
		   				'<'.fldBody::TAG.' X-TYP="1">Breakfast meeting with executive'."\n".
						'team at 8:30 AM EST.</'.fldBody::TAG.'>'.
		   				'<'.fldDuration::TAG.'>900</'.fldDuration::TAG.'>'.
		   				'</'.self::TAG.'></Data>';
		   		$cmp2 = $ext;
		   		foreach ($cmp2 as $k => $unused) {
	   				$a = explode('/', $cmp2[$k]['T']);
	   				$cmp2[$k]['T'] = isset($a[3]) ? $a[3] : $a[2];
		   		}
		   		$cmp2[3]['P']['RELATED'] = 'START';
	   			$cmp2[3]['P']['VALUE'] = 'DURATION';
		   		$unused;
	    	}
	    }

        if ($typ == 'application/activesync.calendar+xml') {
            $ext = new XML();
        	$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'>10</'.$xpath.'></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'><Action>DISPLAY</Action><'.fldTrigger::TAG.' VALUE="duration" RELATED="start">-600</'.
	 	   			fldTrigger::TAG.'></'.self::TAG.'></Data>';
        	$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Calendar">10</'.$xpath.'></Data>');
        }

        if ($typ == 'application/activesync.task+xml') {
        	$ext = new XML();
            $ext->loadXML('<syncgw><ApplicationData><'.$xpath.'>19960415T235000'.
            			  '</'.$xpath.'></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'><Action>DISPLAY</Action><'.fldTrigger::TAG.' VALUE="date-time">829612200</'.
	 	   			fldTrigger::TAG.'></'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Tasks">1996-04-15T23:50:00.000Z</'.$xpath.'><ReminderSet>1</ReminderSet></Data>');
        }

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
  	}

}

?>
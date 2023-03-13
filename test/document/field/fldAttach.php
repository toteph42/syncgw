<?php
declare(strict_types=1);

/*
 *  Attachment field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldAttach extends \syncgw\document\field\fldAttach {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAttach {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'FMTTYPE' => 'audio/basic', 'VALUE' => 'URI' ],
					'D' => 'http://github.com/sound.wav' ]];
			$cmp1 = '<Data><'.self::TAG.' X-TYP="audio/basic"><'.self::SUB_TAG[2].
					'>http://github.com/sound.wav</'.self::SUB_TAG[2].
					'></'.self::TAG.'></Data>';
			$cmp2 = $ext;
			$cmp2[0]['P']['VALUE'] = 'URI';
			if ($int = $obj->testImport($this,TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this,$typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'ENCODING' => 'BASE64', 'VALUE' => 'BINARY' ],
					'D' => '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSop' ]];
			$cmp1 = '<Data><'.self::TAG.'><'.self::SUB_TAG[1].'>sgw-61730bd3</'.self::SUB_TAG[1].
					'></'.self::TAG.'></Data>';
			$cmp2 = $ext;
			$cmp2[0]['P']['ENCODING'] = 'BASE64';	   		// new parameter
	   		$cmp2[0]['P']['FMTTYPE'] = 'image/jpeg';
		}

		if ($typ == 'application/activesync.calendar+xml' || $typ == 'application/activesync.mail+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><Add>'.
						  '<DisplayName>Sound file</DisplayName>'.
						  '<Method>1</Method><ContentId>4711</ContentId><Content>YnViYmEgaXMgZGVhdGgh</Content>'.
						  '</Add></'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><ContentId>4711</ContentId><DisplayName>Sound file</DisplayName><Method>1</Method>'.
					'<FileReference>sgw-2b7b0540</FileReference></'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:AirSyncBase"><Attachment>'.
						'<ContentId>4711</ContentId><DisplayName>Sound file</DisplayName><FileReference>sgw-2b7b0540</FileReference>'.
						'<Method>1</Method></Attachment></'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
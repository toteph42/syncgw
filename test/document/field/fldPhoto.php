<?php
declare(strict_types=1);

/*
 *  Photo field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldPhoto extends \syncgw\document\field\fldPhoto {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldPhoto {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => 'http://xxx.com/pic.jpg' ]];
	   		$cmp1 = '<Data><'.self::TAG.' VALUE="uri">http://xxx.com/pic.jpg</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		unset($cmp2[0]['P']['DUMMY']);
	   		$cmp2[0]['P']['VALUE'] = $ver == 2.1 ? 'URL' : 'uri';
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'home' ], 'D' => 'http://xxx.com/pic.jpg' ]];
	   		$cmp1 = '<Data><'.self::TAG.' TYPE="home" VALUE="uri">http://xxx.com/pic.jpg</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		$cmp2[0]['P']['VALUE'] = $ver == 2.1 ? 'URL' : 'uri';
	   		if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	   		$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'JPEG', 'ENCODING' => 'BASE64' ],
					  'D' => '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSop' ]];
			$cmp1 = '<Data><'.self::TAG.'>sgw-61730bd3</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		if ($ver == 4.0) {
	   			$cmp2[0]['D'] = 'data:image/jpeg;base64,'.$cmp2[0]['D'];
	   			unset($cmp2[0]['P']['TYPE']);
	   			unset($cmp2[0]['P']['ENCODING']);
	   		} elseif ($ver == 3.0)
	   			$cmp2[0]['P']['ENCODING'] = 'b';
	   		else
	   			$cmp2[0]['P']['ENCODING'] = 'base64';
		}

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
		   $ext = [[ 'T' => $xpath, 'P' => [ 'VALUE' => 'URI', 'DISPLAY' => 'BADGE', 'FMTTYPE' => 'image/png' ],
					 'D' => 'http://example.com/images/party.png' ]];
	   		$cmp1 = '<Data><'.self::TAG.' VALUE="uri" DISPLAY="badge" FMTTYPE="image/png">http://example.com/images/party.png</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		$cmp2[0]['P']['VALUE'] = 'uri';
	   		$cmp2[0]['P']['DISPLAY'] = 'badge';
		}

		if ($typ == 'application/activesync.gal+xml') {
			$ext = new XML();
	   		$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'>/9j/4AAQSkZJRgABAQEAYABgAAD/...</'.$xpath.'></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'>sgw-5d8e06a8</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="'.XML::CP[XML::AS_GAL].'"><Status>1</Status><Data>/9j/4AAQSkZJRgABAQEAYABgAAD/</Data></'.$xpath.'></Data>');
		}

		if ($typ == 'application/activesync.contact+xml') {
			$ext = new XML();
	   		$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '<Truncated>1</Truncated></Body><WebPage>http://www.contoso.com/</WebPage>'.
						  '<TitleJob>Development Manager</TitleJob><'.$xpath.'>/9j/4AAQSkZJRgABAQEAYABgAAD/...</'.$xpath.'>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
	   		$cmp1 = '<Data><'.self::TAG.'>sgw-5d8e06a8</'.self::TAG.'></Data>';
	   		$cmp2 = new XML();
	   		$cmp2->loadXML('<Data><'.$xpath.' xml-ns="'.XML::CP[XML::AS_CONTACT].'">/9j/4AAQSkZJRgABAQEAYABgAAD/</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
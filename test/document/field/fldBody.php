<?php
declare(strict_types=1);

/*
 *  Body field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldBody extends \syncgw\document\field\fldBody {

  	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldBody {

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

		if ($typ == 'text/x-vnote') {
   			$ext = [[ 'T' => $xpath, 'P' => [ 'any' => 'value', 'TYPE' => 'HOME' ], 'D' => 'This is a text' ]];
   			$cmp1 = '<Data><'.self::TAG.' TYPE="home" X-TYP="'.self::TYP_TXT.'">This is a text</'.self::TAG.'></Data>';
   			$cmp2 = $ext;
   			unset($cmp2[0]['P']['any']);
		}

		if ($typ == 'text/plain') {
   			$ext = [[ 'T' => $xpath, 'P' => [ 'any' => 'value', 'TYPE' => 'HOME' ], 'D' => 'This is a text' ]];
   			$cmp1 = '<Data><'.self::TAG.' any="value" TYPE="home" X-TYP="'.self::TYP_TXT.'">This is a text</'.self::TAG.'></Data>';
   			$cmp2 = $ext;
		}

		if ($typ == 'text/vcard' || $typ == 'text/x-vcard' ||
			$typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
   			$ext = [[ 'T' => $xpath, 'P' => [ 'any' => 'value', 'TYPE' => 'HOME' ], 'D' => 'This is a text' ]];
	   		if ($typ != 'text/calendar' && $typ != 'text/x-vcalendar')
	   			$cmp1 = '<Data><'.self::TAG.' TYPE="home" X-TYP="'.self::TYP_TXT.'">This is a text</'.self::TAG.'></Data>';
   			else
   				$cmp1 = '<Data><'.self::TAG.' X-TYP="'.self::TYP_TXT.'">This is a text</'.self::TAG.'></Data>';
   			$cmp2 = $ext;
	   		unset($cmp2[0]['P']['any']);
	   		if ($typ != 'text/calendar' && $typ != 'text/x-vcalendar') $cmp2[0]['P']['TYPE'] = 'home';
	   		else unset($cmp2[0]['P']['TYPE']);
		}

		if ($typ == 'application/activesync.note+xml' || $typ == 'application/activesync.contact+xml' ||
			$typ == 'application/activesync.calendar+xml' || $typ == 'application/activesync.task+xml' ||
			$typ == 'application/activesync.mail+xml') {
   			// load <Options>
			$ext = new XML();
			list(,$cl) = explode('.', $typ);
			list($cl,) = explode('+', $cl);
			$cl = strtoupper(substr($cl, 0, 1)).substr($cl, 1);
			$ext->loadXML('<Sync><Collection><Options><Class>'.$cl.'</Class><BodyPreference xml-ns="activesync:Calendar"><Type>1</Type>'.
						  '<TruncationSize>51200</TruncationSize></BodyPreference></Options></Collection></Sync>');
   			$mas = masHandler::getInstance();
   			$mas->loadOptions('Sync', $ext);
			$txt = 'This is a text';
   			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><Type>1</Type><EstimatedDataSize>'.strlen($txt).'</EstimatedDataSize><Data>'.$txt.'</Data>'.
						  '</'.$xpath.'><WebPage>http://www.contoso.com/</WebPage>'.
						  '<BusinessAddressCountry>United States of America</BusinessAddressCountry>'.
						  '<AssistantName>Development Manager</AssistantName><Picture>/9j/4AAQSkZJRgABAQEAYABgAAD/...</Picture>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
			$cmp2 = new XML();
   			$cmp1 = '<Data><'.self::TAG.' X-TYP="'.self::TYP_TXT.'">'.$txt.'</'.self::TAG.'></Data>';
   			$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:AirSyncBase"><Type>1</Type>'.
     					   '<EstimatedDataSize>14</EstimatedDataSize><Data>'.$txt.'</Data>'.
     					   '</'.$xpath.'></Data></syncgw>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
<?php
declare(strict_types=1);

/*
 *  Categories text field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldCategories extends \syncgw\document\field\fldCategories {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldCategories {

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
			$ext = [[ 'T' => substr($xpath, 0, -1), 'P' => [ 'any' => 'value' ], 'D' => 'cat1,cat2,cat3' ]];
			$cmp1 = '<Data><'.self::TAG.' any="value">cat1</'.self::TAG.'><'.self::TAG.'>cat2</'.self::TAG.'><'.
					self::TAG.'>cat3</'.self::TAG.'></Data>';
			$cmp2 = $ext;
		}

		if ($typ == 'text/vcard' || $typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$tst = explode(',', $xpath);
			$ext = [[ 'T' => $tst[0], 'P' => [ 'any' => 'value' ], 'D' => 'cat1,cat2,cat3' ]];
			$cmp1 = '<Data><'.self::TAG.'>cat1</'.self::TAG.'><'.self::TAG.'>cat2</'.self::TAG.'><'.
					self::TAG.'>cat3</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['any']);
			if ($ver == 1.0)
				$cmp2[0]['T'] = 'X-EPOCAGENDAENTRYTYPE';
		}

		if ($typ == 'application/activesync.note+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
					'</Body><Categories><Category>Business</Category></Categories><Subject>New note</Subject>'.
					'<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>Business</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Notes"><'.self::TAG.'>Business</'.
					self::TAG.'></'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.contact+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						'</Body><Categories><Category>Business</Category></Categories><Subject>New note</Subject>'.
						'<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>Business</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Contacts"><'.self::TAG.'>Business</'.
						self::TAG.'></'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						'</Body><Categories><Category>Business</Category></Categories><Subject>New note</Subject>'.
						'<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>Business</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Calendar"><'.self::TAG.'>Business</'.
						self::TAG.'></'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.task+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						'</Body><Categories><Category>Business</Category></Categories><Subject>New note</Subject>'.
						'<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>Business</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Tasks"><'.self::TAG.'>Business</'.
						self::TAG.'></'.$xpath.'></Data></syncgw>');
		}

		if ($typ == 'application/activesync.mail+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>2</Type><Data>A new note I just created.</Data>'.
						'</Body><Categories><Category>Business</Category></Categories><Subject>New note</Subject>'.
						'<MessageClass>IPM.StickyNote</MessageClass></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>Business</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<syncgw><Data><'.$xpath.' xml-ns="activesync:Mail"><'.self::TAG.'>Business</'.
						self::TAG.'></'.$xpath.'></Data></syncgw>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>
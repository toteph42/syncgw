<?php
declare(strict_types=1);

/*
 *  Class field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldClass extends fldHandler {

	// module version number
	const VER = 4;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Class';
	// Content value	   	PUBLIC			- Note is public
	//					 	PRIVATE		  	- Note is private
	//					 	CONFIDENTIAL	- Note is confidential

	/*
	 class      = "CLASS" classparam ":" classvalue CRLF

     classparam = *(";" xparam)
     classvalue = "PUBLIC" / "PRIVATE" / "CONFIDENTIAL" / iana-token
                / x-name
     ;Default is PUBLIC
	 */
	const RFC_PARM		 	= [
		// description see fldHandler:check()
	    'text' 				=> [
		  'VALUE'			=> [ 1, 'text ' ],
    	  '[ANY]'			=> [ 0 ],
		],
	];

	const AS_MAP		   	= [
		'0'					=> 'PUBLIC',
		'1'					=> 'PERSONAL',
		'2'					=> 'PRIVATE',
		'3'					=> 'CONFIDENTIAL',
	];

   /**
     * 	Singleton instance of object
     * 	@var fldClass
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldClass {

		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			unset(parent::$Deleted[self::TAG]);
		}

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), self::TAG));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Import field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
	 *	@param  - External path
	 *  @param  - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or external document
	 *  @param  - Internal path
	 * 	@param 	- Internal document
	 *  @return - TRUE = Ok; FALSE = Skipped
	 */
	public function import(string $typ, float $ver, string $xpath, $ext, string $ipath, XML &$int): bool {

		$rc    = FALSE;
		$ipath .= self::TAG;

		switch ($typ) {
		case 'text/x-vnote':
		case 'text/vcard':
		case 'text/x-vcard':
		case 'text/calendar':
		case 'text/x-vcalendar':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// check value
				if (strpos(' PUBLIC PRIVATE CONFIDENTIAL', ' '.$rec['D']) !== FALSE) {
					parent::check($rec, self::RFC_PARM['text']);
					parent::delTag($int, $ipath);
					unset($rec['P']['VALUE']);
					$int->addVar(self::TAG, $rec['D'], FALSE, $rec['P']);
					$rc = TRUE;
				}
				else //3
					Debug::Msg('['.$xpath.'] invalid value "'.$rec['D'].'" - dropping record'); //3
			}
			break;

		case 'application/activesync.mail+xml':
		case 'application/activesync.calendar+xml':
		case 'application/activesync.task+xml':
			if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath, $typ == 'application/activesync.calendar+xml' ? '16.0' : '');
			while (($val = $ext->getItem()) !== NULL) {
				if (!strlen($val))
					continue;
				if (isset(self::AS_MAP[$val])) {
					$int->addVar(self::TAG, self::AS_MAP[$val]);
					$rc = TRUE;
				}
				else //3
					Debug::Msg('['.$xpath.'] invalid value "'.$val.'" - dropping record'); //3
			}
			break;

		default:
			break;
		}

		return $rc;
	}

	/**
	 * 	Export field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
 	 *	@param  - Internal path
	 * 	@param 	- Internal document
	 *  @param  - External path
	 *  @param  - External document
	 *  @return - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or FALSE=Not found
	 */
	public function export(string $typ, float $ver, string $ipath, XML &$int, string $xpath, ?XML $ext = NULL) {

		$rc   = FALSE;
		$cp   = NULL;
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
		case 'text/x-vnote':
		case 'text/calendar':
		case 'text/x-vcalendar':
			$rec = [];
			while (($val = $int->getItem()) !== NULL) {
				if (Debug::$Conf['Script'] != 'MIME01') //3
   					if (($typ == 'text/vcard' || $typ == 'text/x-vcard') && $ver == 4.0) {
	   					Debug::Msg('['.$xpath.'] not supported in "'.$typ.'" "'.($ver ? sprintf('%.1F', $ver) : 'n/a').'"'); //3
   						break;
					}
				$a = $int->getAttr();
				// $a['VALUE'] = 'TEXT';
				$rec[] = [ 'T' => $tag, 'P' => $a, 'D' => $val ];
			}
			if (count($rec))
				$rc = $rec;
			break;

		case 'application/activesync.mail+xml':
			$cp = XML::AS_MAIL;

		case 'application/activesync.calendar+xml':
			if (!$cp)
				$cp = XML::AS_CALENDAR;

		case 'application/activesync.task+xml':
			if (!$cp)
				$cp = XML::AS_TASK;

			$map = array_flip(self::AS_MAP);
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, strval($map[$val]), FALSE, $ext->setCP($cp));
				$rc = TRUE;
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
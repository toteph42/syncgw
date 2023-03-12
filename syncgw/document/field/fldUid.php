<?php
declare(strict_types=1);

/*
 *  Uid field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\activesync\masHandler;
use syncgw\lib\Config; //3
use syncgw\lib\XML;

class fldUid extends fldHandler {

	// module version number
	const VER = 9;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'UID';

	/*
	 UID-param = UID-uri-param / UID-text-param
     UID-value = UID-uri-value / UID-text-value
       ; Value and parameter MUST match.

     UID-uri-param = "VALUE=uri"
     UID-uri-value = URI

     UID-text-param = "VALUE=text"
     UID-text-value = text

     UID-param =/ any-param
	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'uri'			  	=> [
		  'VALUE'			=> [ 1, 'uri ' ],
		],
		'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		],
	];

	/*
	uid        = "UID" uidparam ":" text CRLF

    uidparam   = *(";" other-param)
	 */
	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldUid
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldUid {

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
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				parent::delTag($int, $ipath);
				$int->addVar(self::TAG, $rec['D'], FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'text/vcard':
		case 'text/x-vcard':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// defaults to type text
				$var = 'text';
				$p = parse_url($rec['D']);
				if (isset($p['scheme']))
					$var = 'uri';
				// check parameter
				parent::check($rec, self::RFCA_PARM[$var]);
				parent::delTag($int, $ipath);
				$rec['P']['VALUE'] = $var;
				$int->addVar(self::TAG, $var == 'text' ? parent::rfc6350($rec['D']) : $rec['D'], FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			foreach ($ext as $rec) {
				if (strpos($xpath, $rec['T']) === FALSE)
					continue;
				// check parameter
				parent::check($rec, self::RFCC_PARM['text']);
				parent::delTag($int, $ipath);
				unset($rec['P']['VALUE']);
				$int->addVar(self::TAG, parent::rfc5545($rec['D']), FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'application/activesync.mail+xml':
		case 'application/activesync.calendar+xml':
	   		if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath, $typ == 'application/activesync.calendar+xml' ? '16.0' : '');
			while (($val = $ext->getItem()) !== NULL) {
				if (strlen($val)) {
					$int->addVar(self::TAG, $val);
					$rc = TRUE;
				}
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
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		$int->xpath($ipath.self::TAG, FALSE);

		switch ($typ) {
		case 'text/x-vnote':
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => $val ];
			}
			if (count($recs))
				$rc = $recs;
			else {
				Debug::Msg(' ['.self::TAG.'] adding missing value'); //3
				$cnf = Config::getInstance(); //3
				if (Debug::$Conf['Script'] || $cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) //3
					$int->addVar(self::TAG, $val = '5c9264179f3d9'); //3
				else //3
					$int->addVar(self::TAG, $val = uniqid());
				$rc = [[ 'T' => $tag, 'P' => [], 'D' => $val ]];
			}
			break;

		case 'text/vcard':
		case 'text/x-vcard':
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => (isset($a['VALUE']) && $a['VALUE'] == 'text') ?
				            parent::rfc6350($val, FALSE) : $val ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			$tags = explode(',', $xpath);
			$tags = $tags[$ver == 1.0 && isset($tags[1]) ? 1 : 0];
			$tags = explode('/', $tags);
			$tag  = array_pop($tags);
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => parent::rfc5545($val, FALSE) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.mail+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 12.0)
				break;

		case 'application/activesync.calendar+xml':
			if (isset($tags[0]) && $tags[0] == fldExceptions::TAG) {
				$mas = masHandler::getInstance();
				if ($mas->callParm('BinVer') > 2.5)
					break;
			}

			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, $val, FALSE, $ext->setCP(XML::AS_CALENDAR));
				$rc	= TRUE;
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
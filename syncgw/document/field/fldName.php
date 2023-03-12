<?php
declare(strict_types=1);

/*
 *  Name field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Util;
use syncgw\lib\XML;

class fldName extends fldHandler {

	// module version number
	const VER = 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG	 	  		= [
								fldLastName::TAG,
								fldFirstName::TAG,
								fldMiddleName::TAG,
	                            fldPrefix::TAG,
								fldSuffix::TAG,
	];

	// Parameter		   	P0  - Family Names (also known as surnames or last name)
	//					 	P1  - Given Names (also known as first name)
	//					 	P2  - Additional Names
	//					 	P3  - Honorific Prefixes
	//					 	P4  - Honorific Suffixes
	/*
	 N-param = "VALUE=text" / sort-as-param / language-param
             / altid-param / any-param
     N-value = list-component 4(";" list-component)
	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  'SORT-AS'		  	=> [ 0 ],
		  'LANGUAGE'		=> [ 6 ],
		  'ALTID'			=> [ 0 ],
		  '[ANY]'			=> [ 0 ],
		],
	];

	/*
	 name       = "NAME" nameparam ":" text CRLF

     nameparam  = *(
                 ;
                 ; The following are OPTIONAL,
                 ; but MUST NOT occur more than once.
                 ;
                 (";" altrepparam) / (";" languageparam) /
                 ;
                 ; The following is OPTIONAL,
                 ; and MAY occur more than once.
                 ;
                 (";" other-param)
                 ;
                 )
	 */
	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  'ALTREP'		   	=> [ 0 ],
		  'LANGUAGE'		=> [ 6 ],
		  '[ANY]'			=> [ 0 ],
		]
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldName
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldName {

		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			foreach (self::TAG as $tag)
			    parent::$Deleted[$tag] = 0;
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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), 'Names'));
		$xml->addVar('Ver', strval(self::VER));

		foreach ([ fldLastName::TAG	=> fldLastName::VER,
				   fldFirstName::TAG	=> fldFirstName::VER,
				   fldMiddleName::TAG	=> fldMiddleName::VER,
	               fldPrefix::TAG		=> fldPrefix::VER,
				   fldSuffix::TAG		=> fldSuffix::VER] as $tag => $ver) {
			$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), $tag));
			$xml->addVar('Ver', strval($ver));
		}
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

		$rc = FALSE;

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// check parameter
				parent::check($rec, self::RFCA_PARM['text']);
				foreach (self::TAG as $t) {
            		// clear tag deletion status
				     if (isset(parent::$Deleted[$t]) && !parent::$Deleted[$t])
				        unset(parent::$Deleted[$t]);
				    parent::delTag($int, $t);
				}
				if ($p = Util::unfoldStr(str_replace("\;", "\#", $rec['D']), ';', 5)) {
					unset($rec['P']['VALUE']);
				    for ($i=0; $i < 5; $i++) {
						if (!$p[$i])
						    continue;
						$p[$i] = parent::rfc6350(str_replace('\#', ';', $p[$i]));
                        $int->addVar(self::TAG[$i], $p[$i], FALSE, $rec['P']);
				    }
				}
				// feed in full name
				$ip = $int->savePos();
				if (!$int->xpath('//Data/'.fldFullName::TAG.'/.')) {
					$int->restorePos($ip);
					$c = fldFullName::getInstance();
					$c->import($typ, $ver, 'FN', [[ 'T' => 'FN', 'P' => [], 'D' => $p[1].' '.$p[0] ]], $ipath, $int);
				}
				$int->restorePos($ip);
				$rc = TRUE;
			 }
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// check parameter
				parent::check($rec, self::RFCC_PARM['text']);
           		// clear tag deletion status
				if (isset(parent::$Deleted[fldPrefix::TAG]) && !parent::$Deleted[fldPrefix::TAG])
				    unset(parent::$Deleted[fldPrefix::TAG]);
				parent::delTag($int, $ipath.fldPrefix::TAG);
				unset($rec['P']['VALUE']);
				$int->addVar(fldPrefix::TAG, parent::rfc5545($rec['D']), FALSE, $rec['P']);
				$rc = TRUE;
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

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
		    $i   = 0;
			$val = [];
			$a   = [];
		    foreach (self::TAG as $t) {
				$ip = $int->savePos();
				$int->xpath($ipath.$t, FALSE);
				if (($v = $int->getItem()) !== NULL)
					$val[$i] = parent::rfc6350($v, FALSE);
				else
				    $val[$i] = '';
				if ($v = $int->getAttr())
				    $a += $v;
				$i++;
				$int->restorePos($ip);
			}
			$rc = [[ 'T' => $tag, 'P' => $a, 'D' => Util::foldStr($val, ';') ]];
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			$recs = [];
	       	if (!$int->xpath($ipath.fldPrefix::TAG, FALSE))
                return $rc;
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				// $a['VALUE'] = 'TEXT';
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => parent::rfc5545($val, FALSE) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>
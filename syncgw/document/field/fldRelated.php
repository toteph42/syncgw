<?php
declare(strict_types=1);

/*
 *  Related field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldRelated extends fldHandler {

	// module version number
	const VER = 4;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG         		= 'Related';
	const SUB_TAG		  	= [
								fldAssistant::TAG	    => [ 1, [ 'TYPE' => 'co-worker' ]],		// Name of the contact’s assistant
						   		fldChild::TAG			=> [ 1, [ 'TYPE' => 'child'	 	]],		// Child of the contact
						   		fldManagerName::TAG	=> [ 1, [ 'TYPE' => 'x-manager' ]],		// Contact's manager name
						   		fldSpouse::TAG		=> [ 1, [ 'TYPE' => 'spouse'	]],		// Name of the contact’s spouse/partner
						   		self::TAG   		    => [ 1, [					   	]],
	];

	/*
	 RELATED-param = RELATED-param-uri / RELATED-param-text
     RELATED-value = URI / text
       ; Parameter and value MUST match.

     RELATED-param-uri = "VALUE=uri" / mediatype-param
     RELATED-param-text = "VALUE=text" / language-param

     RELATED-param =/ pid-param / pref-param / altid-param / type-param
                    / any-param

     type-param-related = related-type-value *("," related-type-value)
       ; type-param-related MUST NOT be used with a property other than
       ; RELATED.

     related-type-value = "contact" / "acquaintance" / "friend" / "met"
                        / "co-worker" / "colleague" / "co-resident"
                        / "neighbor" / "child" / "parent"
                        / "sibling" / "spouse" / "kin" / "muse"
                        / "crush" / "date" / "sweetheart" / "me"
                        / "agent" / "emergency"
	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'uri'			  	=> [
		  'VALUE'			=> [ 1, 'uri ' ],
		  'MEDIATYPE'		=> [ 7 ],
		  'PID'			  	=> [ 0 ],
		  'PREF'			=> [ 2, '1-100' ],
		  'ALTID'			=> [ 0 ],
		  'TYPE'			=> [ 1, ' work home contact acquaintance friend  met co-worker colleague co-resident neighbor'.
						   			' child parent sibling spouse kin muse crush date sweetheart me agent emergency'.
						   			' x-other x-manager x- ' ],
		  '[ANY]'			=> [ 0 ],
		],
		'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  'LANGUAGE'		=> [ 6 ],
		  'PID'			  	=> [ 0 ],
		  'PREF'			=> [ 2, '1-100' ],
		  'ALTID'			=> [ 0 ],
		  'TYPE'			=> [ 1, ' work home contact acquaintance friend  met co-worker colleague co-resident neighbor'.
						   			' child parent sibling spouse kin muse crush date sweetheart me agent emergency x-manager x- ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

	/*
	 related    = "RELATED-TO" [relparam] ":" text CRLF

     relparam   = *(

                ; the following is optional,
                ; but MUST NOT occur more than once

                (";" reltypeparam) /

                ; the following is optional,
                ; and MAY occur more than once

                (";" xparm)

                )
	 */
   	const RFCC_PARM			= [
		// description see fldHandler:check()
   	    'text'			    => [
		  'VALUE'		    => [ 1, 'text ' ],
		  'RELTYPE'		  	=> [ 1, ' PARENT CHILD SIBLING X- ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldRelated
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldRelated {
		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			foreach (self::SUB_TAG as $tag => $unused)
			    parent::$Deleted[$tag] = 0;
			$unused; // Disable Ecclipse warning
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

		foreach ([ fldAssistant::TAG	    => fldAssistant::VER,
				   fldChild::TAG			=> fldChild::VER,
				   fldManagerName::TAG	=> fldManagerName::VER,
				   fldSpouse::TAG			=> fldSpouse::VER ] as $tag => $ver) {
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
				// defaults to type text
				$var = 'text';
				$p = parse_url($rec['D']);
				if (isset($p['scheme']))
					$var = 'uri';
				// check parameter
				parent::check($rec, self::RFCA_PARM[$var]);
				if ($t = parent::match($rec, self::SUB_TAG)) {
            		// clear tag deletion status
				    if (isset(parent::$Deleted[$t]) && !parent::$Deleted[$t])
				        unset(parent::$Deleted[$t]);
				    parent::delTag($int, $t);
					if ($var == 'text')
					    unset($rec['P']['VALUE']);
					else
					    $rec['P']['VALUE'] = $var;
					$int->addVar($t, $var == 'text' ? parent::rfc6350($rec['D']) : $rec['D'], FALSE, $rec['P']);
					$rc = TRUE;
				}
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
				if (isset(parent::$Deleted[self::TAG]) && !parent::$Deleted[self::TAG])
				    unset(parent::$Deleted[self::TAG]);
				parent::delTag($int, $ipath.self::TAG);
				unset($rec['P']['VALUE']);
				$int->addVar(self::TAG, parent::rfc5545($rec['D']), FALSE, $rec['P']);
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
			$recs = [];
			foreach (self::SUB_TAG as $t => $parm) {
				$ip = $int->savePos();
				$int->xPath($ipath.$t, FALSE);
				while (($val = $int->getItem()) !== NULL) {
					if (Debug::$Conf['Script'] != 'MIME01') //3
						if ($ver != 4.0) {
							Debug::Msg('['.$xpath.'] not supported in "'.$typ.'" "'.($ver ? sprintf('%.1F', $ver) : 'n/a').'"'); //3
							break;
						}
					$a = $int->getAttr();
					if (isset($parm[1]['TYPE']))
						$a['TYPE'] = isset($a['TYPE']) ? $a['TYPE'].','.$parm[1]['TYPE'] : $parm[1]['TYPE'];
					$recs[]	   = [ 'T' => $tag, 'P' => $a, 'D' => $a['TYPE'] == 'text' ? parent::rfc6350($val, FALSE) : $val ];
				}
				$int->restorePos($ip);
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
    		if (!$int->xpath($ipath.self::TAG, FALSE))
	       		return $rc;
			$recs = [];
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
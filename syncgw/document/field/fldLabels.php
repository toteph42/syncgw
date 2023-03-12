<?php
declare(strict_types=1);

/*
 *  Label field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldLabels extends fldHandler {

	// module version number
	const VER = 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG			  	= [
						   		'LabelHome'		=> [ 1, [ 'TYPE' => 'home'   ]],
							   	'LabelWork' 	=> [ 1, [ 'TYPE' => 'work'   ]],
							   	'LabelOther'	=> [ 0, [					 ]],
 	];

	// Parameter (v3.0)		TYPE=dom		- Domestic address
	//					 	TYPE=intl		- International address
	//					 	TYPE=postal	  	- Postal address
	//					 	TYPE=parcel	  	- Parcel address
	// Parameter (v2.1)		DOM			  	- Domestic address
	//					 	INTL			- International address
	//					 	POSTAL		   	- Postal address
	//					 	PARCEL		   	- Parcel address
	//					 	HOME			- Home address
	//					 	WORK			- Business address

	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldLabels
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldLabels {
		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			foreach (self::TAG as $tag => $unused)
			    parent::$Deleted[$tag] = 0;
			$unused; // disable Eclipse warning
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
		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), 'Labels'));
		$xml->addVar('Ver', strval(self::VER));

		foreach (self::TAG as $tag => $unused) {
			$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), $tag));
			$xml->addVar('Ver', strval(self::VER));
		}
		$unused; // disable Eclipse warning
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
				if ($ver != 4.0 && isset($rec['P']['TYPE'])) {
					$t   			  = '';
					$rec['P']['TYPE'] = strtolower($rec['P']['TYPE']);
					foreach (explode(',', $rec['P']['TYPE']) as $v) {
						// change all "unknown" types
						if (strpos(' home work ', $v.' ') === FALSE && substr($v, 0, 2) != 'x-') {
							$v  = 'x-'.$v;
							$t .= ($t ? ',' : '').$v;
						} else
							$t .= ($t ? ',' : '').$v;
					}
					$rec['P']['TYPE'] = $t;
				}
				// check parameter
				// parent::check($rec, self::RFCA_PARM['text']);
				if ($t = parent::match($rec, self::TAG)) {
            		// clear tag deletion status
				     if (isset(parent::$Deleted[$t]) && !parent::$Deleted[$t])
				        unset(parent::$Deleted[$t]);
				    parent::delTag($int, $t);
					unset($rec['P']['VALUE']);
					$int->addVar($t, parent::rfc6350($rec['D']), FALSE, $rec['P']);
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

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
			$recs = [];
			foreach (self::TAG as $t => $parm) {
				$ip = $int->savePos();
				$int->xpath($ipath.$t, FALSE);
				while (($val = $int->getItem()) !== NULL) {
					if (Debug::$Conf['Script'] != 'MIME01') //3
   						if ($ver != 3.0) {
	   						Debug::Msg('['.$xpath.'] not supported in "'.$typ.'" "'.($ver ? sprintf('%.1F', $ver) : 'n/a').'"'); //3
   							break;
						}
					$a = $int->getAttr();
					if ($parm[0] == 1)
						$a['TYPE'] = isset($a['TYPE']) ? $a['TYPE'].','.$parm[1]['TYPE'] : $parm[1]['TYPE'];
					if ($ver != 4.0) {
						if (isset($a['TYPE']))
							$a['TYPE'] = str_replace('x-', '', $a['TYPE']);
						if (isset($a['PREF'])) {
							$a['TYPE'] .= ',pref';
							unset($a['PREF']);
						}
		   			}
		   			// $a['VALUE'] = 'text';
		   			if (strpos($val, "\n") !== FALSE)
		   				$a['ENCODING'] = 'QUOTED-PRINTABLE';
					$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => parent::rfc6350($val, FALSE) ];
				}
				$int->restorePos($ip);
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
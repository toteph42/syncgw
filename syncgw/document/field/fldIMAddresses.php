<?php
declare(strict_types=1);

/*
 *  IM field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldIMAddresses extends fldHandler {

	// module version number
	const VER = 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG		  		= [
									fldIMskype::TAG,
									fldIMicq::TAG,
									fldIMjabber::TAG,
									fldIMaim::TAG,
									fldIMmsn::TAG,
									fldIMyahoo::TAG,
	];

	/*
	 IMPP-param = "VALUE=uri" / pid-param / pref-param / type-param
                / mediatype-param / altid-param / any-param
     IMPP-value = URI
	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'uri'			  	=> [
		  'VALUE'			=> [ 1, 'uri ' ],
		  'PID'			  	=> [ 0 ],
		  'PREF'			=> [ 2, '1-100' ],
		  'TYPE'			=> [ 1, ' work home x- ' ],
		  'MEDIATYPE'		=> [ 7 ],
		  'ALTID'			=> [ 0 ],
		  '[ANY]'			=> [ 0 ],
		]
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldIMAddresses
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldIMAddresses {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), 'IMAdresses'));
		$xml->addVar('Ver', strval(self::VER));

		foreach ([ fldIMskype::TAG	=> fldIMskype::VER,
				   fldIMicq::TAG		=> fldIMicq::VER,
				   fldIMjabber::TAG	=> fldIMjabber::VER,
				   fldIMaim::TAG		=> fldIMaim::VER,
				   fldIMmsn::TAG		=> fldIMmsn::VER,
				   fldIMyahoo::TAG	=> fldIMyahoo::VER] as $tag => $ver) {
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
				if ($var != 'uri') {
					Debug::Msg('['.$rec['T'].'] wrong data type "text" for "'.$rec['D'].'" - should be "uri" - dropping record'); //3
					continue;
				}
				// check parameter
				parent::check($rec, self::RFCA_PARM[$var]);
				$p['scheme'] = strtolower($p['scheme']);
	 			if (strpos('skype icq jabber aim msn yahoo', $p['scheme']) === FALSE) {
					Debug::Msg('['.$rec['T'].'] unsupported scheme "'.$p['scheme'].'" - dropping record'); //3
					break;
				}
				foreach (self::TAG as $tag) {
					if (stripos($tag, $p['scheme']) !== FALSE)
						break;
				}
            	// clear tag deletion status
				 if (isset(parent::$Deleted[$tag]) && !parent::$Deleted[$tag])
					unset(parent::$Deleted[$tag]);
				parent::delTag($int, $tag);
	    		unset($rec['P']['VALUE']);
		      	$int->addVar($tag, $rec['D'], FALSE, $rec['P']);
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
			foreach (self::TAG as $t) {
				$ip = $int->savePos();
				$int->xPath($ipath.$t, FALSE);
				while (($val = $int->getItem()) !== NULL) {
					if (Debug::$Conf['Script'] != 'MIME01') //3
						if ($ver != 4.0) {
							Debug::Msg('['.$xpath.'] not supported in "'.$typ.'" "'.($ver ? sprintf('%.1F', $ver) : 'n/a').'"'); //3
							break;
						}
					$a = $int->getAttr();
					// $a['VALUE'] = 'uri';
					$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => $val ];
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
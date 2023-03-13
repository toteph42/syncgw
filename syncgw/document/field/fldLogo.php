<?php
declare(strict_types=1);

/*
 *  BinLogo field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\Attachment;
use syncgw\lib\Log;
use syncgw\lib\Util;
use syncgw\lib\XML;

class fldLogo extends fldHandler {

	// module version number
	const VER 	= 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Logo';

	/*
	 LOGO-param = "VALUE=uri" / language-param / pid-param / pref-param
                / type-param / mediatype-param / altid-param / any-param
     LOGO-value = URI
	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'uri'			  	=> [
		  'VALUE'			=> [ 1, 'uri ' ],
		  'LANGUAGE'		=> [ 6 ],
		  'PID'			  	=> [ 0 ],
		  'PREF'			=> [ 2, '1-100' ],
		  'TYPE'			=> [ 1, ' work home x- ' ],
		  'MEDIATYPE'		=> [ 7 ],
		  'ALTID'			=> [ 0 ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldLogo
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldLogo {
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
		$att   = Attachment::getInstance();
		$ipath .= self::TAG;

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath || !strlen($rec['D']))
					continue;
				$p = parse_url($rec['D']);
				if ($ver == 4.0) {
					$var = 'text';
					if (isset($p['scheme']))
						$var = 'uri';
					if ($var != 'uri') {
						Debug::Msg('['.$rec['T'].'] wrong data type "text" for "'.$rec['D'].'" - should be "uri" - dropping record'); //3
						continue;
					}
					// check parameter
					parent::check($rec, self::RFCA_PARM[$var]);
					parent::delTag($int, $ipath);
					// save as uri
					$val = $att->create($rec['D'], 'text/html');
				} else {
					if (isset($p['scheme']) && $p['scheme'] != 'base64') {
						// check parameter
						parent::check($rec, self::RFCA_PARM['uri']);
						parent::delTag($int, $ipath);
						$val = $att->create($rec['D'], 'text/html');
					} else {
						// check parameter
						parent::check($rec, self::RFCA_PARM['text']);
						parent::delTag($int, $ipath);
						$rec['D'] = base64_decode($rec['D']);
						$val = $att->create($rec['D'], isset($rec['P']['TYPE']) && $rec['P']['TYPE'] == 'JPEG' ? 'image/jpeg' : '');
						unset($rec['P']['TYPE']);
					}
				}
				// clear some parameter
				unset($rec['P']['VALUE']);
				unset($rec['P']['DATA']);
				unset($rec['P']['ENCODING']);
				$int->addVar(self::TAG, $val, FALSE, $rec['P']);
				$rc = TRUE;
				break;
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
		$att  = Attachment::getInstance();
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
			$recs = [];
			// we assume $rec['D'] is attachment record id
			while (($val = $int->getItem()) !== NULL) {
				// check if attachment could be loaded
				if (!($data = $att->read($val)))
					continue;
				$rec = [ 'T' => $tag, 'P' => $int->getAttr(), 'D' => $data ];
				if ($att->getVar('MIME') == 'text/html')
					$rec['P']['VALUE'] = $ver == 2.1 ? 'URL' : 'uri';
				else {
					// not supported!
					if ($ver == 4.0)
						continue;
					if (strpos($att->getVar('MIME'), 'jpeg') !== FALSE) {
   				  		if (!($t = Util::cnvImg($rec['D'], 'JPEG'))) {
							$log = Log::getInstance();
				    		$log->setMsg([ 13101 => _('Error converting logo [%s] to \'JPEG\'') ]);
							$log->Msg(Log::WARN, 13101, $val);
							continue;
   				  		}
						$rec['D'] = $t['newdata'];
		   			}
		   			$rec['D'] 			  = base64_encode($rec['D']);
					$rec['P']['TYPE']	  = 'JPEG';
  					$rec['P']['ENCODING'] = $ver == 2.1 ? 'base64' : 'b';
  					// $rec['P']['VALUE'] = 'text';
				}
				$recs[] = $rec;
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
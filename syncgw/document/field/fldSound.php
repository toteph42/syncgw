<?php
declare(strict_types=1);

/*
 *  BinSound field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldSound extends fldHandler {

	// module version number
	const VER = 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Sound';

	/*
     SOUND-param = "VALUE=uri" / language-param / pid-param / pref-param
                 / type-param / mediatype-param / altid-param
                 / any-param
     SOUND-value = URI
	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'uri' => [
		  'VALUE'			=> [ 1, 'uri ' ],
		  'LANGUAGE'		=> [ 0 ],
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
     * 	@var fldSound
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldSound {
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
		case 'text/vcard':
		case 'text/x-vcard':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath || !strlen($rec['D']))
					continue;
				$var = 'text';
				$p   = parse_url($rec['D']);
				if (isset($p['scheme']))
					$var = 'uri';
				if ($var != 'uri') {
					Debug::Msg('['.$rec['T'].'] wrong data type "text" for "'.$rec['D'].'" - should be "uri" - dropping record'); //3
					continue;
				}
				// check parameter
				parent::check($rec, self::RFCA_PARM[$var]);
				parent::delTag($int, $ipath);
				$rec['P']['VALUE'] = $var;
				$int->addVar(self::TAG, $rec['D'], FALSE, $rec['P']);
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
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
			$recs = [];
			while (($val = $int->getItem()) !== NULL)
				$recs[] = [ 'T' => $tag, 'P' => $int->getAttr(), 'D' => $val ];
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
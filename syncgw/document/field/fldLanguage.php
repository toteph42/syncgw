<?php
declare(strict_types=1);

/*
 *  Language field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\XML;

class fldLanguage extends fldHandler {

	// module version number
	const VER = 4;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Language';

	/*
	 LANG-param = "VALUE=language-tag" / pid-param / pref-param
                / altid-param / type-param / any-param
     LANG-value = Language-Tag

	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  'PID'			  	=> [ 0 ],
		  'PREF'			=> [ 2, '1-100' ],
		  'ALTID'			=> [ 0 ],
		  'TYPE'			=> [ 1, ' work home x- ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldLanguage
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldLanguage {
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
				if ($rec['T'] != $xpath)
					continue;
				// check parameter
				parent::check($rec, self::RFCA_PARM['text']);
				parent::delTag($int, $ipath);
				unset($rec['P']['VALUE']);
				$int->addVar(self::TAG, $rec['D'], FALSE, $rec['P']);
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

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
			if ($ver != 4.0)
				break;
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				// $a['VALUE'] = 'text';
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => $val ];
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
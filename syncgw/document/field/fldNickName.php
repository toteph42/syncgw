<?php
declare(strict_types=1);

/*
 *  Nick name field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\XML;

class fldNickName extends fldHandler {

	// module version number
	const VER = 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'NickName';

	/*
	 NICKNAME-param = "VALUE=text" / type-param / language-param
                    / altid-param / pid-param / pref-param / any-param
     NICKNAME-value = text-list
    */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  'TYPE'			=> [ 1, ' work home x- ' ],
		  'LANGUAGE'		=> [ 0 ],
		  'ALTID'			=> [ 0 ],
		  'PID'			  	=> [ 0 ],
		  'PREF'			=> [ 2, '1-100' ],
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldNickName
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldNickName {

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
				if (strpos($xpath, $rec['T'].',') === FALSE)
					continue;
				// check parameter
				parent::check($rec, self::RFCA_PARM['text']);
				parent::delTag($int, $ipath);
				$int->addVar(self::TAG, parent::rfc6350($rec['D']), FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'application/activesync.contact+xml':
	   		if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath, '2.5');
			while (($val = $ext->getItem()) !== NULL) {
				if ($val) {
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

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/vcard':
		case 'text/x-vcard':
			$recs = [];
			$tag  = explode(',', $xpath);
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				$tag[0] = explode('/', $tag[0]);
				$rec = [ 'T' => array_pop($tag[0]), 'P' => $a, 'D' => parent::rfc6350($val, FALSE) ];
				if ($ver <> 4.0) {
					$t = explode('/', $tag[$ver == 3.0 ? 1 : 2]);
					$rec['T'] = array_pop($t);
				}
				$recs[] = $rec;
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.contact+xml':
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, $val, FALSE, $ext->setCP(XML::AS_CONTACT2));
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
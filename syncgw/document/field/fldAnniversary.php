<?php
declare(strict_types=1);

/*
 *  Anniversary field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Util;
use syncgw\lib\XML;

class fldAnniversary extends fldHandler {

	// module version number
	const VER = 6;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Anniversary';

	/*
	 ANNIVERSARY-param = "VALUE=" ("date-and-or-time" / "text")
     ANNIVERSARY-value = date-and-or-time / text
       ; Value and parameter MUST match.

     ANNIVERSARY-param =/ altid-param / calscale-param / any-param
       ; calscale-param can only be present when ANNIVERSARY-value is
       ; date-and-or-time and actually contains a date or date-time.
	 */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'date-time' 		=> [
		  'VALUE'			=> [ 1, 'date-time ' ],
		  'CALSCALE'		=> [ 1, ' gregorian x- ' ],
		  '[ANY]'			=> [ 0 ],
		],
		'date' 	          	=> [
		  'VALUE'			=> [ 1, 'date' ],
		  'CALSCALE'		=> [ 1, ' gregorian x- ' ],
		  '[ANY]'			=> [ 0 ],
		],
		'time' 		        => [
		  'VALUE'			=> [ 1, 'time ' ],
		  'CALSCALE'		=> [ 1, ' gregorian x- ' ],
		  '[ANY]'			=> [ 0 ],
		],
		'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  'ALTID'			=> [ 0 ],
		  'LANGUAGE'	    => [ 6 ],
		],
	];

   /**
     * 	Singleton instance of object
     * 	@var fldAnniversary
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAnniversary {

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
				$var = 'text';
				$p = date_parse($rec['D']);
				if (!$p['warning_count'] && !$p['error_count']) {
					if (!$p['day'])
						$var = 'time';
					elseif (!$p['hour'])
						$var = 'date';
					else {
						$var = 'date-time';
					}
				}
				// check parameter
				parent::check($rec, self::RFCA_PARM[$var]);
				parent::delTag($int, $ipath);
				if ($var != 'date-time')
					$rec['P']['VALUE'] = $var;
				$int->addVar(self::TAG, Util::unxTime($rec['D']), FALSE, $rec['P']);
				$rc = TRUE;
			}
			break;

		case 'application/activesync.contact+xml':
	   		if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath, '2.5');
			while (($val = $ext->getItem()) !== NULL) {
				if ($val) {
					$int->addVar(self::TAG, Util::unxTime(substr($val, 0, 11).'00:00:00Z'), FALSE, [ 'VALUE' => 'date' ]);
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
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
   				unset($a['VALUE']);
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => gmdate(Util::STD_DATE, intval($val)) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.contact+xml':
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, gmdate(Util::masTIME, intval($val)), FALSE, $ext->setCP(XML::AS_CONTACT));
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
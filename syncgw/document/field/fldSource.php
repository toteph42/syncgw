<?php
declare(strict_types=1);

/*
 *  Source field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldSource extends fldHandler {

	// module version number
	const VER = 3;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Source';

	/*
	 SOURCE-param = "VALUE=uri" / pid-param / pref-param / altid-param
                  / mediatype-param / any-param
     SOURCE-value = URI
     */
	const RFCA_PARM			= [
		// description see fldHandler:check()
	    'uri'			  	=> [
		  'VALUE'			=> [ 1, 'uri ' ],
		  'PID'			  	=> [ 0 ],
		  'PREF'			=> [ 2, '1-100' ],
		  'ALTID'			=> [ 0 ],
		  'MEDIATYPE'		=> [ 7 ],
		  '[ANY]'			=> [ 0 ],
		],
	];

	/*
	 source       = "SOURCE" sourceparam ":" uri CRLF

     sourceparam = *(";" other-param)
    */
	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'uri'			  	=> [
		  '[ANY]'			=> [ 0 ],
		],
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldSource
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldSource {

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
		$parm  = NULL;

		switch ($typ) {
		case 'text/calendar':
		case 'text/x-vcalendar':
		    $parm = self::RFCC_PARM['uri'];

		case 'text/vcard':
		case 'text/x-vcard':
		    if (!$parm)
		        $parm = self::RFCA_PARM['uri'];
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// check value
        		$p = parse_url($rec['D']);
				if (!isset($p['scheme'])) {
					Debug::Msg('['.$rec['T'].'] ['.$rec['D'].'] not "uri" - dropping record'); //3
					continue;
				}
				// check parameter
				parent::check($rec, $parm);
				parent::delTag($int, $ipath);
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
		case 'text/calendar':
		case 'text/x-vcalendar':
		    $recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
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
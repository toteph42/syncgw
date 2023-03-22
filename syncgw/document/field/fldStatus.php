<?php
declare(strict_types=1);

/*
 *  Status field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldStatus extends fldHandler {

	// module version number
	const VER = 10;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG = 'Status';

	/*
	 status     = "STATUS" statparam] ":" statvalue CRLF

     statparam  = *(";" xparam)

     ;Status values for a "VEVENT"
     statvalue  = "TENTATIVE"           ;Indicates event is tentative.
                / "CONFIRMED"           ;Indicates event is definite.
                / "CANCELLED"           ;Indicates event was cancelled.

     ;Status values for "VTODO".
     statvalue  =/ "NEEDS-ACTION"       ;Indicates to-do needs action.
                / "COMPLETED"           ;Indicates to-do completed.
                / "IN-PROCESS"          ;Indicates to-do in process of
                / "CANCELLED"           ;Indicates to-do was cancelled.

     ;Status values for "VJOURNAL".
     statvalue  =/ "DRAFT"              ;Indicates journal is draft.
                / "FINAL"               ;Indicates journal is final.
                / "CANCELLED"           ;Indicates journal is removed.
	 */
	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  '[ANY]'			=> [ 0 ],
		],
	];
	const RFCC_VAL		 	= [
		'event'				=> 'TENTATIVE CONFIRMED CANCELLED ',
		'task'			 	=> 'NEEDS-ACTION COMPLETED IN-PROCESS CANCELLED ',
	];

	const AST_SUB           = 'X-PC';

	// [MS-ASEMAIL] 2.2.2.74 Status
	const ASM_MAP 			= [
		'CLEARED'			=> '0',		// The flag is cleared.
		'COMPLETED'			=> '1',		// The status is set to complete.
		'ACTIVE'			=> '2',		// The status is set to active.
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldStatus
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldStatus {
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
		$map   = NULL;
		$ipath .= self::TAG;

		switch ($typ) {
		case 'text/calendar':
		case 'text/x-vcalendar':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;

				if (strpos($rec['T'], 'VTODO')) {
					if ($rec['D'] == 'NEEDS ACTION') {
						$rec['D'] = 'NEEDS-ACTION';
						Debug::Msg('['.$rec['T'].'] parameter "NEEDS ACTION" converted to "NEEDS-ACTION"'); //3
					}
					$chk = self::RFCC_VAL['task'];
				} else
					$chk = self::RFCC_VAL['event'];
				if (strpos($chk, $rec['D']) === FALSE) {
					Debug::Msg('['.$rec['T'].'] - value "'.$rec['D'].'" invalid - dropping record'); //3
					continue;
				}
				// check parameter
				parent::check($rec, self::RFCC_PARM['text']);
				parent::delTag($int, $ipath);
				unset($rec['P']['VALUE']);
				// add dummy percentage for AsTask
				$rec['P'][self::AST_SUB] = '0';
				$int->addVar(self::TAG, $rec['D'], FALSE, $rec['P']);
				$rc = TRUE;
	  		}
			break;

		case 'application/activesync.task+xml':
		    if ($ext->xpath($xpath, FALSE)) {
		        $p = $int->savePos();
    			$int->xpath('/syncgw/Data/'.$ipath);
    			$int->getItem();
                $pc = $int->getAttr(self::AST_SUB);
                $int->restorePos($p);
				parent::delTag($int, $ipath);
		    } else
		        $pc = '0';
		    while (($val = $ext->getItem()) !== NULL) {
		    	if ($val == '1')
					$int->addVar(self::TAG, 'COMPLETED', FALSE, [ self::AST_SUB => '1' ]);
				else
					$int->addVar(self::TAG, 'IN-PROCESS', FALSE, [ self::AST_SUB => $pc ]);
				$rc = TRUE;
		    }
			break;

		case 'application/activesync.mail+xml':
			if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath);
			while (($val = $ext->getItem()) !== NULL) {
				$map = array_flip(self::ASM_MAP);
				if (!isset($map[$val])) {
					DbgWarm('['.$xpath.'] invalid value "'.$val.'" - dropping record'); //3
					continue;
				}
				$int->addVar(self::TAG, $map[$val], FALSE, [ self::AST_SUB => '0' ]);
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
		case 'text/calendar':
		case 'text/x-vcalendar':

			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				// $a['VALUE'] = 'TEXT';
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => $val ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.task+xml':
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, $val == 'COMPLETED' ? '1' : '0', FALSE, $ext->setCP(XML::AS_TASK));
				$rc	= TRUE;
			}
			break;

		case 'application/activesync.mail+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 12.0)
				break;

			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, self::ASM_MAP[$val], FALSE, $ext->setCP(XML::AS_MAIL));
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
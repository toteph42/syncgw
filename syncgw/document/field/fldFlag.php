<?php
declare(strict_types=1);

/*
 *  Flag field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldFlag extends fldHandler {

	// module version number
	const VER = 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Flag';

    const ASM_SUB			= [
	        'Subject'						=> 'fldSummary',
    		'Status'						=> 'fldStatus',
    		'FlagType'						=> 'FlagType',
    		'DateCompleted'					=> 'fldCompleted',
        	'CompleteTime'					=> 'fldCompleted',
        	'StartDate'						=> 'fldStartTime',
		    'UtcStartDate'					=> 'fldStartTime',
		    'DueDate'						=> 'fldDueDate',
	        'UtcDueDate'					=> 'fldDueDate',
	        'ReminderTime'					=> 'fldAlarm',
			// 'ReminderSet'				// Handled in fldAlarm
			'OrdinalDate'					=> 'fldOrdinal',
			'SubOrdinalDate'				=> 'fldOrdinalSub',
    ];

   	/**
     * 	Singleton instance of object
     * 	@var fldFlag
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldFlag {

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
		case 'application/activesync.mail+xml':
			if (!$ext->xpath($xpath, FALSE))
	        	break;
	        self::delTag($int, $ipath);
			$ip = $int->savePos();
	        $int->addVar(self::TAG);

	        $xp = $ext->savePos();
    	    foreach (self::ASM_SUB as $key => $class) {
	        	if (substr($class, 0, 3) == 'fld') {
               		$class = 'syncgw\\document\\field\\'.$class;
	               	$field = $class::getInstance();
               		$t = explode(',', $key);
               		if (isset($t[1]))
               			$t = $xpath.'/'.$t[0].','.$xpath.'/'.$t[1];
               		else
               			$t = $xpath.'/'.$key;
					if ($field->import($typ, $ver, $t, $ext, $ipath.'/', $int))
						$rc = TRUE;
	        	} elseif ($val = $ext->getVar($key, FALSE)) {
  		        	$int->addVar($key, $val);
					$rc = TRUE;
	        	}
	        	$ext->restorePos($xp);
	       	}
    	   	$int->restorePos($ip);
			if (!$rc)
				$int->delVar(self::TAG, FALSE);

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

		$rc  = FALSE;
		$mas = masHandler::getInstance();
		$ver = $mas->CallParm('BinVer');

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'application/activesync.mail+xml':
			$xp = $ext->savePos();
			$ext->addVar('Flag', NULL, FALSE, $ext->setCP(XML::AS_MAIL));
        	while ($int->getItem() !== NULL) {
		        $ip = $int->savePos();
        		foreach (self::ASM_SUB as $key => $class) {
					if (substr($class, 0, 3) == 'fld') {
						$class = 'syncgw\\document\\field\\'.$class;
	               		$field = $class::getInstance();
	               		if (strpos($key, 'Date') !== FALSE || strpos($key, 'Subj') !== FALSE)
	               			$typ = 'application/activesync.task+xml';
	                    $field->export($typ, $ver, '', $int, self::TAG.'/'.$key, $ext);
	                    $typ = 'application/activesync.mail+xml';
					} elseif ($val = $int->getVar($key, FALSE)) {
						if ($ver< 12.0)
							break;
						$ext->addVar($class, $val);
						$int->restorePos($ip);
					}
					$int->restorePos($ip);
	            }
	            $rc = TRUE;
       		}
       		$ext->restorePos($xp);
       		break;

		default:
			break;
		}

		return $rc;
	}

}

?>
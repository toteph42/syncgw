<?php
declare(strict_types=1);

/*
 * 	MIME decoder / encoder for "text/plain" class
 *
 *	@package	sync*gw
 *	@subpackage	mim support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\mime;

use syncgw\lib\Debug; //3
use syncgw\lib\DataStore;
use syncgw\lib\XML;
use syncgw\document\field\fldBody;

class mimPlain extends mimHandler {

	// module version number
	const VER = 9;

	const MIME = [

		[ 'text/plain', 1.1 ],
		[ 'text/plain',	1.0 ],
		[ 'text/plain',	0.0 ],
	];
	const MAP = [
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
	// Document source     															IrMC_v1p1.pdf
    // Chapter reference   															10.7.3
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
	    'SUMMARY'																	=> 'fldSummary',
	    'BODY'																		=> 'fldBody',
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
	];

    /**
     * 	Singleton instance of object
     * 	@var mimPlain
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mimPlain {

		if (!self::$_obj) {
            self::$_obj = new self();

			self::$_obj->_ver  = self::VER;
			self::$_obj->_mime = self::MIME;
			self::$_obj->_hid  = DataStore::NOTE;
			foreach (self::MAP as $tag => $class) {
			    $class = 'syncgw\\document\\field\\'.$class;
			    $class = $class::getInstance();
			    self::$_obj->_map[$tag] = $class;
			}
		}

		return self::$_obj;
	}

    /**
	 * 	Get information about class
	 *
     *	@param 	- TRUE = Check status; FALSE = Provide supported features
	 * 	@param 	- Object to store information
	 */
	public function Info(bool $mod, XML $xml): void {

		if (!$mod) {
			$xml->addVar('Opt', _('Plain text handler'));
			$xml->addVar('Ver', strval(self::VER));
		}

		parent::Info($mod, $xml);
	}

	/**
	 * 	Convert MIME data to internal document
	 *
	 *	@param	- MIME type
	 *  @param  - MIME version
	 *  @param  - External document
	 * 	@param 	- Internal document
	 * 	@return	- TRUE = Ok; FALSE = We're not responsible
	 */
	public function import(string $typ, float $ver, XML &$ext, XML &$int): bool {

		$data = $ext->getVar('Data');

		// decode and swap data
        $recs = $m = [];
		if (preg_match('/^(^\n)\n\n(.*)$/', $data, $m)) {
		    $recs[] = [ 'T' => 'SUMMARY', 'P' => [], 'D' => $m[0] ];
			$data = $m[1];
		} elseif (preg_match('/^.*(?=\norganizer.or6)/i', $data, $m)) {
			$recs[] = [ 'T' => 'SUMMARY', 'P' => [ 'X-TYP' => fldBody::TYP_OR6],  'D' => $m[0] ];
			preg_match('/(?<=\norganizer.or6).*/si', $data, $m);
			$data = $m[0];
		}
		$recs[] = [ 'T' => 'BODY', 'P' => [], 'D' => $data ];

		// swap data
		$int->getVar('Data');
		$rc = FALSE;
		foreach ($this->_map as $tag => $class)
		    if ($class->import($typ, $ver, $tag, $recs, 'Data/', $int))
		    	$rc = TRUE;

		return $rc;
	}

	/**
	 * 	Export to external document
	 *
	 *	@param	- Requested MIME type
	 *  @param  - Requested MIME version
	 * 	@param 	- Internal document
	 *  @param  - External document
	 * 	@return	- TRUE = Ok; FALSE = We're not responsible
	 */
	public function export(string $typ, float $ver, XML &$int, XML &$ext): bool {

		$int->getVar('Data'); //3
		Debug::Msg($int, 'Input document'); //3

		$int->getVar('syncgw');

		// swap data
		$data = '';
		foreach ($this->_map as $tag => $class) {
            $p = $int->savePos();
		    if (($rec = $class->export($typ, $ver, 'Data/', $int, $tag)) !== FALSE) {
        		// get data
            	$data .= $rec[0]['D'];
			    if (isset($rec['P']['X-TYP']) && $rec['P']['X-TYP'] == fldBody::TYP_OR6)
    				$data .= 'Organizer.OR6';
    		}
            $int->restorePos($p);
		}

		// add data
		$ext->addVar('Data', $data);

		return TRUE;
	}

}

?>
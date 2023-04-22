<?php
declare(strict_types=1);

/*
 * 	MIME decoder / encoder for ActiveSync classes
 *
 *	@package	sync*gw
 *	@subpackage	MIME support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\mime;

use syncgw\lib\Config;
use syncgw\lib\Debug; //3
use syncgw\lib\XML;
use syncgw\lib\DataStore;
use syncgw\document\field\fldTimezone;

class mimAs extends XML {

	// module version number
	const VER = 7;

	/**
	 *  Parents modul version
	 *  @var integer
	 */
	protected $_ver = 0;

	/**
	 *  Handler ID
	 *  @var int
	 */
	protected $_hid = 0;

	/**
	 *  mim types
	 *  @var array
	 */
	public $_mime = [];

	/**
	 *  Mapping table
	 */
	public $_map = [];

	/**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt', 'ActiveSync base handler');
		$xml->addVar('Ver', strval(self::VER));

		foreach ($this->_mime as $mime) {
			$xml->addVar('Opt', sprintf(_('MIME type handler "%s %s"'), $mime[0], $mime[1] ? sprintf('%.1F', $mime[1]) : ''));
			$xml->addVar('Ver', strval($this->_ver));
		}
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

		$xp = $ext->savePos();

		// be sure to set proper position in internal document
		$int->getVar('Data');

 		// swap data
 		$rc = FALSE;
		foreach ($this->_map as $tag => $class) {
		   	if ($class->import($typ, $ver, $tag, $ext, '', $int))
		   		$rc = TRUE;
		    $ext->restorePos($xp);
		}
		$tag; // disable Eclipse warning

		$int->getVar('syncgw');
		Debug::Msg($int, 'Imported document'); //3

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

		$int->getVar('syncgw');
		Debug::Msg($int, 'Input document'); //3
		$ip = $int->savePos();

		// add time zone parameter
		if ($this->_hid & DataStore::CALENDAR) {
			$int->getVar('Data');
			$cnf = Config::getInstance();
			$int->addVar(fldTimezone::TAG, $cnf->getVar(Config::TIME_ZONE));
		    $int->restorePos($ip);
		}

		$ext->addVar('ApplicationData');
		$xp = $ext->savePos();

		// swap data
		foreach ($this->_map as $tag => $class) {
		    $class->export($typ, $ver, 'Data/', $int, $tag, $ext);
		    $int->restorePos($ip);
		}
		$tag; // disable Eclipse warning

		$ext->restorePos($xp);
		Debug::Msg($ext, 'Output document'); //3

		return TRUE;
	}

}

?>
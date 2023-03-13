<?php
declare(strict_types=1);

/*
 * 	Administration interface handler class
 *
 *	@package	sync*gw
 *	@subpackage	File handler
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\file;

use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\interfaces\DBAdmin;
use syncgw\gui\guiHandler;

class Admin implements DBAdmin {

	// module version number
	const VER = 8;

    /**
     * 	Singleton instance of object
     * 	@var Admin
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Admin {

	   	if (!self::$_obj)
            self::$_obj = new self();

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt', _('File administration handler'));
		$xml->addVar('Stat', 'v'.Server::MVER.sprintf('%03d', self::VER));
	}

    /**
	 * 	Show/get installation parameter
	 */
	public function getParms(): void {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		if (!($cnf->getVar(Config::DATABASE))) {
			if(!($c = $gui->getVar('FileDir')))
				$c = $cnf->getVar(Config::FILE_DIR);
			$gui->putQBox(_('Data base root directory'),
							'<input name="FileDir" type="text" size="40" maxlength="250" value="'.$c.'" />',
							_('Please specify where files should be stored. Please be aware you need to enable access to this directory '.
							'for your web server user id (<a class="sgwA" href="http://www.php.net/manual/en/ini.sect.safe-mode.php#ini.open-basedir"'.
							' target="_blank">more information</a>).'), FALSE);
		}
	}

	/**
	 * 	Connect to handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
	 */
	public function Connect(): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		// connection already established?
		if ($cnf->getVar(Config::DATABASE))
			return TRUE;

		$v = $gui->getVar('FileDir');
		if (!$v || !realpath($v)) {
			$gui->clearAjax();
			$gui->putMsg(_('Directory does not exist'), Util::CSS_ERR);
			return FALSE;
		}

		// does directory exist?
		if (!@file_exists($v))
			@mkdir($v, 0755, TRUE);

		// check attributes
		if (!@is_writable($v)) {
			$gui->clearAjax();
			$gui->putMsg(_('Error accessing directory - please check file permission on directory \'syncgw\''), Util::CSS_ERR);
			return FALSE;
		}

		// save path
		$cnf->updVar(Config::FILE_DIR, str_replace([ '\\', '/' ], [ DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR ], $v));

		return TRUE;
	}

	/**
	 * 	Disconnect from handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
 	 */
	public function DisConnect(): bool {
		return TRUE;
	}

	/**
	 * 	Return list of supported data store handler
	 *
	 * 	@return - Bit map of supported data store handler
	 */
	public function SupportedHandlers(): int {
		return DataStore::DATASTORES&~DataStore::MAIL;
	}

}

?>
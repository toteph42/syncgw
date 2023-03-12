<?php
declare(strict_types=1);

/*
 * 	Administration interface handler
 *
 *	@package	sync*gw
 *	@subpackage	myApp handler
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\myapp;

use syncgw\gui\guiHandler;
use syncgw\interfaces\DBAdmin;
use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;

class Admin extends \syncgw\interfaces\mysql\Admin implements DBAdmin {

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
		$xml->addVar('Opt', _('MyApp administration handler'));
		$xml->addVar('Stat', 'v1.0');
	}

    /**
	 * 	Show/get installation parameter
	 */
	public function getParms(): void {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		$gui->putQBox('Please enter message which will be used during execution',
					  '<input name="MyParm" type="text" size="40" maxlength="250" value="'.$cnf->getVar('Usr_Parm').'" />',
					  'MyApp dummy message.', FALSE);

		parent::getParms();
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

		if ($v = $gui->getVar('MyParm'))
			$cnf->updVar('Usr_Parm', $v);

		// create our own tables
		$cmds = parent::loadSQL(Util::mkPath('interfaces/myapp').'/tables.sql');
		parent::mkTable($cmds);

		return parent::Connect();
	}

	/**
	 * 	Disconnect from handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
	 */
	public function DisConnect(): bool {

		// remove parameter
		$cnf = Config::getInstance();
		$cnf->updVar('Usr_Parm', '');

		// delete our own tables
		$cmds = parent::loadSQL(Util::mkPath('interfaces/myapp').'/tables.sql');
		parent::delTable($cmds);

		return parent::DisConnect();
	}

	/**
	 * 	Return list of supported data store handler
	 *
	 * 	@return - Bit map of supported data store handler
 	 */
	public function SupportedHandlers(): int {
		return DataStore::EXT|DataStore::NOTE;
	}

}

?>
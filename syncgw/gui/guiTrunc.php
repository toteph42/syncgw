<?php
declare(strict_types=1);

/*
 * 	Truncate all tables
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiTrunc {

	// module version
	const VER = 14;

    /**
     * 	Singleton instance of object
     * 	@var guiTrunc
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiTrunc {

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

		$xml->addVar('Opt', _('Truncate all data base tables plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		$gui = guiHandler::getInstance();

		// only allowed for administrators
		if (!$gui->isAdmin())
			return guiHandler::CONT;

		switch ($action) {
		case 'Init':
			$gui->putCmd('<input id="Trunc" '.($gui->getVar('LastCommand') == 'Trunc' ? 'checked ' : '').'type="radio" name="Command" '.
						 'value="Trunc" onclick="'.
						 'var a=document.getElementById(\'Trunc\');'.
						 'if (confirm(\''._('Do you really want to truncate all tables?').'\')) {'.
						 'a.checked = true;'.
						 '} else '.
						 'a.checked = false;document.syncgw.submit();"'.
						 '/>&nbsp;<label for="Trunc">'._('Truncate ALL <strong>sync&bull;gw</strong> tables').'</label>');
			break;

		case 'Trunc':
			$cnf = Config::getInstance();
			if ($cnf->getVar(Config::DATABASE) == 'file') {
				$dir = $cnf->getVar(Config::FILE_DIR);
				$gui->putMsg(sprintf(_('Deleting all files and sub directories in [%s]'), $dir));
				// shutdown server to initiate re-creation of directories
				$srv = Server::getInstance();
				$srv->shutDown();
				// delete files
				Util::rmDir($dir);
				break;
			}
			$p = $cnf->getVar(Config::DB_PREF);
			$recs = [
					'TRUNCATE TABLE `'.$p.'_User`;',
					'TRUNCATE TABLE `'.$p.'_Session`;',
					'TRUNCATE TABLE `'.$p.'_Device`;',
					'TRUNCATE TABLE `'.$p.'_Attachments`;',

					'TRUNCATE TABLE `'.$p.'_Contact`;',
					'TRUNCATE TABLE `'.$p.'_Calendar`;',
					'TRUNCATE TABLE `'.$p.'_Task`;',
					'TRUNCATE TABLE `'.$p.'_Note`;',
					'TRUNCATE TABLE `'.$p.'_Mail`;',
					'TRUNCATE TABLE `'.$p.'_SMS`;',
			];
			$db = DB::getInstance();
			foreach ($recs as $rec) {
				$gui->putMsg($rec);
				if(!$db->SQL($rec))
					$gui->putMsg(_('Error executing SQL command'), Util::CSS_ERR);
			}

		    // delete trace directory
		    Util::rmDir($p = $cnf->getVar(Config::TRACE_DIR));
		    mkdir($p);

			$gui->putMsg('');
            $gui->putMsg(_('All <strong>sync&bull;gw</strong> tables truncated in database'), Util::CSS_INFO);

		default:
			break;
		}

		return guiHandler::CONT;
	}

}

?>
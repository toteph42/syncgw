<?php
declare(strict_types=1);

/*
 * 	Clean trace / session data
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiCleanUp {

	// module version
	const VER = 9;

    /**
     * 	Singleton instance of object
     * 	@var guiCleanUp
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiCleanUp {

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

		$xml->addVar('Opt', _('Data store cleanup plugin'));
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

		switch ($action) {
		case 'ExpCleanup':
			$hid = intval($gui->getVar('ExpHID'));
		    $cnf = Config::getInstance();
		    if ($hid & DataStore::TRACE && ($p = $cnf->getVar(Config::TRACE_DIR))) {
		    	Util::rmDir($p);
		    	if (!file_exists($p))
			    	mkdir($p);
		    } else {
				$db  = DB::getInstance();
			    if ($cnf->getVar(Config::DATABASE) != 'file') {
	    			$r  = $cnf->getVar(Config::DB_PREF);
	    			$r  = 'TRUNCATE TABLE `'.$r.'_'.Util::HID(Util::HID_TAB, $hid).'`;';
					if(!$db->SQL($r))
						$gui->putMsg(_('Error executing SQL command'), Util::CSS_ERR);
			    } else {
	    			foreach ($db->getRIDS($hid) as $id => $unused) {
	    				if ($hid & DataStore::USER)
		       				guiDelete::delUser($id);
	    				else
		       				$db->Query($hid, DataStore::DEL, $id);
	    			}
					$unused; // disable Eclipse warning
			    }
		    }
			$gui->updVar('ExpGID', '');
		    $gui->updVar('Action', 'Explorer');
			$gui->clearAjax();
			return guiHandler::RESTART;

		default:
			break;
		}

		// allow only during explorer call
		if (substr($a = $gui->getVar('Action'), 0, 3) == 'Exp' && substr($a, 6, 4) != 'Edit' && $gui->getVar('ExpHID'))
			$gui->setVal($gui->getVar('Button').$gui->mkButton(_('CleanUp'), _('Delete ALL record in selected data store'),
					'var v=document.getElementById(\'Action\');'.
					'if (confirm(\''._('Do you really want to delete >ALL< records in selected group?').'\')) {'.
					'v.value=\'ExpCleanup\'; '.
					'} else'.
					'v.value=\'Explorer\';'), TRUE);

		return guiHandler::CONT;
	}

}

?>
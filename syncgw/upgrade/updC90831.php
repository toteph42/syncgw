<?php
declare(strict_types=1);

/*
 *  Configuration upgrade handler class
 *
 *	@package	sync*gw
 *	@subpackage	Upgrade
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\upgrade;

class updC90831 {

	/**
	 * 	Upgrade configuration
	 *
	 *	@param  - Version to upgrade to
	 * 	@return	- TRUE = Ok; FALSE =A bort execution
	 */
	public function upgrade(string $ver): bool {

//		$name = Util::mkPath('config.ini.php');
//		if (!file_exists($name))
//			return TRUE;

//		$conf = @parse_ini_file($name);

//		if (isset($conf[Config::UPGRADE]) && $conf[Config::UPGRADE] != '0.00.00') {
//			$gui = GUI_Handler::getInstance();
//			$gui->putMsg(_('Upgrading this <strong>sync&bull;gw</strong> installation is not supported. ').
//						 _('For more information please check version announcement in our forum'), Util::CSS_ERR);
//			return FALSE;
//		}

		return TRUE;
	}

}

?>
<?php
declare(strict_types=1);

/*
 * 	Environement and server check
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiCheck {

	// module version
	const VER  = 5;

    /**
     * 	Singleton instance of object
     * 	@var guiCheck
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiCheck {

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

		$xml->addVar('Opt', _('Environment and server check plugin'));
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
		case 'Init':
			$gui->putCmd('<input id="Check" '.($gui->getVar('LastCommand') == 'Check' ? 'checked ' : '').'type="radio" name="Command" '.
						 'value="Check" onclick="document.syncgw.submit();"/>&nbsp;'.
						 '<label for="Check">'._('Environment and server check').'</label>');
			break;

		case 'Check':

			// perform basic checks
			$err = 0;
			$ok = _('Implemented');
			$gui->tabMsg(_('Checking PHP version ...'), '', phpversion());

		    $s  = array( 'B', 'KB', 'MB', 'GB', 'TB' );
    		$b1 = disk_free_space('.');
		    $c1 = min((int)log($b1, 1024) , count($s) - 1);
    		$b2 = disk_total_space('.');
		    $c2 = min((int)log($b2, 1024) , count($s) - 1);
    		$gui->tabMsg(_('Checking disk space ...'), '', sprintf('%1.2f', $b1 / pow(1024, $c1)).' '.$s[$c1].' / '.
									 				       sprintf('%1.2f', $b2 / pow(1024, $c2)).' '.$s[$c2]);

			$m = _('Checking \'Document Object Model\' (DOM) PHP extension');
			if (!class_exists('DOMDocument')) {
				$gui->tabMsg($m, '', _('PHP extension must be enabled in PHP.INI (<a class="sgwA" '.
							 'href="http://www.php.net/manual/en/dom.setup.php'.
							 '" target="_blank">more information</a>)'), Util::CSS_ERR);
				$err++;
			} else
				$gui->tabMsg($m, '', $ok);

			$m = _('Checking \'DOM_XML\' PHP extension ...');
			if (function_exists('domxml_new_doc')) {
				$gui->tabMsg($m, '', _('PHP extension must be disabled in PHP.INI (<a class="sgwA" '.
							 'href="http://www.php.net/manual/en/domxml.installation.php'.
							 '" target="_blank">more information</a>)'), Util::CSS_ERR);
				$err++;
			} else
				$gui->tabMsg($m, '', $ok);

			$m = _('Checking \'GD\' PHP extension ...');
			if (!function_exists('gd_info')) {
				$gui->tabMsg($m, '', _('PHP extension must be enabled in PHP.INI (<a class="sgwA" '.
							 'href="http://www.php.net/manual/en/image.setup.php'.
							 '" target="_blank">more information</a>)'), Util::CSS_ERR);
				$err++;
			} else
				$gui->tabMsg($m, '', $ok);

			$m = _('Checking \'ZIP\' PHP extension ...');
			if (!class_exists('ZipArchive'))
				$gui->tabMsg($m, '', _('You need to enable this PHP extension in if you want to download or upload data in administrator panel '.
							 '(<a class="sgwA" '.
							 'href="http://www.php.net/manual/en/zip.setup.php" target="_blank">more information</a>)'), Util::CSS_WARN);
			else
				$gui->tabMsg($m, '', $ok);

			if (file_exists(Util::mkPath('interfaces/mysql'))) {
				$m = _('Checking \'MySQL improved\' PHP extension ...');
				if (!function_exists('mysqli_connect')) {
					$gui->tabMsg($m, '', _('You need to enable this PHP extension in PHP.INI if you want to use a MySQL based data base handler'.
								 '(<a class="sgwA" href="http://www.php.net/manual/en/mysqli.installation.php" '.
								 'target="_blank">more information</a>)'), Util::CSS_WARN);
					$err++;
				} else
					$gui->tabMsg($m, '', $ok);
			}

			$m = _('Checking \'Gettext\' PHP extension ...');
			if (!function_exists('gettext'))
				$gui->tabMsg($m, '', _('You need to enable this PHP extension in PHP.INI if you want to use native language support '.
							 '(<a class="sgwA" href="http://www.php.net/manual/en/book.gettext.php" '.
							 'target="_blank">more information</a>)'), Util::CSS_WARN);
			else
				$gui->tabMsg($m, '', $ok);

			$m = _('Checking \'Multibyte string\' PHP extension ...');
			if (!function_exists('mb_convert_encoding'))
				$gui->tabMsg($m, '', _('You need to enable this PHP extension in PHP.INI if you want to synchronize multi-byte data '.
							 '(<a class="sgwA" href="http://www.php.net/manual/en/mbstring.installation.php" '.
							 'target="_blank">more information</a>)'), Util::CSS_WARN);
			else
				$gui->tabMsg($m, '', $ok);

			if (!$err) {
				$m = _('Checking directory path used for temporary files ...');
				if ($tmp = Util::getTmpFile()) {
				    unlink($tmp);
					$rc = TRUE;
				} else
					$rc = FALSE;
				if ($rc === FALSE) {
					$gui->tabMsg($m, '', _('Please enable access to directory in PHP.INI (<a class="sgwA" href="'.
								 'http://www.php.net/manual/en/ini.core.php#ini.open-basedir" target="_blank">more information</a>)'),
								 Util::CSS_ERR);
					$err++;
				} else
					$gui->tabMsg($m, '', $ok);
			}

			// create footer
			$gui->putMsg('');
			$m = _('Environment status');
			$cnf = Config::getInstance();
			if ($err)
				$gui->tabMsg($m, Util::CSS_TITLE, sprintf(_('%d errors found - please fix errors and run script again'), $err),
							 Util::CSS_ERR);
			elseif (!$cnf->getVar(Config::DATABASE)) {
				$gui->tabMsg($m, Util::CSS_TITLE, _('Warning - no data base connected'), Util::CSS_WARN);
				$err = 1;
			}
			if (!$err)
				$gui->tabMsg($m, Util::CSS_TITLE, _('Ok'), Util::CSS_TITLE);

			// is server configured?
			if (!$gui->isConfigured()) {
				$gui->tabMsg(_('<strong>sync&bull;gw</strong> status'), Util::CSS_TITLE, _('Server not configured'), Util::CSS_ERR);
				break;;
			}

			// create server object
			$tit = _('Ready for synchronizing!');
			$col = Util::CSS_TITLE;

			// get server information
			$srv = Server::getInstance();
			$xml = $srv->getInfo(TRUE);

			$tag = '';
			$xml->getChild('syncgw');
			while (($v = $xml->getItem()) !== NULL) {
				switch ($xml->getName()) {
				case 'Name':
					$m = $v;
					break;

				case 'Ver':
					if ($tag == 'Name') {
						$gui->putMsg('');
						$gui->tabMsg($m, Util::CSS_TITLE, 'v'.Server::MVER.sprintf('%03d', $v), Util::CSS_TITLE);
					} else
						$gui->tabMsg($m, '', 'v'.Server::MVER.sprintf('%03d', $v), '');
					break;

				case 'Opt':
					$m = '&raquo; '.$v;
					break;

				case 'Stat':
					if (stripos($v, '+++') !== FALSE) {
						$gui->tabMsg($m, '', $v, Util::CSS_ERR);
						$tit = _('Configuration error. Please see above ...');
						$col = Util::CSS_ERR;
					} else
						$gui->tabMsg($m, '', $v, '');
					break;

				default:
					break;
				}
				$tag = $xml->getName();
			}
			$gui->putMsg('');
			$gui->tabMsg(_('Overall status'), Util::CSS_TITLE, $tit, $col);
			$gui->putMsg('');
			break;

		default:
			break;
		}

		return guiHandler::CONT;
	}

}

?>
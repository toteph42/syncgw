<?php
declare(strict_types=1);

/*
 * 	Administration interface handler class
 *
 *	@package	sync*gw
 *	@subpackage	Mail handler
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\mail;

use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\interfaces\DBAdmin;
use syncgw\gui\guiHandler;

class Admin implements DBAdmin {

	// module version number
	const VER = 1;

    /**
     * 	Pointer to sustaninable handlerr
     * 	@var Admin
     */
    private $_hd = NULL;

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

		if (!self::$_obj) {

            self::$_obj = new self();

			// check for roundcube interface
		    $file = Util::mkPath('interfaces/roundcube/').'Admin.php';
			if (file_exists($file))
				self::$_obj->_hd = \syncgw\interfaces\roundcube\Admin::getInstance();
			else
				self::$_obj->hd = \syncgw\interfaces\mysql\Admin::getInstance();
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

		$xml->addVar('Opt', _('Mail administration handler'));
		$xml->addVar('Stat', 'v'.Server::MVER.sprintf('%03d', self::VER));
	}

    /**
	 * 	Show/get installation parameter
	 */
	public function getParms(): void {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		if(!($c = $gui->getVar('IMAPHost')))
			$c = $cnf->getVar(Config::IMAP_HOST);
		$gui->putQBox(_('IMAP server name'),
					  '<input name="IMAPHost" type="text" size="40" maxlength="250" value="'.$c.'" />',
					  _('IMAP server name where your mails resides (default: "localhost").'), FALSE);
		if(!($c = $gui->getVar('IMAPPort')))
			$c = $cnf->getVar(Config::IMAP_PORT);
		$gui->putQBox(_('IMAP port address'),
					'<input name="IMAPPort" type="text" size="5" maxlength="6" value="'.$c.'" />',
					_('IMAP server port (default: 143).'), FALSE);
		$enc = [
				_('None')	=> '',
				_('SSL')	=> 'SSL',
				_('TLS')	=> 'TLS',
		];
		if(!($c = $gui->getVar('IMAPEnc')))
			$c = $cnf->getVar(Config::IMAP_ENC);
		$f = '<select name="'.'IMAPEnc">';
		foreach ($enc as $k => $v) {
			$s = $v == $c ? 'selected="selected"' : '';
			$f .= '<option '.$s.' value="'.$v.'">'.$k.'</option>';
		}
		$f .= '</select>';
		$gui->putQBox(_('IMAP encryption'), $f,
					_('Specify encryption to use for connection (default: "None").'), FALSE);
		$yn = [
				_('Yes')	=> 'Y',
				_('No')		=> 'N',
		];
		if(!($c = $gui->getVar('IMAPCert')))
			$c = $cnf->getVar(Config::IMAP_CERT);
		$f = '<select name="'.'IMAPCert">';
		foreach ($yn as $k => $v) {
			$s = $v == $c ? 'selected="selected"' : '';
			$f .= '<option '.$s.' value="'.$v.'">'.$k.'</option>';
		}
		$f .= '</select>';
		$gui->putQBox(_('IMAP server certificate validation'), $f,
					_('Speficfy if <strong>sync&bull;gw</strong> should request validation of server certificate for IMAP connection.'), FALSE);

		if(!($c = $gui->getVar('SMTPHost')))
			$c = $cnf->getVar(Config::SMTP_HOST);
		$gui->putQBox(_('SMTP server name'),
					  '<input name="SMTPHost" type="text" size="40" maxlength="250" value="'.$c.'" />',
					  _('SMTP server name to use for sending mails (default: "localhost").'), FALSE);
		if(!($c = $gui->getVar('SMTPPort')))
			$c = $cnf->getVar(Config::SMTP_PORT);
		$gui->putQBox(_('SMTP port address'),
					'<input name="SMTPPort" type="text" size="5" maxlength="6" value="'.$c.'" />',
					_('SMTP server port (default: 25).'), FALSE);
		$yn = [
				_('Yes')	=> 'Y',
				_('No')		=> 'N',
		];

		if(($c = $gui->getVar('SMTPAuth')) === NULL)
			$c = $cnf->getVar(Config::SMTP_AUTH);

		$f = '<select name="SMTPAuth">';
		foreach ($yn as $k => $v) {
			$s = $v == $c ? 'selected="selected"' : '';
			$f .= '<option '.$s.' value="'.$v.'">'.$k.'</option>';
		}
		$f .= '</select>';

		$gui->putQBox(_('SMTP authentication'), $f,
			 		   _('By default <strong>sync&bull;gw</strong> use SMTP authentication. Setting this option to '.
						'<strong>No</strong> disables SMTP authentication.'), FALSE);
		$enc = [
				_('None')	=> '',
				_('SSL')	=> 'SSL',
				_('TLS')	=> 'TLS',
		];
		if(!($c = $gui->getVar('SMTPEnc')))
			$c = $cnf->getVar(Config::SMTP_ENC);
		$f = '<select name="'.'SMTPEnc">';
		foreach ($enc as $k => $v) {
			$s = $v == $c ? 'selected="selected"' : '';
			$f .= '<option '.$s.' value="'.$v.'">'.$k.'</option>';
		}
		$f .= '</select>';
		$gui->putQBox(_('SMTP encryption'), $f,
					_('Specify encryption to use for connection (default: "None").'), FALSE);

		if(!($c = $gui->getVar('ConTimeout')))
			$c = $cnf->getVar(Config::CON_TIMEOUT);
		$gui->putQBox(_('Connection timeout'),
					'<input name="ConTimeout" type="text" size="5" maxlength="6" value="'.$c.'" />',
					_('Connection test timeout in seconds (defaults to 5 seconds)'), FALSE);
		$gui->putQBox(_('Login credentials for connection test'),
					'<input name="MAILUsr" type="text" size="20" maxlength="64" value="'.$gui->getVar('MAILUsr').'" />',
					_('Login credeentials (e-mail address) used for testing the IMAP and SMTP connection (will not be stored).'), FALSE);
		$gui->putQBox(_('Login password for connection test'),
					  '<input name="MAILUpw" type="password" size="20" maxlength="30" value="'.$gui->getVar('MAILUpw').'" />',
					  _('Password for connection test (will not be stored).'), FALSE);

		$this->_hd->getParms();
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

		// swap variables
		$cnf->updVar(Config::IMAP_HOST, $gui->getVar('IMAPHost'));
		if (!$cnf->getVar(Config::IMAP_HOST)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing IMAP server name.'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::IMAP_PORT, $gui->getVar('IMAPPort'));
		if (!$cnf->getVar(Config::IMAP_PORT)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing IMAP server port number.'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::IMAP_ENC, $gui->getVar('IMAPEnc'));
		$cnf->updVar(Config::IMAP_CERT, $gui->getVar('IMAPCert'));

		$cnf->updVar(Config::SMTP_HOST, $gui->getVar('SMTPHost'));
		if (!$cnf->getVar(Config::SMTP_HOST)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing SMTP server name.'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::SMTP_PORT, $gui->getVar('SMTPPort'));
		if (!$cnf->getVar(Config::SMTP_PORT)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing SMTP server port number.'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::SMTP_AUTH, $gui->getVar('SMTPAuth'));
		$cnf->updVar(Config::SMTP_ENC, $gui->getVar('SMTPEnc'));

		$cnf->updVar(Config::CON_TIMEOUT, $gui->getVar('ConTimeout'));

		$uid = $gui->getVar('MAILUsr');
		$upw = $gui->getVar('MAILUpw');

		if ($uid && $upw) {
			$hd = Handler::getInstance();
			if (!$hd->IMAP($uid, $upw, TRUE))
				return FALSE;
			if (!$hd->SMTP($uid, $upw, TRUE))
				return FALSE;
			$srv = Server::getInstance();
			$srv->shutDown();
		}

		return $this->_hd->Connect();
	}

	/**
	 * 	Disconnect from handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
 	 */
	public function DisConnect(): bool {

		return $this->_hd->DisConnect();
	}

	/**
	 * 	Return list of supported data store handler
	 *
	 * 	@return - Bit map of supported data store handler
	 */
	public function SupportedHandlers(): int {

		return $this->_hd->SupportedHandlers()|DataStore::MAIL;
	}

}

?>
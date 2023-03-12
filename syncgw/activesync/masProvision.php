<?php
declare(strict_types=1);

/*
 * 	<Provision> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\lib\ErrorHandler;
use syncgw\lib\Log;
use syncgw\lib\Util;
use syncgw\lib\XML;

class masProvision extends XML {

	// module version number
	const VER 		 = 6;

	// status codes
	const NOPOLICY   = '2';
	const TYPE	 	 = '3';
	const CORRUPT	 = '4';
	const KEY		 = '5';
	// status description
	const STAT       = [ //2
		self::NOPOLICY		=>	'There is no policy for this client', //2
		self::TYPE			=>	'Unknown PolicyType value', //2
		self::CORRUPT		=> 	'The policy data on the server is corrupted (possibly tampered with)', //2
		self::KEY			=>	'The client is acknowledging the wrong policy key', //2
	]; //2

   /**
     * 	Singleton instance of object
     * 	@var masProvision
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masProvision {

		if (!self::$_obj) {
            self::$_obj = new self();

			// set messages 16101-16200
			$log = Log::getInstance();
			$log->setMsg([
					16101 => _('Error loading [%s]'),
					16102 => _('Provision status %d received from device'),
			]);

			// load policy
			$file = Util::mkPath('source/masPolicy.xml');
	        if (!self::$_obj->loadFile($file))
	        	ErrorHandler::Raise(16101, $file);
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

		$xml->addVar('Opt', '<a href="https://learn.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-asprov" target="_blank">[MS-ASPROV]</a> '.
				      'Exchange ActiveSync: Provisioning handler');
		$xml->addVar('Ver', strval(self::VER));
		$xml->addVar('Opt', _('Exchange ActiveSync server policy version'));
		parent::getVar('Policy');
		$v = parent::getAttr('ver');
		$xml->addVar('Stat', $v ? 'v'.$v : _('N/A'));
	}

	/**
	 * 	Parse XML node
	 *
	 * 	@param	- Input document
	 * 	@param	- Output document
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function Parse(XML &$in, XML &$out): bool {

		Debug::Msg($in, '<Provision> input'); //3

		$out->addVar('Provision', NULL, FALSE, $out->setCP(XML::AS_PROVISION));

		// used for sending the device's properties to the server in an initial <Provision> command request
		// version 14.0 or 12.1 will not send data, but we take what we get :-)
		if ($in->getVar('DeviceInformation') !== NULL) {
			$op = $out->savePos();
			// create dummy output object
			$xml = new XML();
			$set = masSettings::getInstance();
			$set->Parse($in, $xml);
			$out->addVar('DeviceInformation', NULL, FALSE, $out->setCP(XML::AS_SETTING));
			$out->addVar('Status', $xml->getVar('Status'), FALSE, $out->setCP(XML::AS_PROVISION));
			$out->restorePos($op);
		}

		$out->addVar('Status', $rc = masStatus::OK, FALSE, $out->setCP(XML::AS_PROVISION));

		// <Policies> -> not used

		$in->xpath('//Policy');
		while ($in->getItem() !== NULL) {

			$ip = $in->savePos();
			$op = $out->savePos();

			$out->addVar('Policies');
			$out->addVar('Policy');

			// specifies the format in which the policy settings are to be provided to the device
			$typ = $in->getVar('PolicyType', FALSE);
			// any Policy elements that have a value for their PolicyType child element other than "MS-EAS-Provisioning-WBXML" SHOULD be ignored.
			if ($typ != 'MS-EAS-Provisioning-WBXML')
				$typ = 'MS-EAS-Provisioning-WBXML';
			$out->addVar('PolicyType', $typ);

			$out->addVar('Status', $rc = masStatus::OK);

			// mark the state of policy settings on the client in the settings download phase of the <Provision> command
			$in->restorePos($ip);
			if (!($pkey = $in->getVar('PolicyKey', FALSE)))
				$k = time() % 65535;
			else
				$k = $pkey;
			$out->addVar('PolicyKey', strval($k));

			// check client return code
			if ($pkey) {
				$in->restorePos($ip);
				if (($n = $in->getVar('Status', FALSE)) != masStatus::OK && $n != self::NOPOLICY) {
					$log = Log::getInstance();
					$log->Msg(Log::WARN, 16102, $n);
				}
			}

			// send default policy
			if ($rc == masStatus::OK && !$pkey) {
				$out->addVar('Data');
				// specifies the collection of security settings for device provisioning
				$out->addVar('EASProvisionDoc');

				parent::getChild('Policy');
				while (($v = parent::getItem()) !== NULL)
					$out->addVar(parent::getName(), $v);
			}

			$in->restorePos($ip);
			$out->restorePos($op);
		}

		// @todo WIPE <RemoteWipe> - remote wipe directive
		// specifies either a remote wipe directive from the server or a client's confirmation of a server's remote wipe directive
		$in->xpath('//RemoteWipe');
		while ($in->getItem() !== NULL) {

			$ip = $in->savePos();
			$op = $out->savePos();


			$in->restorePos($ip);
			$out->restorePos($op);
		}

		// @todo WIPE <AccountOnlyRemoteWipe> - account only remote wipe directive
		// specifies either an account only remote wipe directive from the server or a client's
		// confirmation of a server's account only remote wipe directive
		$in->xpath('//AccountOnlyRemoteWipe');
		while ($in->getItem() !== NULL) {

			$ip = $in->savePos();
			$op = $out->savePos();


			$in->restorePos($ip);
			$out->restorePos($op);
		}

		$out->getVar('Provision'); //3
		Debug::Msg($out, '<Provision> output'); //3

		return TRUE;
	}

	/**
	 * 	Get status comment
	 *
	 *  @param  - Path to status code
	 * 	@param	- Return code
	 * 	@return	- Textual equation
	 */
	static public function status(string $path, string $rc): string { //2

		if (isset(self::STAT[$rc])) //2
			return self::STAT[$rc]; //2
		if (isset(masStatus::STAT[$rc])) //2
			return masStatus::STAT[$rc]; //2
		return 'Unknown return code "'.$rc.'"'; //2
	} //2

}

?>
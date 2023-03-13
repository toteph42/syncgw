<?php
declare(strict_types=1);

/*
 * 	<QueryRows> handler class
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi;

use syncgw\lib\Debug; //3
use syncgw\lib\User;
use syncgw\lib\XML;

class mapiQueryRows extends mapiWBXML {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var mapiQueryRows
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mapiQueryRows {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; request/response handler'), 'QueryRows'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse <QueryRows> request / response
	 *
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 * 	@return	- new XML structure or NULL
	 */
	public function Parse(int $mod): ?XML {

		// [MS-OXCMAPIHTTP] 2.2.5.12.1 QueryRows Request Type Request Body
		// [MS-OXCMAPIHTTP] 2.2.5.12.2 QueryRows Request Type Success Response Body
		// [MS-OXCMAPIHTTP] 2.2.5.12.3 QueryRows Request Type Failure Response Body
		// [MS-OXNSPI] 		2.2.8 <State>
		// [MS-OXNSPI] 		2.2.9.1 MinimalEntryID
		// [MS-OXCMAPIHTTP] 2.2.1.8 <LargePropertyTagArray> Structure
		// [MS-OXCDATA] 	2.11.2.1 PropertyValue Structure
		// [MS-OXNSPI] 		2.2.1.2 Permitted Error Code Values
		// [MS-OXCDATA] 	2.4 Error Codes
		// [MS-OXCMAPIHTTP] 2.2.1.7 AddressBookPropertyRow Structure
		// [MS-OXCRPC] 		3.1.4.1.1 <AuxiliaryBuffer> Extended Buffer Handling
		// [MS-OXCRPC] 		3.1.4.1.1.1.1 rgbAuxIn Input Buffer

		// load skeleton
		if (!($xml = parent::_loadSkel('skeleton/QueryRows', 'QueryRows', $mod)))
			return NULL;

		if ($mod == mapiHTTP::REQ || $mod == mapiHTTP::RESP)
			parent::Decode($xml, 'QueryRows');
		else {
			$usr = User::getInstance();

			$email = $usr->getVar('EMailPrime');
			$ou    = '/O=I638513D0/OU=EXCHANGE ADMINISTRATIVE GROUP (FYDIBOHF23SPDLT)/CN=';

			if (Debug::$Conf['Script']) //3
				$email = 'dummy@xxx.com'; //3

			if (!($val = $usr->getVar('DisplayName')))
				list($val,) = explode('@', $email);
			$xml->xpath('//Value[text()="##username"]');
			$xml->getItem();
			$xml->setVal($val);

			$xml->xpath('//Value[text()="##email"]');
			$xml->getItem();
			$val = $usr->getVar('AccountName');
			if (Debug::$Conf['Script']) //3
				$val = '010000xxxx000000-Debug'; //3
			$xml->setVal(strtoupper($ou.'RECIPIENTS/CN='.$val));

			$xml->xpath('//Value[text()="##msgdb"]');
			$xml->getItem();
			list(, $v) = explode('@', $email);
			$xml->setVal(strtoupper($ou.'CONFIGURATION/CN=SERVERS/'.
						 'CN='.mapiDefs::GUID['MessageDB'].'@'.$v.'/CN=MICROSOFT PRIVATE MDB'));

			if (!($val = $usr->xpath('EMailSec')))
				$val = $email;
			$xml->xpath('//Value[text()="##email-sec"]');
			$xml->getItem();
			$xml->setVal('SMTP:'.$val);

			if (!($val = $usr->getVar('SMTPLoginName')))
				$val = $email;
			$xml->xpath('//Value[text()="##smtp"]');
			$xml->getItem();
			$xml->setVal($email);

			$xml->setTop();
		}

		return $xml;
	}

}

?>
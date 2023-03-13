<?php
declare(strict_types=1);

/*
 * 	<GetMatches> handler class
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

class mapiGetMatches extends mapiWBXML {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var mapiGetMatches
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mapiGetMatches {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; request/response handler'), 'GetMatches'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse <GetMatches> request / response
	 *
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 * 	@return	- new XML structure or NULL
	 */
	public function Parse(int $mod): ?XML {

		// [MS-OXCMAPIHTTP] 2.2.5.5.1 GetMatches Request Type Request Body
		// [MS-OXCMAPIHTTP] 2.2.5.5.2 GetMatches Request Type Success Response Body
		// [MS-OXCMAPIHTTP] 2.2.5.5.3 GetMatches Request Type Failure Response Body
		// [MS-OXNSPI] 		2.2.8 <State>
		// [MS-OXNSPI] 		2.2.9.1 MinimalEntryID
		// [MS-OXCDATA] 	2.12 Restrictionions
		// [MS-OXCDATA] 	2.12.5 Property Restrictionion Structures
		// [MS-OXCMAPIHTTP] 2.2.1.8 <LargePropertyTagArray> Structure
		// [MS-OXCDATA] 	2.9 PropertyTag Structure
		// [MS-OXCPERM]		2.2.4 PidTagEntryId Property
		// [MS-OXNSPI] 		2.2.9.2 EphemeralEntryID
		// [MS-OXABK] 		2.2.3.12 PidTagDisplayTypeEx
		// [MS-OXCDATA] 	2.11.1 Property Data Types (mapiWBXML::DATA_TYP)
		// [MS-OXNSPI] 		2.2.1.2 Permitted Error Code Values
		// [MS-OXCDATA] 	2.4 Error Codes
		// [MS-OXCRPC] 		3.1.4.1.1 <AuxiliaryBuffer> Extended Buffer Handling
		// [MS-OXCRPC] 		3.1.4.1.1.1.1 rgbAuxIn Input Buffer

		// load skeleton
		if (!($xml = parent::_loadSkel('skeleton/GetMatches', 'GetMatches', $mod)))
			return NULL;

		if ($mod == mapiHTTP::REQ || $mod == mapiHTTP::RESP)
			parent::Decode($xml, 'GetMatches');
		else {
			$usr = User::getInstance();

			$email = $usr->getVar('EMailPrime');
			if (Debug::$Conf['Script']) //3
				$email = 'dummy@xxx.com'; //3
			list($val,) = explode('@', $email);
			$xml->xpath('//Value[text()="##username"]');
			$xml->getItem();
			$xml->setVal($val);

			if (!($val = $usr->getVar('SMTPLoginName')))
				$val = $email;

			$xml->xpath('//Value[text()="##smtp"]');
			$xml->getItem();
			$xml->setVal($val);
			$xml->getItem();
			$xml->setVal($val);

			$xml->xpath('//Value[text()="##dn"]');
			$xml->getItem();
			$val = $usr->getVar('AccountName');
			if (Debug::$Conf['Script']) //3
				$val = '010000xxxx000000-Debug'; //3
			$val = '/O=I638513D0/OU=EXCHANGE ADMINISTRATIVE GROUP (FYDIBOHF23SPDLT)/CN=RECIPIENTS/CN='.$val;
			$xml->setVal(strtoupper($val), FALSE);

			$xml->setTop();
		}

		return $xml;
	}

}

?>
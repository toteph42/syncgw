<?php
declare(strict_types=1);

/*
 * 	<GetSpecialTable> handler class
 *
 *	@package	sync*gw
 *	@subpackage	MAPI over HTTP support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi;

use syncgw\lib\User;
use syncgw\lib\XML;

class mapiGetSpecialTable extends mapiWBXML {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var mapiGetSpecialTable
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mapiGetSpecialTable {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; request/response handler'), 'GetSpecialTable'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse <GetSpecialTable> request / response
	 *
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 * 	@return	- new XML structure or NULL
	 */
	public function Parse(int $mod): ?XML {

		// [MS-OXCMAPIHTTP] 2.2.5.8.1 GetSpecialTable Request Type Request Body
		// [MS-OXCMAPIHTTP] 2.2.5.8.2 GetSpecialTable Request Type Success Response Body
		// [MS-OXCMAPIHTTP] 2.2.5.8.3 GetSpecialTable Request Type Failure Response Body
		// [MS-OXNSPI] 		2.2.8 <State>
		// [MS-OXOABK]		2.2.2 Properties that Apply to Containers in the Address Book Hierarchy Table
		// [MS-OXNPSI]		2.2.9.3 PermanentEntryID
		// [MS-OXCMAPIHTTP] 2.2.1.3 <AddressBookPropertyValueList> Structure
		// [MS-OXCMAPIHTTP] 2.2.1.2 <AddressBookTaggedPropertyValue> Structure
		// [MS-OXCMAPIHTTP] 2.2.1.1 <AddressBookPropertyValue> Structure
		// [MS-OXCDATA] 	2.11.1 Property Data Types (mapiHTTP::DATA_TYP)
		// [MS-OXCRPC] 		3.1.4.1.1 <AuxiliaryBuffer> Extended Buffer Handling
		// [MS-OXCRPC] 		3.1.4.1.1.1.1 rgbAuxIn Input Buffer

		// load skeleton
		if (!($xml = parent::_loadSkel('skeleton/GetSpecialTable', 'GetSpecialTable', $mod)))
			return NULL;

		if ($mod == mapiHTTP::REQ || $mod == mapiHTTP::RESP)
			parent::Decode($xml, 'GetSpecialTable');
		else {
			$usr = User::getInstance();
			$xml->xpath('//ProviderUID[text()="NPSI"]/..');
			$xml->getItem();
			$xml->getItem();
			$g = explode('-', mapiDefs::GUID['User']);
			if (($gid = $usr->getVar('LUID')) > 0xffff) {
				$g[2] = sprintf('%04X', $gid / 0xffff);
				$gid %= 0xffff;
			} else
				$g[2] = '0000';
			$g[1] = sprintf('%04X', $gid);
			$xml->updVar('DN', '/guid='.implode('', $g), FALSE);

			$xml->xpath('//PropertyID[text()="DisplayName"]/../Property/Value');
			$xml->getItem();
			$xml->getItem();
			$val = $usr->getVar('DisplayName');
			if (!$val) //3
				$val = 'Dummy user'; //3
			$xml->setVal($val);
		}

		return $xml;
	}

}

?>
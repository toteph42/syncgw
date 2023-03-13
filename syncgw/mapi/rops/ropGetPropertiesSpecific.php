<?php
declare(strict_types=1);

/*
 * 	<RopGetPropertiesSpecific> handler class
 *
 *	@package	sync*gw
 *	@subpackage	Remote Operations (ROP) List and Encoding Protocol
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\mapi\rops;

use syncgw\lib\Debug; //3
use syncgw\lib\User;
use syncgw\lib\XML;
use syncgw\mapi\mapiHTTP;
use syncgw\mapi\mapiWBXML;
use syncgw\mapi\mapiDefs;

class ropGetPropertiesSpecific extends mapiWBXML {

	// module version number
	const VER = 1;

	// known properties
	const TAGS = [
		'MailboxOwnerName',
		'MailboxOwnerEntryId',
		// 'ServerTypeDisplayName',
		// 'ServerConnectedIcon',
		// 'ServerAccountIcon',
		'MaximumSubmitMessageSize',
		'LocalCommitTimeMax',
		'DeletedCountTotal',
		'ContentUnreadCount',
		'IpmAppointmentEntryId',
		'IpmContactEntryId',
		'IpmJournalEntryId',
		'IpmNoteEntryId',
		'IpmTaskEntryId',
		'RemindersOnlineEntryId',
		// '##0x7C04',
	];

    /**
     * 	Singleton instance of object
     * 	@var RopGetPropertiesSpecific
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): RopGetPropertiesSpecific {

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

		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; response handler'), 'RopGetPropertiesSpecific'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse Rop request / response
	 *
	 *	@param 	- XML request document or binary request body
	 *	@param 	- XML response document
	 *	@param	- mapiHTTP::REQ = Decode request; mapiHTTP::RESP = Decode response; mapiHTTP::MKRESP = Create response
	 *	@return - TRUE = Ok; FALSE = Error
	 */
	public function Parse(&$req, XML &$resp, int $mod): bool {

		// [MS-OXCROPS]		2.2.1 ROP Input and Output Buffers
		// [MS-OXCRPC] 		2.2.2.1 RPC_HEADER_EXT Structure
		// [MS-OXCROPS]		2.2.8.3.1 RopGetPropertiesSpecific ROP Request Buffer
		// [MS-OXCROPS] 	2.2.8.3.2 RopGetPropertiesSpecific ROP Success Response Buffer
		// [MS-OXCDATA]		2.8.1.2 FlaggedPropertyRow Structure
		// [MS-OXCDATA] 	2.9 PropertyTag Structure
		// [MS-OXCDATA] 	2.11.1 Property Data Types (mapiHTTP::DATA_TYP)
		// [MS-OXNSPI] 		2.2.1.2 Permitted Error Code Values
		// [MS-OXCDATA] 	2.2.1.3 Global Identifier Structure

		if ($mod == mapiHTTP::MKRESP) {

			// set <ServerObjectHandleTable><Object>
			$req->xpath('//RopId[text()="GetPropertiesSpecific"]/..');
			$req->getItem();
			$ip = $req->savePos();

			$id = $req->getVar('InputHandleIndex', FALSE);
			$p  = $resp->savePos();
			$resp->xpath('//RopId[text()="GetPropertiesSpecific"]/../InputHandleIndex');
			$resp->getItem();
			$resp->setVal($id);
			$resp->restorePos($p);

			// check for properties
			$req->xpath('//PropertyTag');
			$tags = [];
			$err  = FALSE;
			while ($req->getItem() !== NULL) {

				$p = $req->savePos();
				$typ = $req->getVar('PropertyType', FALSE);
				$req->restorePos($p);
				$id = $req->getVar('PropertyId', FALSE);
				$req->restorePos($p);

				if (in_array($id, self::TAGS))
					$tags[$id] = $typ;
				else {
					$tags[$id] = -1;
					$err = TRUE;
				}
			}

			$resp->getVar('PropertyRow');
			$resp->addVar('Flag', $err ? 'Flagged' : 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
			$resp->addVar('ValueArray');

			// return values
			foreach ($tags as $id => $typ) {

				$p = $resp->savePos();
				$resp->addVar('PropetyValue', NULL, FALSE, [ 'Tag' => $id ]);

				// error?
				if ($typ == -1) {
					$resp->addVar('Flag', 'Error', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Value', 'NotFound', FALSE, [ 'T' => 'I', 'S' => '4', 'D' => 'ERR_CODE' ]);
					$resp->restorePos($p);
					continue;
				}

				switch ($id) {
				case 'MailboxOwnerName':
					$usr = User::getInstance();
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					if (!($val = $usr->getVar('EMailPrime')))
						$val = '';
					if (Debug::$Conf['Script']) //3
						$val = 'dummy@xxx.com';
					// typ: 'S'
					$resp->addVar('Value', parent::_putData($resp, $val, $typ), FALSE, [ 'T' => $typ ]);
					break;

				case 'MailboxOwnerEntryId':
					$usr = User::getInstance();
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$val = $usr->getVar('AccountName');
					if (Debug::$Conf['Script']) //3
						$val = '010000xxxx000000-Debug'; //3
					$g = '';
					foreach (explode('-', mapiDefs::GUID['NPSI']) as $t) {
						for ($i=strlen($t) - 2; $i >= 0; $i-=2)
							$g .= substr($t, $i, 2);
					}
					$ou  = '/O=I638513D0/OU=EXCHANGE ADMINISTRATIVE GROUP (FYDIBOHF23SPDLT)/CN=RECIPIENTS/CN=';
					$val = '00000000'.$g.'0100000000000000'.bin2hex(strtoupper($ou.$val)).'00';
					$resp->addVar('Count', strval(strlen($val) / 2), FALSE, [ 'T' => 'I', 'S' => '2' ]);
					// typ: 'H'
					$resp->addVar('Value', $val, FALSE, [ 'T' => $typ, 'S' => 'Count' ]);
					break;

				case 'LocalCommitTimeMax':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$d = new \DateTime();
					$resp->addVar('Value', $d->format('Y-m-d H:i:s'), FALSE, [ 'T' => $typ ]);
					break;

				case 'DeletedCountTotal':
				case 'ContentUnreadCount':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Value', '0', FALSE, [ 'T' => $typ ]);
					break;

				case 'MaximumSubmitMessageSize':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Value', '67108864', FALSE, [ 'T' => $typ ]);
					break;

				case 'IpmAppointmentEntryId':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Count', '46', FALSE, [ 'T' => 'I', 'S' => '2' ]);
					$resp->addVar('Value',
								  '0000000015f3a97abbe0c243a6c2a406c292e8a5010001000000a5187b6fbcdcea1ed03c565700000000000f0000',
								  FALSE, [ 'T' => 'H', 'S' => '46' ]);
					break;

				case 'IpmContactEntryId':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Count', '46', FALSE, [ 'T' => 'I', 'S' => '2' ]);
					$resp->addVar('Value',
								  '0000000015f3a97abbe0c243a6c2a406c292e8a5010001000000a5187b6fbcdcea1ed03c56570000000000130000',
								  FALSE, [ 'T' => 'H', 'S' => '46' ]);
					break;

				case 'IpmNoteEntryId':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Count', '46', FALSE, [ 'T' => 'I', 'S' => '2' ]);
					$resp->addVar('Value',
								  '0000000015f3a97abbe0c243a6c2a406c292e8a5010001000000a5187b6fbcdcea1ed03c56570000000000110000',
								  FALSE, [ 'T' => 'H', 'S' => '46' ]);
					break;

				case 'IpmTaskEntryId':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Count', '46', FALSE, [ 'T' => 'I', 'S' => '2' ]);
					$resp->addVar('Value',
								  '0000000015f3a97abbe0c243a6c2a406c292e8a5010001000000a5187b6fbcdcea1ed03c56570000000000120000',
								  FALSE, [ 'T' => 'H', 'S' => '46' ]);
					break;

				case 'IpmJournalEntryId':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Count', '46', FALSE, [ 'T' => 'I', 'S' => '2' ]);
					$resp->addVar('Value',
								  '0000000015f3a97abbe0c243a6c2a406c292e8a5010001000000a5187b6fbcdcea1ed03c56570000000000100000',
								  FALSE, [ 'T' => 'H', 'S' => '46' ]);
					break;

				case 'RemindersOnlineEntryId':
					if ($err)
						$resp->addVar('Flag', 'Implied', FALSE, [ 'T' => 'I', 'S' => '1', 'D' => 'VALUE_TYP' ]);
					$resp->addVar('Count', '46', FALSE, [ 'T' => 'I', 'S' => '2' ]);
					$resp->addVar('Value',
								  '0000000015f3a97abbe0c243a6c2a406c292e8a5010001000000a5187b6fbcdcea1ed03c56570000000001020000',
								  FALSE, [ 'T' => 'H', 'S' => '46' ]);
					break;

				default:
					break;
				}
				$resp->restorePos($p);
			}
			$req->restorePos($ip);
		}

		return TRUE;
	}

}

?>
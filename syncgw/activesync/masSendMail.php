<?php
declare(strict_types=1);

/*
 * 	<SendMail> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\document\field\fldConversationId;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\HTTP;
use syncgw\lib\User;
use syncgw\lib\XML;

class masSendMail {

	// module version number
	const VER 			 = 6;

   /**
     * 	Singleton instance of object
     * 	@var masSendMail
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masSendMail {

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

		$xml->addVar('Opt', '<a href="https://learn.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-ascmd" target="_blank">[MS-ASCMD]</a> '.
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'SendMail'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Parse XML node
	 *
	 * 	@param	- Input document
	 * 	@param	- Output document
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function Parse(XML &$in, XML &$out): bool {

		Debug::Msg($in, '<SendMail> input'); //3

		$out->addVar('SendMail', NULL, FALSE, $out->setCP(XML::AS_COMPOSE));

		$db  = DB::getInstance();
		$mas = masHandler::getInstance();

		// return status
		$rc = 0;

		// a required child element of the <SendMail> element in SendMail command requests
		// it specifies the client's unique message ID (MID)
		$mid = $in->getVar('ClientId');

		// optional child element of command requests. it identifies the account from which an email is sent
		if ($uid = $in->getVar('AccountId')) {
			// we use <AccountId> as <GUID>
			if (!($usr = $db->Query(DataStore::USER, DataStore::RGID, $uid)))
				$rc = masStatus::ACCID;
		} else
			$usr = User::getInstance();

		// does account support sending mails?
		if ($usr->getVar('SendDisabled'))
			$rc = masStatus::ACCSEND;

		// optional child element. specifies whether a copy of the message will be stored in the Sent Items folder
		$save = $in->getVar('SaveInSentItems') !== NULL;
		if ($mas->callParm('Options') == 'SaveInSent')
			$save = TRUE;

		// @todo <TemplateID> - RM - Rights Management
		// contains a string that identifies a particular rights policy template to be applied to the outgoing message

		// use different account id?
		if (!$rc && $uid) {
			$http = HTTP::getInstance();
			if (!$db->Authorize($uid, $http->getHTTPVar('Password')))
				$rc = masStatus::ACCID;
		}

		if (!$rc) {

			// required child element. it contains the MIME-encoded message
			if (($doc = $db->SendMail($save, str_replace("\n", "\r\n", $in->getVar('Mime')))) == NULL)
				$rc = masStatus::SUBMIT;
			else {
				$doc->getVar('Data');
				$doc->addVar(fldConversationId::TAG, $mid);
				if (!$db->Query(DataStore::EXT|DataStore::MAIL, DataStore::ADD, $doc))
					$rc = masStatus::SERVER;
				else {
					// set status 200 - ok
					$mas->setStat(masHandler::STOP);
					return TRUE;
				}
			}
		}
		$out->addVar('Status', $rc);

		$out->getVar('SendMail'); //3
		Debug::Msg($out, '<SendMail> output'); //3

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

		if (isset(masStatus::STAT[$rc])) //2
			return masStatus::STAT[$rc]; //2
		return 'Unknown return code "'.$rc.'"'; //2
	} //2

}

?>
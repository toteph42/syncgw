<?php
declare(strict_types=1);

/*
 * 	<Autodiscover> handler class
 *
 *	@package	sync*gw
 *	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\lib\Config;
use syncgw\lib\HTTP;
use syncgw\lib\Log;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class masAutodiscover {

	// module version number
	const VER 	  = 8;

	// status codes
	const PROT	  = '2';
	const REQ	  = '600';
	const PROV	  = '601';

	// status description
	const STAT    = [ //2
		self::PROT		=> 'Protocol error', //2
		self::REQ		=> 'Invalid request was sent to the server', //2
		self::PROV		=> 'Provider could not be found to handle the AcceptableResponseSchema element', //2
	]; //2

    /**
     * 	Singleton instance of object
     * 	@var masAutodiscover
     */
    static private $_obj = NULL;

    /**
     * 	MAPI flag
     * 	@var bool
     */
    private $_mapi = FALSE;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masAutodiscover {

	   	if (!self::$_obj) {

            self::$_obj = new self();

			// 16301-16400 message codes
			$log = Log::getInstance();
			$log->setMsg( [
					16301 => _('Client does not support "MAPI Extensions for HTTP" - please upgrade to Outlook 2013 SP1 or higher'),
			]);
		}

		// check for MAPI
		self::$_obj->_mapi = file_exists(Util::mkPath('mapi'));

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
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'Autodiscover'));
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

		Debug::Msg($in, '<Autodiscover> input'); //3

		$http = HTTP::getInstance();
		$cnf  = Config::getInstance();
		$usr  = User::getInstance();

		// disable WBXML conversion
		$http->addHeader('Content-Type', 'text/xml');

		// build uri
		$server = $http->getHTTPVar('SERVER_NAME');
		if (($uri = $http->getHTTPVar('HTTPS')) && $uri != 'off')
			$uri = 'https://';
		else
			$uri = 'http://';

		$uri .= $server;

		// https://docs.microsoft.com/en-us/exchange/client-developer/web-service-reference/response-pox
		$rs = $in ? $in->getVar('AcceptableResponseSchema') : '';

		// single command execution - we assume schema
		if (!$rs)
			$rs = 'http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006';

		// [MS-ASCMD] Schema is: https://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006

		// facilitates the discovery of core account configuration information by using the user's SMTP address as the primary input.
		// the value of the schema MUST be "http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006"
		$out->addVar('Autodiscover', NULL, FALSE, [ 'xml-ns:xsd' => 'http://www.w3.org/2001/XMLSchema',
												 	'xml-ns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
													'xml-ns' 	 => 'http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006' ]);

		// [MS-OXDSCLI] Schema is: https://schemas.microsoft.com/exchange/autodiscover/outlook/requestschema/2006a
		if (strpos($rs, '2006a') !== FALSE) {

			// <Response> (Required)
			$out->addVar('Response', NULL, FALSE, [ 'xml-ns' => $rs ]);

			// default return code
			$rc = masStatus::OK;

			// <Request> contains the <Autodiscover> command request parameters - ignored

			// <EMailAddress> contains the SMTP email address of the user and is used to identify the user's
			// mailbox in the network
			if (!($email = $in->getVar('EMailAddress')))
				if (!($email = $usr->getVar('EMailPrime')))
					// <LegacyDN> identifies a user's mailbox by a legacy distinguished name (DN).
					$email = $in->getVar('LegacyDN');

			// X-ClientCanHandle a comma-delimited list of capabilities that the client supports.

			// X-MapiHttpCapability is an optional header used in Autodiscover requests to indicate support for
			// the Messaging Application
			if (!($mapi = $http->getHTTPVar('X-Mapihttpcapability')))
				$this->_mapi = FALSE;

			// The X-AnchorMailbox header identifies the email address of the account for which the
			// configuration information will be retrieved
			$http->addHeader('X-AnchorMailbox', $email);

			// get user id
			if ($cnf->getVar(Config::DBG_LEVEL) != Config::DBG_OFF //2
				&& !($cnf->getVar(Config::TRACE) & Config::TRACE_FORCE) //3
				) //2
				$uid = $cnf->getVar(Config::DBG_USR); //2
			else //2
				$uid = $email;

			// load user id
			if (!$usr->loadUsr($uid)) {
				$out->setParent();
				// <Error> contains an Autodiscover error response.
				//   Time= 	Represents the time when the error response was returned.
				//   Id=	Represents a hash value of the name of the mail server.
				$out->addVar('Error', NULL, FALSE, [ 'Time' => gmdate('H:i:s.\0\0\0', time()), 'Id' => $http->getHTTPVar('SERVER_NAME') ]);
				// <Message> contains the error message for an error Autodiscover response.
				$out->addVar('Message', _('The user\'s account is disabled'));
				// <DebugData> contains the debug data for an Autodiscover error response.
				$out->addVar('DebugData', _('User may be <Banned>'));
				// <ErrorCode> contains the error code for an error Autodiscover response.
				$out->addVar('ErrorCode', self::PROV);

				$out->getVar('Autodiscover'); //3
				Debug::Msg($out, '<Autodiscover> output'); //3

				return TRUE;
			}

			// -----------------------------------------------------------------------------------------------------------
			// (Optional) User-specific information. Servers MUST include this element if the server does not need to
			// redirect the request and encounters no errors.
			$op = $out->savePos();
			$out->addVar('User');

			$out->addVar('EMailAddress', $email);

			// (Optional) Represents the user's display name
			$out->addVar('DisplayName', $usr->getVar('DisplayName'));
			// (Required) identifies a user's mailbox by a legacy distinguished name (DN)
			$out->addVar('LegacyDN', '/o=sync*gw/ou=Users/cn=Recipients/cn='.$usr->getVar('LUID'));
			// (Optional) Contains the user's SMTP address that is used for the Autodiscover process
			$out->addVar('AutoDiscoverSMTPAddress', $email); //3

			// is returned when the user is within a server forest. The returned value is the GUID
			// identifier of the Active Directory forest in which the mailbox user account is contained.
			$out->addVar('DeploymentId', Util::WinGUID(FALSE)); //3

			// -----------------------------------------------------------------------------------------------------------
			// (Required) account settings for the user
			$out->restorePos($op);
			$out->addVar('Account');

			// Required) represents the account type.
			$out->addVar('AccountType', 'email');

			// <(Required) provides information that is used to determine whether another Autodiscover request
			// is required to return the user configuration information.
			// The Autodiscover server has returned configuration settings in the Protocol element
			$out->addVar('Action', 'settings');

			// (Optional) specifies whether the user account is an online account.
			$out->addVar('MicrosoftOnline', 'False');

			// (Optional) specifies whether the user account is a consumer mailbox.
			$out->addVar('ConsumerMailbox', 'False');
            $op = $out->savePos();

			// -----------------------------------------------------------------------------------------------------------
			// Messaging Application Programming Interface (MAPI) Extensions for HTTP
			// contains the configuration information for connecting a client to the server.
			//	Type=	  Indicates the type of protocol described by this Protocol element. The only valid value for
			// 			  this attribute is "mapiHttp". This attribute is only present if the Autodiscover request that
			// 			  corresponds to this response included an X-MapiHttpCapability header.
			//	Version=  Indicates the version of the protocol described by this Protocol element. The only valid value
			// 			  for this attribute is "1". This attribute is only present if the Autodiscover request that
			// 			  corresponds to this response included an X-MapiHttpCapability header. This attribute is
			// 			  applicable to clients that implement the MAPI/HTTP protocol and target Exchange Online,
			// 			  Exchange Online as part of Office 365, or on-premises versions of Exchange starting with
			// 			  build 15.00.0847.032 (Exchange Server 2013 SP1).
			if ($this->_mapi) {
				$out->addVar('Protocol', NULL, FALSE, [ 'Type' => 'mapiHttp', 'Version' => $mapi ]);

		        // The MailStore element contains information that the client can use to connect to a mailbox
		        $p1 = $out->savePos();
				$out->addVar('MailStore');
				$out->addVar('ExternalUrl', $uri.'/mapi/emsmdb/?MailboxId='.base64_encode($email));
				$out->restorePos($p1);

				// The AddressBook element contains information that the client can use to connect to an NSPI server
				// $p1 = $out->savePos();
				$out->addVar('AddressBook');
				$out->addVar('ExternalUrl', $uri.'/mapi/nspi/?MailboxId='.base64_encode($email));
				// $out->restorePos($p1);
				$out->restorePos($op);
			}
			// specifies whether the client uses the connection information contained in the parent Protocol
			// element first when the client attempts to connect to the server
            $out->addVar('ServerExclusiveConnect', 'On'); //3

			// Indicates that the client SHOULD use basic authentication, as specified in [RFC2617].
            $out->addVar('AuthPackage', 'basic'); //3
			$out->restorePos($op); //3

           	// -----------------------------------------------------------------------------------------------------------
			if ($val = $cnf->getVar(Config::SMTP_HOST)) {
				$out->restorePos($op);
				$out->addVar('Protocol');
				$out->addVar('Type', 'SMTP');
				$out->addVar('Server', $val);
				$out->addVar('Port', strval($cnf->getVar(Config::SMTP_PORT)));
				$out->addVar('DomainRequired', 'off');
				$out->addVar('LoginName', $usr->getVar('SMTPLoginName'));
				$out->addVar('SPA', 'off');

				// <UsePOPAuth> (Optional) Indicates whether the authentication information that is provided for a POP3 type of
				// account is also used for SMTP.
				$out->addVar('UsePOPAuth', 'off');

				// <SMTPLast> (Optional) Default is off. Specifies whether the Simple Mail Transfer Protocol (SMTP) server
				// requires that email be downloaded before it sends email by using the SMTP server.
				$out->addVar('SMTPLast', 'off');

				$out->addVar('AuthRequired', 'on');

				if ($val = $cnf->getVar(Config::SMTP_ENC))
					$out->addVar('Encryption', $val);
			}

			// -----------------------------------------------------------------------------------------------------------
			if ($val = $cnf->getVar(Config::IMAP_HOST)) {
				$out->restorePos($op);

				// $out->restorePos($op);
				$out->addVar('Protocol');
				// <Type> (Required) identifies the type of the configured mail account.
				$out->addVar('Type', 'IMAP');

				// <ExpirationDate> (Optional) The value here specifies the last date which these settings should be used.
				// After that date, the settings should be rediscovered via Autodiscover again. If no value is specified,
				// the default will be no expiration.

				// <TTL> (Optional) specifies the time, in hours, during which the settings remain valid. It

				// <Server> (Required) specifies the name of the mail server.
				$out->addVar('Server', $val);

				// <Port> (Optional) specifies the port that is used to connect to the message store
				$out->addVar('Port', strval($cnf->getVar(Config::IMAP_PORT)));

				// <DomainRequired> (Optional) Default is off. If this value is true, then a domain is required during
				// authentication.  If the domain is not specified in the LOGINNAME tag, or the LOGINNAME tag was not
				// specified, the user will need to enter the domain before authentication will succeed.
				$out->addVar('DomainRequired', 'off');

				// <LoginName> (Optional) This value specifies the user's login. If no value is specified, the default will
				// be set to the string preceding the '@' in the email address.  If the Login name contains a domain,
				// the format should be <Username>@<Domain>.  Such as JoeUser@SalesDomain.
				$out->addVar('LoginName', $usr->getVar('IMAPLoginName'));

				// <DomainName> specifies the user's domain.

				// <SPA> (Optional) indicates whether secure password authentication is required. If unspecified, the default
				// is set to on.
				$out->addVar('SPA', 'off');

				// <AuthRequired> (Optional) specifies whether authentication is required. If unspecified,
				// the default is set to on.
				$out->addVar('AuthRequired', 'on');

				// <Encryption> specifies the required encryption for the connection to the server.
				if ($val = $cnf->getVar(Config::IMAP_ENC))
					$out->addVar('Encryption', $val);
			}

			$out->getVar('Autodiscover'); //3
			Debug::Msg($out, '<Autodiscover> output'); //3

			return TRUE;
		}

		// default return code
		$rc = masStatus::OK;

		// <Request> contains the <Autodiscover> command request parameters - ignored

		// <EMailAddress> contains the SMTP email address of the user and is used to identify the user's mailbox in the network
		if (!($email = $in->getVar('EMailAddress')))
			if (!($email = $usr->getVar('EMailPrime')))
				$email = $http->getHTTPVar('User');

		// load user id
		if (!$email || !$usr->loadUsr($email)) {
			$out->addVar('Error');
			$out->addVar('Status', masStatus::USER);
			$out->addVar('Message', sprintf(_('The user "%s" was not found in the directory service'), $email));
			$out->addVar('DebugData', _('User may be banned - please contact your administrator'));
			$out->addVar('ErrorCode', self::REQ);
			$out->getVar('Autodiscover'); //3
			Debug::Msg($out, '<Autodiscover> output'); //3
			return TRUE;
		}

		// load additional e-mail addresses
		$mail   = [];
		$usr->xpath('//EMail');
		while ($val = $usr->getItem())
			$mail[] = $val;

		// It contains the Autodiscover command response parameters
		$out->addVar('Response', NULL, FALSE,
					 [ 'xml-ns' => 'http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006' ]);

		// specifies the client culture, which is used to localize error messages, e.g. "en:us"
		list(, $v) = explode(';', $cnf->getVar(Config::LANG));
		$out->addVar('Culture', strtolower(str_replace('_', ':', $v)));

		// encapsulates information about the user to whom the Response element relates
		$out->addVar('User');

		// contains the user's display name in the directory service
		if ($val = $usr->getVar('DisplayName'))
			$out->addVar('DisplayName', $val);

		// contains the SMTP email address of the user and is used to identify the user's mailbox in the network
		$out->addVar('EMailAddress', $email);
		$out->setParent();

		// encapsulates the server action type for the request
		$out->setParent();
		$out->addVar('Action', NULL, FALSE, [ 'Time' => gmdate('H:i:s.\0\0\0', time()), 'Id' => $http->getHTTPVar('SERVER_NAME') ]);

		// <Redirect> specifies the SMTP address of the requested user
		if (count($mail))
			// we take the first e-mail in list as alternative
			$out->addVar('Redirect', $email);

		// Because the <Status> element is only returned when the command encounters an error, the success
		// status code is never included in a response message
		if ($rc == masStatus::OK) {
			$out->addVar('Settings');
			$out->addVar('Server');
			// the URL that is returned by the URL element can be accessed by clients
			$out->addVar('Type', 'MobileSync');

			$uri = $uri.'/Microsoft-Server-ActiveSync';
			$out->addVar('Url', $uri);
			$out->addVar('Name', $uri);

			// <ServerData> - the template name for the client certificate
			// It is a string value that is present only when the <Type> element value is set to "CertEnroll".
		}

		$out->getVar('Autodiscover'); //3
		Debug::Msg($out, '<Autodiscover> output'); //3

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
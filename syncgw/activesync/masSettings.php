<?php
declare(strict_types=1);

/*
 * 	<Settings> handler class
 *
 * 	@package	sync*gw
 * 	@subpackage	ActiveSync support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\activesync;

use syncgw\lib\Debug; //3
use syncgw\lib\Device;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Log;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class masSettings extends XML {

	// module version number
	const VER 			 = 12;

	// status codes
	const PROTOCOL  	 = '2';
	const ACCESS    	 = '3';
	const SERVER		 = '4';
	const ARGS			 = '5';
	const CONFLICT		 = '6';
	const DENIED		 = '7';

	// status description
	const STAT1     	 = [ //2
		self::PROTOCOL	 => 'Protocol error', //2
    	self::ACCESS	 => 'Access denied', //2
	    self::SERVER	 => 'Server unavailable', //2
		self::ARGS		 => 'Invalid arguments', //2
    	self::CONFLICT	 => 'Conflicting arguments', //2
	    self::DENIED	 => 'Denied by policy', //2
    ]; //2

    const OOF_DIS   	 = '0';
	const OOF_GLOB  	 = '1';
	const OOF_TIME  	 = '2';

	// status description
	const STAT2     	 = [ //2
    	self::OOF_DIS    => 'Oof is disabled', //2
    	self::OOF_GLOB   => 'Oof is global', //2
    	self::OOF_TIME   => 'Oof is time-based', //2
	]; //2

    /**
     * 	Singleton instance of object
     * 	@var masSettings
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): masSettings {

	   	if (!self::$_obj) {
            self::$_obj = new self();

			// set messages 16201-16300
			$log = Log::getInstance();
			$log->setMsg( [
					16201 => _('Error loading [%s]'),
			]);

			$file = Util::mkPath('source/masRights.xml');
			if (!self::$_obj->loadFile($file))
				ErrorHandler::Raise(16201, $file);
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

		$xml->addVar('Opt', '<a href="https://learn.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-ascmd" target="_blank">[MS-ASCMD]</a> '.
				      sprintf(_('Exchange ActiveSync &lt;%s&gt; handler'), 'Settings'));
		$xml->addVar('Ver', strval(self::VER));

		$xml->addVar('Opt', _('Exchange ActiveSync right management settings'));
		parent::getVar('Rigths');
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

		Debug::Msg($in, '<Settings> input'); //3

		$dev = Device::getInstance();
		$usr = User::getInstance();
		$mas = masHandler::getInstance();
		$ver = $mas->callParm('BinVer');

		// The Settings command supports get and set operations on global properties and Out of Office (OOF) settings for the user.
		// The Settings command also sends device information to the server, implements the device password/personal identification
		// number (PIN) recovery, and retrieves a list of the user's email addresses
		$out->addVar('Settings', NULL, FALSE, $out->setCP(XML::AS_SETTING));
		$out->addVar('Status', masStatus::OK);

		// specifies a named property node for retrieving and setting Out of Office (OOF) information.
		if ($in->getVar('Oof') !== NULL) {

		    $ip = $in->savePos();

       		$out->addVar('Oof');
	        $out->addVar('Status', masStatus::OK);

		    // <Set>
			if ($in->getVar('Set', FALSE) !== NULL) {

			    $ip1 = $in->savePos();

				// <OofState> specifies the availability of the Oof property
				// 0 The Oof property is disabled.
				// 1 The Oof property is global.
				// 2 The Oof property is time-based.
				$mod = $in->getVar('OofState', FALSE);

 				// <StartTime> specify the range of time during which the user is out of office.
				$in->restorePos($ip1);
				if ($slot = $in->getVar('StartTime', FALSE)) {
					// <EndTime> specify the range of time during which the user is out of office
					$in->restorePos($ip1);
					$slot = Util::unxTime($slot).'/'.Util::unxTime($in->getVar('EndTime', FALSE));
				}

				$in->restorePos($ip1);

				// <OofMessage> specifies the OOF message for a particular audience.
				$in->xpath('OofMessage', FALSE);
			    while ($in->getItem() !== NULL) {

     				$ip1 = $in->savePos();
    				$au = '1';

    				// <AppliesToInternal> indicates that the OOF message applies to internal user
    				$in->restorePos($ip1);
    				if ($in->getVar('AppliesToInternal', FALSE) !== NULL)
    					$au = '1';
    				else {
    					// <AppliesToExternalKnown> indicates that the OOF message applies to known external users
	    				$in->restorePos($ip1);
	    				if ($in->getVar('AppliesToExternalKnown', FALSE) !== NULL)
	    					$au = '2';
	    				else {
	    					// <AppliesToExternalUnknown> indicates that the OOF message applies to unknown external users
		    				$in->restorePos($ip1);
	    					if ($in->getVar('AppliesToExternalUnknown', FALSE) !== NULL)
	    						$au = '3';
	    				}
    				}

  					// <Enabled> specifies whether an OOF message is sent to this audience while the sending user is OOF

       				// <BodyType> specifies the format of the OOF message (Text / HTML)
    				$in->restorePos($ip1);
    				$typ = $in->getVar('BodyType');

    				// <ReplyMessage> specifies the message to be shown to a particular audience when the user is OOF.
    				$in->restorePos($ip1);
    				if (($val = $in->getVar('ReplyMessage', FALSE)) !== NULL) {
    					if ($typ == 'Text')
	    					$usr->setOOF($mod == '0' ? 2 : 1, intval($au), $slot, $val);
    					else
	    					$usr->setOOF($mod == '0' ? 2 : 1, intval($au), $slot, NULL, $val);
    				}

    				$in->restorePos($ip1);
			    }
			}
            $in->restorePos($ip);

			$op = $out->savePos();

            // <Get>
			if ($in->xpath('Get', FALSE)) {

				$out->addVar('Get');

				// type to load (TEXT/HTML)
				$typ = strtoupper($in->getVar('BodyType', FALSE));

				if ($usr->getVar('OutOfOffice') === NULL) {
					$usr->setOOF(2, 1);
				}

				$usr->xpath('OutOfOffice/Time');
				while (($val = $usr->getItem()) !== NULL) {
					if ($stat = $usr->getVar('State', FALSE)) {
						if ($val)
							// The Oof property is time-based.
							$out->addVar('OofState', '2');
						else
							// The Oof property is global.
							$out->addVar('OofState', '1');
					} else
						// The Oof property is disabled.
							$out->addVar('OofState', '0');
					if ($val) {
						list($s, $e) = explode('/', $val);
						$out->addVar('StartTime', gmdate(Util::masTIME, intval($s)));
						$out->addVar('EndTime', gmdate(Util::masTIME, intval($e)));
					}

					$usr->xpath('Message', FALSE);

					while ($usr->getItem() !== NULL) {

						$up  = $usr->savePos();
						$op1 = $out->savePos();

						$out->addVar('OofMessage');

						switch ($usr->getVar('Audience', FALSE)) {
						case '1':
							$out->addVar('AppliesToInternal', '');
							break;

						case '2':
							$out->addVar('AppliesToExternalKnown', '');
							break;

						case '3':
							$out->addVar('AppliesToExternalUnknown', '');
							break;
						}

						$usr->restorePos($up);
						$out->addVar('Enabled', $stat ? '1' : '0');

						// get text message
						$usr->restorePos($up);
						if ($val = $usr->getVar('Text')) {
							$out->addVar('ReplyMessage', $val);
							$out->addVar('BodyType', $usr->getAttr('TYP'));
						}

						$usr->restorePos($up);
						$out->restorePos($op1);
					}
				}
			}

            $in->restorePos($ip);
			$out->restorePos($op);
		}

		// <DevicePassword> is used to send the recovery password of the device to the server
		if ($in->getVar('DevicePassword') !== NULL) {
			// <Set>
			// <Password> specifies the recovery password of the device, which is stored by the server
			$val = $in->getVar('Password', FALSE);
    		$dev->updVar('Password', base64_encode($val));

    		$op = $out->savePos();
    		$out->addVar('DevicePassword');
    		$out->addVar('Status', masStatus::OK);
    		$out->restorePos($op);
		}


		// <DeviceInformation> is used for sending the device's properties to the server
		if ($in->getVar('DeviceInformation') !== NULL) {

			// <Set>
			// <Model> specifies a name that generally describes the device of the client
			if ($val = $in->getVar('Model'))
    			$dev->updVar('Model', $val);

			// <IMEI> specifies a 15-character code that MUST uniquely identify a device
			if ($val = $in->getVar('IMEI'))
    			$dev->updVar('IMEI', $val);

			// <FriendlyName> specifies a name that MUST uniquely describe the device
			if ($val = $in->getVar('FriendlyName'))
    			$dev->updVar('FriendlyName', $val);

			// <OS> specifies the operating system of the device
			if ($val = $in->getVar('OS'))
    			$dev->updVar('OS', $val);

			// <OSLanguage> specifies the language that is used by the operating system of the device
			if ($val = $in->getVar('OSLanguage'))
    			$dev->updVar('OSLanguage', $val);

			// <PhoneNumber> specifies a unique number that identifies the device
			if ($val = $in->getVar('PhoneNumber'))
    			$dev->updVar('PhoneNumber', $val);

			// <UserAgent> specifies the user agent
			if ($val = $in->getVar('UserAgent'))
    			$dev->updVar('UserAgent', $val);

			// <EnableOutboundSMS> specifies whether the server will send outbound SMS messages through the mobile device
			if ($val = $in->getVar('EnableOutboundSMS'))
    			$dev->updVar('AcceptSMS', $val);

			// <MobileOperator> specifies the name of the mobile operator to which a mobile device is connected
			if ($val = $in->getVar('MobileOperator'))
    			$dev->updVar('MobileOperator', $val);

    		// send back status
    		$op = $out->savePos();
    		$out->addVar('DeviceInformation');
    		$out->addVar('Status', masStatus::OK);
    		$out->restorePos($op);
		}

		// <UserInformation> serves as a container node that is used to request a list of a user's email addresses from the server
		if ($in->getVar('UserInformation') !== NULL) {

			// send back status
    		$op = $out->savePos();
    		$out->addVar('UserInformation');
    		$out->addVar('Status', masStatus::OK);

    		// no <Set> available!

			if (($val = $usr->getVar('EMailPrime')) || $usr->xpath('//EMail')) {
    	       	$out->addVar('Get');
	           	if ($ver < 14.1) {
    	       		// <EmailAddresses> contains one or more email addresses for the user
					$out->addVar('EmailAddresses');
    		       	// <PrimarySmtpAddress> specifies the primary SMTP address for the given account
					if ($val)
						$out->addVar('PrimarySmtpAddress', $val);
					while (($val = $usr->getItem()) !== NULL)
   	       				// <SMTPAddress> specifies one of the user's email addresses
						$out->addVar('SMTPAddress', $val);
 	          	} else {
					// <Accounts> contains all aggregate accounts that the user subscribes to
 	        	   	$out->addVar('Accounts');
					// <Account> contains all account information associated with a single account
 	        	   	$out->addVar('Account');
					// <AccountId> The value of this element identifies an account
 	        		$out->addVar('AccountId', $usr->getVar('GUID'));
					// <AccountName> specifies the account name for the given account
					if ($val = $usr->getVar('AccountName'))
	 	        		$out->addVar('AccountName', $val);
 	        		// <UserDisplayName> specifies the display name of the user associated with the given account
					if ($val = $usr->getVar('DisplayName'))
	 	        		$out->addVar('UserDisplayName', $val);
 	        		// <SendDisabled> specifies whether the client can send messages using the given account
    	       		if ($val = $usr->getVar('SendDisabled'))
	 	        		$out->addVar('SendDisabled', $val);
 	        		// <EmailAddresses> contains one or more email addresses for the user
					$out->addVar('EmailAddresses');
    		       	// <PrimarySmtpAddress> specifies the primary SMTP address for the given account
					if ($val = $usr->getVar('EMailPrime'))
						$out->addVar('PrimarySmtpAddress', $val);
					$usr->xpath('//EMail/.');
					while (($val = $usr->getItem()) !== NULL)
   	       				// <SMTPAddress> specifies one of the user's email addresses
						$out->addVar('SMTPAddress', $val);
 	          	}
 	       	}
 	       	$out->restorePos($op);
		}

		// <RightsManagementInformation> container node that is used to request rights management information settings
		if ($in->getVar('RightsManagementInformation') !== NULL) {
			// <Get> enables the client to retrieve rights management information settings, OOF settings, or user information settings from the server

			// send back status
    		$op = $out->savePos();
    		$out->addVar('RightsManagementInformation');
    		$out->addVar('Status', masStatus::OK);
	    	$out->addVar('Get');

			// <RightsManagementTemplates> contains the rights policy templates available to the client
			// <RightsManagementTemplate> contains the template identifier, name, and description of a rights policy template available on the client
			// <TemplateDescription> contains a description of the rights policy template represented by the parent <RightsManagementTemplate> element
			// <TemplateID> contains a string that identifies the rights policy template represented by the parent <RightsManagementTempalte> element
			// <TemplateName> specifies the name of the rights policy template represented by the parent <RightsManagementTemplate> element
			// for more information see source/masRights.xml
    		if (parent::getVar('DisableRightsManagementTemplates') === NULL) {
   				if (parent::xpath('//RightsManagementTemplate/.')) {
       				$out->addVar('RightsManagementTemplates', NULL, FALSE, $out->setCP(XML::AS_RIGTHM));
       				while(parent::getItem() !== NULL)
       					$out->append($this, FALSE);
       			}
   			}

 	       	$out->restorePos($op);
		}

		$out->getVar('Settings'); //3
		Debug::Msg($out, '<Settings> output'); //3

		return TRUE;
	}

	/**
	 * 	Add default Oof segments
	 */
	private function _mkoof(): void {

	    $usr = User::getInstance();
		$usr->getVar('Data');

		$usr->addVar('OutOfOffice');
		$usr->addVar('State', '0');
		$usr->addVar('Time', '-325472400/-325386000');
		$up = $usr->savePos();

	    // 1 - Internal, 2 - Known external user, 3 - Unknown external user
		foreach ([ '1', '2', '3' ] as $typ) {
			$usr->addVar('Message');
			$usr->addVar('Audience', $typ);
			// 0 - disabled / 1 - enabled
			$usr->addVar('Enabled', '0');
			$usr->addVar('Text', '', FALSE, [ 'TYP' => 'Text' ]);
			$usr->addVar('Text', '', FALSE, [ 'TYP' => 'HTML' ]);
			$usr->restorePos($up);
		}

		$usr->getVar('OutOfOffice');
	}

	/**
	 * 	Get status comment
	 *
	 *  @param  - Path to status code
	 * 	@param	- Return code
	 * 	@return	- Textual equation
	 */
	static public function status(string $path, string $rc): string { //2

	    if ($path == 'Oof/Get/OofState') { //2
			if (isset(self::STAT2[$rc])) //2
				return self::STAT2[$rc]; //2
	    } else { //2
			if (isset(self::STAT1[$rc])) //2
				return self::STAT1[$rc]; //2
	    } //2
	    if (isset(masStatus::STAT[$rc])) //2
			return masStatus::STAT[$rc]; //2
		return 'Unknown return code "'.$rc.'"'; //2
	} //2

}

?>
<?php
declare(strict_types=1);

/*
 * 	Device handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

/**
 *	Variables used in device objects:
 *
 * 	<GUID/>							Global Unique Identified: IMEI (clients unique identifier)
 *	<LUID/>							Local Unique Identifier: User id of associated user
 *  <SyncStat/>						DataStore::STAT_OK
 *  <Group/>						Record group: ''
 *  <Type/>				    		Record type: DataStore::TYP_DATA
 *  <Created/>						Time of record creation
 *  <LastMod/>			    		Time of last modification
 *  <Data>
 *
 *  ==== General configuration parameter ==================================================================================================
 *
 *	 <DeviceIP/>					Last used device IP (only informational purpose)
 *   <DataStore>
 *    <HandlerID/>					DataStore::* handler ID
 *    <MIME>						Supported MIME type
 *     <Name/>						MIME type name
 *     <Version/>					Optional version number
 *    </MIME>
 *   </DataStore>
 *
 *  ==== ActiveSync configuration parameter ================================================================================================
 *
 *   <FriendlyName/>				A name that MUST uniquely describe the device
 *   <Model/>						Name that generally describes the device of the client
 *   <DeviceType/>					Device type
 *   <UserAgent/>					User agent
 *   <IMEI/>						15-character code that MUST uniquely identify a device
 *   <PhoneNumber/>					Unique number that identifies the device
 *   <OS/>							Operating system of the device
 *   <OSLanguage/>					Language that is used by the operating system of the device
 *   <MobileOperator/>				Name of the mobile operator to which a mobile device is connected
 *   <Password/>					Recovery password of the device, which is stored by the server
 *   <EnableOutboundSMS/> 			The server will send outbound SMS messages through the mobile device
 *   <ClientManaged/>				Which contact and calendar elements in a Sync request are managed by the client
 *    								and therefore not ghosted (e.g. tag1;tag2;tag3)
 *
 *	 <Options>
 *
 *    <Sync>						Saved options for <Sync>
 *	   <BodyPreference>
 *      <Id/>
 *	    <Type/>						fldBody::TYP_AS
 *      <TruncationSize>
 *      <AllOrNone/>
 *      <Preview/>
 *	   </BodyPreference>
 *    </Sync>
 *
 *    <ItemOperations>				Saved options for <ItemOperations>
 *	   <BodyPreference>
 *      <Id/>
 *	    <Type/>						fldBody::TYP_AS
 *      <TruncationSize>
 *      <AllOrNone/>
 *      <Preview/>
 *	   </BodyPreference>
 *    </ItemOperations>
 *
 *    <Search>						Saved options for <Search>
 *	   <BodyPreference>
 *      <Id/>
 *	    <Type/>						fldBody::TYP_AS
 *      <TruncationSize>
 *      <AllOrNone/>
 *      <Preview/>
 *	   </BodyPreference>
 *    </Search>
 *
 *	 </Options>
 *
 *  </Data>
 */

namespace syncgw\lib;

class Device extends XML {

	// module version number
	const VER = 8;

	// MIME types

    /**
     * 	Singleton instance of object
     * 	@var Device
     */
    static private $_obj = NULL;

   /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Device {

		if (!self::$_obj) {
            self::$_obj = new self();

			// load empty device record skeleton
			self::$_obj->loadXML(
				'<syncgw>'.
					'<GUID/>'.
				  	'<LUID/>'.
					'<SyncStat>'.DataStore::STAT_OK.'</SyncStat>'.
				  	'<Group/>'.
					'<Type>'.DataStore::TYP_DATA.'</Type>'.
				  	'<LastMod>'.time().'</LastMod>'.
					'<Created>'.time().'</Created>'.
					'<CRC/>'.
		   	    	'<extID/>'.
		   	    	'<extGroup/>'.
					'<Data/>'.
			'</syncgw>');

			// register shutdown function
			$srv = Server::getInstance();
			$srv->regShutdown(__CLASS__);
		}

		return self::$_obj;
	}

 	/**
	 * 	Shutdown function
	 */
	public function delInstance(): void {

		if (!self::$_obj)
			return;

		// object modified?
		if (self::$_obj->getVar('GUID') && self::$_obj->updObj()) {
			$db = DB::getInstance();
			$db->Query(DataStore::DEVICE, DataStore::UPD, self::$_obj);
		}

		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', sprintf(_('%s handler'), Util::HID(Util::HID_ENAME, DataStore::DEVICE)));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Activate device
	 *
	 * 	@param	- Device name
	 */
	public function actDev(string $dev): void {

		$http = HTTP::getInstance();

		// did we already have device loaded?
		if (!$dev || parent::getVar('GUID') != $dev) {

			// load device information
			$db = DB::getInstance();
			if (!($rd = $db->Query(DataStore::DEVICE, DataStore::RGID, $dev))) {

				// get default device typ
				$cnf = Config::getInstance();
				if (!($typ = $cnf->getVar(Config::HD)))
				    $typ = $dev;

				// replace device type
				if (substr($typ, 0, 3) == 'GUI')
					$typ = 'DAV';

				Debug::Msg('Load/set default device "source/dev_'.$typ.'.xml"'); //3
				$xml = new XML();
				$xml->loadFile(Util::mkPath('source/dev_'.$typ.'.xml'));
				$xml->getVar('Data');

				// import device
				parent::delVar('Data');
				parent::getVar('syncgw');
				parent::append($xml, FALSE, FALSE);

				// set device name
				parent::updVar('GUID', $dev);

				// save device
				$db->Query(DataStore::DEVICE, DataStore::ADD, $this);

			} else
				parent::loadXML($rd->saveXML());

			Debug::Msg($this, 'Activate device "'.$dev.'"'); //3
		}

		// save remote IP address
        parent::updVar('DeviceIP', $http->getHTTPVar('REMOTE_ADDR'));

        // save user name
		if (Debug::$Conf['Script'] != 'Device' && Debug::$Conf['Script'] != 'MIME01') { //3
			$usr = User::getInstance();
			parent::updVar('LUID', $usr->getVar('LUID'));
		} //3
	}

}

?>
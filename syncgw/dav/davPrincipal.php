<?php
declare(strict_types=1);

/*
 * 	WebDAV principal handler class
 *
 *	@package	sync*gw
 *	@subpackage	SabreDAV support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 * 	@link		Sabre/DAVACL/PrincipalBackend/BackendInterface.php
 *  @link       Sabre/DAVACL/PrincipalBachend/PDO.php
 */

namespace syncgw\dav;

use Sabre\DAV\MkCol;
use syncgw\lib\Debug; //3
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\HTTP;
use syncgw\lib\User;
use syncgw\lib\Util; //2
use syncgw\lib\XML;
use syncgw\lib\Config; //2

class davPrincipal implements \Sabre\DAVACL\PrincipalBackend\BackendInterface {

	// module version number
	const VER = 5;

   /**
     * 	Singleton instance of object
     * 	@var davPrincipal
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): davPrincipal {

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

    	$xml->addVar('Opt', _('WebDAV principal handler'));
		$xml->addVar('Ver', strval(self::VER));
	}

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actually injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     * @return array
     */
	public function getPrincipalsByPrefix($prefixPath) {

		$db = DB::getInstance();

		// load all user data
		$recs = [];
		foreach ($db->Query(DataStore::USER, DataStore::RIDS, '') as $gid => $unused) {

			if (!($doc = $db->Query(DataStore::USER, DataStore::RGID, $gid)))
			    continue;

			$rec = [
					'id'                => $doc->getVar('LUID'),
					'uri'               => $prefixPath.'/'.$gid,
					'{DAV:}displayname' => $gid,
			];
			if ($email = $doc->getVar('EMailPrime'))
				$rec['{http://sabredav.org/ns}'] = $email;
			$recs[] = $rec;
		}
		$unused; // disable Eclipse warning

		Debug::Msg($recs, 'Principal loaded by prefix ['.$prefixPath.']'); //3

		return $recs;
	}

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     * @return array
     */
	public function getPrincipalByPath($path) {

		// split URI
		list(, $email) = \Sabre\Uri\split($path);
		$u   = explode('@', $email);
		if (isset($u[1]))
			$gid = $u[0];
		else {
			$gid = $email;
			$email = NULL;
		}

		// inject user authorization?
		$usr = User::getInstance();
		if (!$usr->getVar('GUID')) {
			$cnf  = Config::getInstance(); //2
			$http = HTTP::getInstance();
			$dev  = $http->getHTTPVar('REMOTE_ADDR');
			if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE //2
				&& !($cnf->getVar(Config::TRACE) & Config::TRACE_FORCE) //3
				)  //2
				if (strncmp($dev, Util::DBG_PREF, Util::DBG_PLEN)) //2
					$dev = Util::DBG_PREF.$dev; //2
			$usr->loadUsr($gid, '', $dev);
			Debug::Msg('Load user (fake login) for ['.$gid.'] on device ['.$dev.']'); //3
		}

		$db = DB::getInstance();

		if (!($doc = $db->Query(DataStore::USER, DataStore::RGID, $gid))) {
		    Debug::Err('User ['.$gid.'] not found'); //3
			return [];
		}

		$rec = [
				'id'  				=> $doc->getVar('LUID'),
				'uri' 				=> $path,
				'{DAV:}displayname' => $gid,
		];
		if ($email)
			$rec['{http://sabredav.org/ns}'] = $email;

		Debug::Msg($rec, 'Principal loaded by path ['.$path.']'); //3

		return $rec;
	}

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $path
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
	public function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch) {

		Debug::Msg('--- UNSUPPORTED davPrincipal::updatePrincipal()'); //3
	}

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @param string $test
     * @return array
     */
	public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {

		Debug::Msg('--- UNSUPPORTED davPrincipal::searchPrincipals()'); //3
		return [];
	}

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or NULL, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     * @return string
     */
	public function findByUri($uri, $principalPrefix) {

		Debug::Msg('--- UNSUPPORTED davPrincipal::findbyUri()'); //3
		return '';
	}

    /**
     * Returns the list of members for a group-principal
     *
     * @param string $principal
     * @return array
     */
	public function getGroupMemberSet($principal) {

		Debug::Msg('--- UNSUPPORTED davPrincipal::getGroupMemberSet("'.$principal.'")'); //3
		return [];
	}

    /**
     * Returns the list of groups a principal is a member of
     *
     * @param string $principal
     * @return array
     */
	public function getGroupMembership($principal) {

		Debug::Msg('--- UNSUPPORTED davPrincipal::getGroupMembership("'.$principal.'")'); //3
		return [];
	}

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array $members
     * @return void
     */
	public function setGroupMemberSet($principal, array $members) {

		Debug::Msg($members, '--- UNSUPPORTED davPrincipal::setGroupMemberSet("'.$principal.'")'); //3
	}

    /**
     * Creates a new principal.
     *
     * This method receives a full path for the new principal. The mkCol object
     * contains any additional webdav properties specified during the creation
     * of the principal.
     *
     * @param string $path
     * @param MkCol $mkCol
     * @return void
     */
	public function createPrincipal($path, MkCol $mkCol) {

		Debug::Msg('--- UNSUPPORTED davPrincipal::createPrincipal()'); //3
    }

}

?>
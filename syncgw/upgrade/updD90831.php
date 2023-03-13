<?php
declare(strict_types=1);

/*
 *	Data base upgrade handler class
 *
 *	@package	sync*gw
 *	@subpackage	Upgrade
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\upgrade;

use syncgw\lib\XML;

class updD90831 {

    /**
 	 * 	Upgrade document
 	 *
 	 *	@param	- Handler ID
 	 *	@param  - Object
 	 *	@param  - Version to upgrade to
 	 * 	@return	- 0 = Abort execution; 1 = Update record; 2 = Nothing changed; 3 = Skip data store; 4 = Delete record
 	 */
 	public function upgrade(int $hid, XML &$doc, string $ver): int {

 		$rc = substr($ver, 0, 1) != '9' ? 0 : 2;

//#  	Debug::Save('new%d.xml', $xml->saveXML(TRUE, TRUE)); //3
//    		$doc->updvar('Record', base64_encode(XML::cnvStr($xml->saveXML(), FALSE))); //3
//       	    $rc = 1; //3
// 	    } //3

 	    return $rc;
 	}

}

?>
<?php
declare(strict_types=1);

/*
 *  Load Mail records
 *
 *	@package	sync*gw
 *	@subpackage	Tools
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\helper;

use syncgw\lib\Debug;
use syncgw\lib\DB;
use syncgw\lib\XML;
use syncgw\lib\DataStore;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\Attachment;
use syncgw\document\field\fldAttach;
use syncgw\document\field\fldAttribute;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../scripts/Server.php');

Debug::$Conf['Script'] = 'LoadMails';

if (Debug::$Conf['UseMercury'])
	die ('Not allowed for remote IMAP server');

Debug::$Conf['Exclude']['syncgw\lib\Log:Caller'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVal'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:addVar'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVar'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getName'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getItem'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:updVar'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$be  = 'mail';
$hid = DataStore::EXT|DataStore::MAIL;

Debug::Msg('Starting back end handler "'.$be.'"');
setDB($be, $hid);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('Authorizing user "'.Debug::$Conf['ScriptUID'].'"');
$usr = User::getInstance();
if (!$usr->Login(Debug::$Conf['ScriptUID'], Debug::$Conf['ScriptUPW'])) {
	msg('+++ Login failed! Please check if Mercury-Mail is running', Util::CSS_ERR);
   	exit;
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('Deleting INBOX content - folder itself will not be deleted');
$db  = DB::getInstance();
Debug::Msg($recs = $db->getRIDS($hid));
foreach ($recs as $gid => $att) {
	if ($att != DataStore::TYP_GROUP)
		$db->Query($hid, DataStore::DEL, $gid);
	else {
		$xml = $db->Query($hid, DataStore::RGID, $gid);
		if ($xml->getVar(fldAttribute::TAG) & fldAttribute::MBOX_USER)
			$db->Query($hid, DataStore::DEL, $gid);
	}
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

$dir = '..'.DIRECTORY_SEPARATOR.'appdata'.DIRECTORY_SEPARATOR.'rc-mail'.DIRECTORY_SEPARATOR;
$xml = new XML();
$att = Attachment::getInstance();

msg('Loading mails from '.$dir);

for ($cnt=1; file_exists($file = $dir.sprintf('sgw.%02d', $cnt)); $cnt++) {
	msg('Loading file "'.$file.'"');
	$xml->loadFile($file);

	if ($xml->xpath('//Data/'.fldAttach::TAG)) {
		while ($xml->getItem() !== NULL) {
			$xp = $xml->savePos();
			$name = $xml->getVar(fldAttach::SUB_TAG[1], FALSE);
			msg('Saving attachment "'.$name.'"');
			$att->create(file_get_contents($dir.$name));
			$xml->restorePos($xp);
		}
	}

	msg('Creating mail record');
	$xml->getVar('syncgw');
	Debug::Msg($xml, 'Mail record');

	// do MIME comparison only
	if (0) {
		$n = Debug::Save('MIME%02d.txt', $db->cnv2MIME($xml));
	 	exec('C:\Windows\System32\fc.exe /N /T /L '.$n.' '.$dir.sprintf('org.%02d', $cnt).' > '.$dir.'Compare 2>&1');
 		echo '<br /><hr><font color="green"><h3>'.''.'</h3>'.
	 	     XML::cnvStr(str_replace('*****', '+++', file_get_contents($dir.'Compare'))).
 		     '</font><hr><br />';

	} else {
		if (!$db->Query($hid, DataStore::ADD, $xml)) {
	        msg('+++ Record "'.$cnt.'" not written', Util::CSS_WARN);
	        exit;
		}
	}
}

msg('+++ End of script');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

?>
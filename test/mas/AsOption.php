<?php

/*
 *  ActiveSync <Optons> handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts;

use syncgw\lib\Debug;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\activesync\MASHandler;
use syncgw\lib\DataStore;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'AsOption';

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

$tests		 	  = [
		1 => [
				'Sync',
				'in01.xml',
				'M1',
				#'in07.xml',
				#'C1',
			 ],
		2 => [
				'Find',
				'in02.xml',
				''
			 ],
		3 => [
				'GetItemEstimate',
				'in03.xml',
				DataStore::MAIL,
			 ],
		4 => [
				'ItemOperations',
				'in04.xml',
				DataStore::MAIL,
			 ],
		5 => [
				'ResolveRecipients',
				'in05.xml',
				'',
			 ],
		6 => [
				'Search',
				'in06.xml',
				DataStore::DOCLIB,
		],
];

if (!strlen($_SERVER['QUERY_STRING'])) {
	msg('+++ Missing parameter', Util::CSS_ERR);
	exit;
}

if (!isset($tests[$_SERVER['QUERY_STRING']])) {
	msg('+++ Test "'.$_SERVER['QUERY_STRING'].'" not found', Util::CSS_ERR);
	exit;
}

$tst = $tests[$_SERVER['QUERY_STRING']];

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$xml = new XML();
$xml->loadXML(file_get_contents($file = '..'.DIRECTORY_SEPARATOR.'mimedata'.DIRECTORY_SEPARATOR.'asoption'.
			  DIRECTORY_SEPARATOR.$tst[1]));

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Load options for <'.$tst[0].'> from XML');

$mas = MASHandler::getInstance();

msg('Input file "'.$file.'"');
Debug::Msg($xml);
$mas->loadOptions($tst[0], $xml);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Get specific option for "'.$tst[2].'"');
Debug::Msg($mas->getOption($tst[2]), 'Should work');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Get specific option for "Error"');
Debug::Msg($mas->getOption('Error'), 'Should return only default values');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Restore saved options / Load default options');
$xml->loadXML('<syncgw><'.$tst[0].'/></syncgw>');
$mas->loadOptions($tst[0], $xml);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>
<?php
declare(strict_types=1);

/*
 *  Test helper functions
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

use syncgw\lib\Config;
use syncgw\lib\Debug;
use syncgw\lib\Encoding;
use syncgw\lib\DataStore;
use syncgw\lib\Log;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\lib\ErrorHandler;

require_once(__DIR__.'/../syncgw/lib/Loader.php');

ErrorHandler::getInstance();

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
echo '<pre>';
msg('Start of script - search for "+++" to locate error', Util::CSS_WARN);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
// enable log reader
$log = Log::getInstance();
$log->Plugin('logread', NULL);

// enable debug output
Debug::Mod(TRUE);

/**
 * 	Show message
 *
 * 	@param	- Message string
 * 	@param	- Color setting (CSS_*)
 */
function msg(string $str, string $col = 'color: #009933'): void {
 	echo '<font style="'.$col.'">'.str_pad('', 67, '-').' </font>'.
 		 '<font style="'.$col.'" size=+2><strong>'.XML::cnvStr($str).'</strong></font><br />';
}

/**
 * 	Load reader function
 *
 * 	@param	- Log message type
 * 	@param	- Long message
 */
function logread(int $typ, string $msg): void {
 	$map = [
   		Log::ERR 	=> Util::CSS_ERR,
   		Log::WARN	=> Util::CSS_WARN,
   		Log::INFO	=> Util::CSS_INFO,
   		Log::APP	=> Util::CSS_APP,
 		Log::DEBUG  => Util::CSS_DBG,
 	];
 	$typ &= ~Log::ONETIME;
 	echo '<font style="'.$map[$typ].'"><strong>'.str_pad('LOG MESSAGE ', 67, $typ == Log::ERR ? '+' : '-').
 		 ' '.XML::cnvStr(trim($msg)).'</strong></font><br />';
}

/**
 * 	Compare XML object with data
 *
 * 	@param	- XML object to compare / First file name
 * 	@param	- TRUE=whole document; FALSE=from current position
 * 	@param	- File name to compare with
 *  @param  - Additional message to show
 *  @return - TRUE= Equal; FALSE= Changes detected
 */
function comp($out, bool $top = TRUE, string $name = '', string $msg = ''): bool {

 	$cnf = Config::getInstance();
 	$dir = $cnf->getVar(Config::DBG_DIR);
 	$enc = Encoding::getInstance();

	if (is_object($out)) {
		$bdy = $out->saveXML($top, TRUE);
	 	$out = Debug::Save('comp%d.xml', $bdy);
	 	$mod = '/N /T /L';
	} else {
		$out = Debug::Save('wbxml%d.wbxml', $out);
		$mod = '/B';
	}
	if (!file_exists($name)) {
  		msg('+++ FILE "'.$name.'" DOES NOT EXIST', Util::CSS_ERR);
  		exit;
 	}

 	exec('C:\Windows\System32\fc.exe '.$mod.' '.$out.' '.$name.' > '.$dir.'Compare 2>&1');
 	$wrk = $enc->import(file_get_contents($dir.'Compare'));
 	if (strpos($wrk, 'Keine Unterschiede') === FALSE) {
 		$wrk .= '+++ Changes detected!'."\n\n";
		echo '<br /><hr><strong><font color="red"><h3>'.$msg.'</h3>'.htmlentities($wrk, ENT_SUBSTITUTE).'</font></strong><hr><br />';
		return FALSE;
 	}

	echo '<br /><hr><font color="green"><h3>'.$msg.'</h3>'.XML::cnvStr($wrk).'</font><hr><br />';

	return TRUE;
}

/**
 * 	Configure data base backend for testing
 *
 * 	@param	- Back end name (defauts to 'file')
 * 	@param	- Config Handler ID (defaults no none)
 */
function setDB(string $be = 'file', int $hid = -1): void {

 	$cnf = Config::getInstance();
 	$cnf->updVar(Config::TRACE_MOD, 'Off');
 	$cnf->updVar(Config::LOG_LVL, Log::ERR|Log::WARN|Log::INFO|Log::APP|Log::DEBUG);

 	// force endless max_execution
	$cnf->updVar(Config::EXECUTION, 0);

 	switch ($be) {
 	case 'file':
  		if (!Debug::$Conf['DirDeleted']) {
		    // cleanup file directory
		    $dir = $cnf->getVar(Config::FILE_DIR);
    		Debug::Msg('Deleting files in "'.$dir.'"');
	  	    Util::rmDir($dir);
	        mkdir($dir);
	  	    Debug::$Conf['DirDeleted'] = 1;
 		}
 		$cnf->updVar(Config::DATABASE, $be);
  		$mid = DataStore::DATASTORES & ~DataStore::MAIL;
  		break;

 	case 'mail':
 		$cnf->updVar(Config::IMAP_HOST, Debug::$Conf['Imap-Host']);
 		$cnf->updVar(Config::IMAP_PORT, Debug::$Conf['Imap-Port']);
 		$cnf->updVar(Config::IMAP_ENC,  Debug::$Conf['Imap-Enc']);
 		$cnf->updVar(Config::IMAP_CERT, Debug::$Conf['Imap-Cert']);
 		$cnf->updVar(Config::SMTP_HOST, Debug::$Conf['SMTP-Host']);
 		$cnf->updVar(Config::SMTP_PORT, Debug::$Conf['SMTP-Port']);
 		$cnf->updVar(Config::SMTP_ENC,  Debug::$Conf['SMTP-Enc']);
 		$cnf->updVar(Config::SMTP_AUTH, Debug::$Conf['SMTP-Auth']);

 	case 'mysql':
 	case 'roundcube':
	case 'myapp':
 		$cnf->updVar(Config::DATABASE, $be);
  		$cnf->updVar(Config::DB_HOST,   Debug::$Conf['DB-Host']);
  		$cnf->updVar(Config::DB_NAME,   Debug::$Conf['DB-Name']);
  		$cnf->updVar(Config::DB_PORT,   Debug::$Conf['DB-Port']);
  		$cnf->updVar(Config::DB_USR, 	Debug::$Conf['DB-UID']);
  		$cnf->updVar(Config::DB_UPW, 	Debug::$Conf['DB-UPW']);
  		$mid = $hid;
  		break;

 	default:
  		msg('+++ Unknown back end "'.$be.'"', Util::CSS_ERR);
  		exit;
 	}

 	if ($hid != -1) {
 	    $hid = $hid & $mid;
	 	$cnf->updVar(Config::ENABLED, $hid);
	 	if (is_array($h = Util::HID(Util::HID_CNAME, $hid)))
	 		$h = implode(', ', $h);
	 	Debug::Msg('Using handler "'.$h.'"');
 	}

}

?>
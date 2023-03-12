<?php
declare(strict_types=1);

/*
 * 	Create production version package
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiSoftware {

	// module version
	const VER = 11;

	// package file
	const PKG_FILE = 'pkg.php';

    /**
     * 	Singleton instance of object
     * 	@var guiSoftware
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiSoftware {

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

		$xml->addVar('Opt', _('Create software packages plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		$gui = guiHandler::getInstance();

		// only allowed for administrators
		if (!$gui->isAdmin())
			return guiHandler::CONT;

		switch ($action) {
		case 'Init':
			$gui->putCmd('<input id="Software" '.($gui->getVar('LastCommand') == 'Software' ? 'checked ' : '').'type="radio" name="Command" '.
						 'value="Software" onclick="document.syncgw.submit();"/>&nbsp;'.
						 '<label for="Software">'._('Create software packages').'</label>');

		default:
			return guiHandler::CONT;

		case 'Software':
		case 'Software_Create':
			break;
		}

		// is syncgw configured?
		if (!$gui->isConfigured())
			return guiHandler::CONT;

		$package = []; // disable Eclipse warning

		$gui->putMsg(sprintf(_('Base directory is [%s]'), $base_dir = Util::mkPath('..').DIRECTORY_SEPARATOR));
		$gui->putMsg(sprintf(_('Using package file [%s]'), $pkg_file = Util::mkPath().'..'.DIRECTORY_SEPARATOR.self::PKG_FILE));
		$gui->putMsg(sprintf(_('Using output directory [%s]'), $pkg_dir = Util::mkPath('..'.DIRECTORY_SEPARATOR.'downloads'.
							DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR)));

		// load package description
		require_once($pkg_file);

		// filter
		$filter = [ 2	=> 'Professional edition debugging code (//2)',
				    3	=> 'Internal debugging code (//3)',
				    1	=> 'Comments (// and /** **/)',
		];

		// selected packages
		if (($sp = $gui->getVar('Softwae_Pkg')) !== NULL)
    		$sp = unserialize(base64_decode($sp));
		// selected filter
		if (($sf = $gui->getVar('Softwae_Filter')) !== NULL)
    		$sf = unserialize(base64_decode($sf));
        if (!$sf)
	       	$sf = [ 1, 2, 3, 4 ];

		// set button
		$gui->setVal($gui->getVar('Button').$gui->mkButton(guiHandler::STOP).
					 $gui->mkButton(_('Create'), _('Create software package'), 'Software_Create'));

		// show packages
		$w = '<b>Select software package to create</b><br/><br/>';
		$ok = '';
		foreach ($package as $k => $v) {
			if ($ok && $ok != substr($k, 0, 2))
				$w .= '<br />';
			$ok = substr($k, 0, 2);
			$s = !$sp || ($sp && is_numeric(array_search($k, $sp))) ? 'checked' : '';
			$w .= '<input type="checkbox" name="Softwae_Pkg[]" value="'.$k.'" '.$s.' /> '.
					'<strong>'.$k.'</strong> '.$v['Name'].'<br/>';
		}
		$w .= '<br/>';

		// filter level
		$w .= '<b>Select filter options</b><br/><br/>';
		foreach ($filter as $k => $v) {
			$s = array_search($k, $sf) !== FALSE ? 'checked' : '';
			$w .= '<input type="checkbox" name="Softwae_Filter[]" value="'.$k.'" '.$s.' /> '.$v.'<br/>';
		}
		$gui->putCmd($w);

		// create files?
		if ($action != 'Software_Create')
			return guiHandler::STOP;

		// any package selected?
		if (!count($sp)) {
			$gui->putMsg('Please select package to create', Util::CSS_WARN);
			return guiHandler::CONT;
		}

		// create output directory
		if ($err = $this->_mkDir($pkg_dir)) {
			$gui->putMsg('Error creating output directory \''.$err.'\'', Util::CSS_ERR);
			return guiHandler::CONT;
		}

		// create syncgw version
		// please note: only internal files are allowed to have a "const VER = xx;" statement
		$files = $this->_getFiles($gui, $base_dir);

		$n = 0;
		foreach ($files as $file => $inc) {
			// special check for data base files
			if (stripos($file, 'interfaces'.DIRECTORY_SEPARATOR) !== FALSE) {
				// only allow "files", "mysql", "roundcube" and "mail" handler
				if (stripos($file, DIRECTORY_SEPARATOR.'file') === FALSE &&
					stripos($file, DIRECTORY_SEPARATOR.'mysql') === FALSE &&
					stripos($file, DIRECTORY_SEPARATOR.'roundcube') === FALSE &&
					stripos($file, DIRECTORY_SEPARATOR.'mail') === FALSE)
					continue;
			}
			// no external code
			if (stripos($file, DIRECTORY_SEPARATOR.'ext') !== FALSE)
			    continue;
			if (stripos($file, '.php') !== FALSE) {
	       		$wrk = []; // disable Eclipse warning
				if (preg_match_all('/(?<=VER).*=(.*)(?=;)/', file_get_contents($file), $wrk)) {
					$n += intval($wrk[1][0]);
				}
			}
		}
		$sgwver = Server::MVER.sprintf('%02d.%02d', $n / 100, $n % 100);

		// get list of files from disc
		$files = $this->_getFiles($gui, $base_dir);

		// things to remove
		$exclude = [];
		foreach ($exclude as $mask) {
			foreach ($files as $name => $unused) {
				if (stripos($name, $mask) !== FALSE)
					unset($files[$name]);
			}
			$unused; // disable Eclipse warning
		}

		// process package list
		$gui->putMsg('Starting package creation');
		foreach ($sp as $k => $id) {
			$pkg = $package[$id];

			// store package name and version?
			if (substr($id, 0, 5) == 'ED01' || substr($id, 0, 4) == 'ED02')
				file_put_contents(Util::mkPath('syncgw.pkg'), $pkg['Name'].' '.$sgwver);

			// creating ZIP file
			$zip = new \ZipArchive();
			$dest = $pkg_dir.$id.'.zip';
			$gui->putMsg('');
			unlink($dest);
			$gui->putMsg($pkg['Name'].' '.$sgwver, Util::CSS_TITLE);
			if (($rc = $zip->open($dest, \ZipArchive::CREATE|\ZipArchive::OVERWRITE)) !== TRUE) {
				$gui->putMsg('Error creating zip file \''.$dest.'\' ('.$rc.')!', Util::CSS_ERR);
				return guiHandler::CONT;
			}

			// reset level counter
			$cnt =  [ 0, 0, 0, 0, 0 ];
			$fcnt = 0;
			$err = 1;

			// reset filter for professional edition
			$wsf = $sf;
			if ($id == 'ED02') {
				if (($t = array_search(2, $wsf)) !== FALSE)
					unset($wsf[$t]);
			}

			// disable everything except if interface is selected
			$wfiles = $files;
			if (substr($pkg['Name'], 0, 2) == 'DS') {
				foreach ($wfiles as $file => $inc)
					$wfiles[$file] = 0;
			}

			// process exclusion/exclusion list
			foreach ($pkg['Files'] as $entry) {
				$entry[0] = str_replace('/', DIRECTORY_SEPARATOR, $entry[0]);
				foreach ($wfiles as $file => $inc) {
					if (stripos($file, $entry[0]) !== FALSE)
						$wfiles[$file] = $entry[1];
				}
			}

			// store files
			$gui->putMsg('');
			foreach ($wfiles as $file => $inc) {
				$dest = str_replace($base_dir, '', $file);
				if ($inc != 0 || is_string($inc)) {
					if (is_string($inc))
						$dest = $inc.basename($file);
					$gui->putMsg('Including '.$dest, Util::CSS_INFO);
					$fcnt++;
				} else {
					$gui->putMsg('Excluding '.$dest, Util::CSS_CODE);
					continue;
				}
				if ($err = $this->_copyFile($gui, $zip, $file, $dest, $wsf, $cnt)) {
					$gui->putMsg('Error creating file \''.$err.'\'', Util::CSS_ERR);
					return guiHandler::CONT;
				}
			}

			// close output zip file
			$zip->close();
			if (!$err) {
				$gui->putMsg('');
				$max = 0;
				foreach ($filter as $id => $name) {
					$gui->putMsg(sprintf('%06d lines of code with \'%s\' were filtered', $cnt[$id], $name));
					$max += $cnt[$id];
				}
				$gui->putMsg(sprintf('%06d total lines of code', $cnt[0] + $max));
				$gui->putMsg('');
				$gui->putMsg(sprintf('%06d number of files in package', $fcnt));
				$gui->putMsg(sprintf('%06d number of lines in package', $cnt[0]));
				$gui->putMsg('');
			}
		}

		return $action == 'Software_Create' ? guiHandler::STOP : guiHandler::CONT;
	}

	/**
	 * 	Get file name list
	 *
	 * 	@param	- Pointer to browser object
	 * 	@param	- Directory path
	 * 	@return	- Files name []
	 */
	private function _getFiles(guiHandler & $gui, string $name): array {

		$l = [];

		if (!($d = @opendir($name))) {
			$gui->putMsg(sprintf(_('Can\'t open [%s]'), $name), Util::CSS_ERR);
			return [];
		}

		while (($file = @readdir($d)) !== FALSE) {
			if ($file == '.' || $file == '..')
				continue;
			if (is_dir($name.DIRECTORY_SEPARATOR.$file))
				$l += $this->_getFiles($gui, $name.DIRECTORY_SEPARATOR.$file);
			else
				$l[$name.DIRECTORY_SEPARATOR.$file] = FALSE;
		}
		@closedir($d);

		return $l;
	}

	/**
	 * 	Create directory recursivly
	 *
	 * 	@param	- Directory path
	 * 	@return	- Path name where error occured or NULL (if ok)
	 */
	private function _mkDir(string $path): ?string {

		if (!@is_dir($path)) {
			if ($base = dirname($path))
				$this->_mkDir($base);
			if (@mkdir($path, 0755) === FALSE)
				return $path;
		}

		return NULL;
	}

	/**
	 * 	Copy file
	 *
	 * 	@param	- Pointer to browser object
	 * 	@param	- Zip archive resource pointer
	 * 	@param	- Source file name
	 * 	@param	- Destination file name
	 * 	@param	- Filter level []
	 * 	@param	- Counter []
	 * 	@return	- File name where error occured or NULL (if ok)
	 */
	private function _copyFile(guiHandler &$gui, $zip, string $src, string $dest, array $filter, array &$cnt): ?string {

		if (substr($src, 0, 1) == '#') {
			$gui->putMsg('');
			$gui->putMsg(substr($src, 1), Util::CSS_TITLE);
			$gui->putMsg('');
			return NULL;
		}

		$wrk = file_get_contents($src);
		// skip special files
		if (!stripos($src, 'Sabre') &&
			!stripos($wrk, 'interface sgw') &&
			(stripos($src, '.php') 	||
			 stripos($src, '.xml') 	||
			 stripos($src, '.js')  	||
			 stripos($src, '.php') 	||
			 stripos($src, '.sql') 	||
			 stripos($src, '.html')	||
			 stripos($src, '.css'))) {

			// filter function headers
			$wrk = preg_replace('#/\*\*[^*]*\*+(?:[^/*][^*]*\*+)*/#', '', $wrk);

			// clean end of line
			$wrk = str_replace("\r\n", "\n", $wrk);
			$wrk = explode("\n", $wrk);

			// 2 => 'Official debugging code (//2)',
			// 3 => 'Internal debugging code (//3)',
			// 1 => 'Comments (// and /** **/)',
			foreach ($filter as $k => $id) {
				foreach ($wrk as $n => $r) {
					if (!$r)
						continue;
					if ($id == 1) {
						if (($p = strpos($r, '// ')) !== FALSE) {
							$wrk[$n] = substr($r, 0, $p);
							$cnt[$id]++;
						}
						if (($p = strpos($r, "//\t")) !== FALSE) {
							$wrk[$n] = substr($r, 0, $p);
							$cnt[$id]++;
						}
					} elseif (($p = strpos($r, '//'.$id)) !== FALSE) {
						$wrk[$n] = NULL;
						$cnt[$id]++;
					}
				}
			}

			// remove all remaining comment tags
			foreach ([ 2, 3 ] as $id)
				$wrk = str_replace('//'.$id, '', $wrk);

			// clear blank lines
			foreach ($wrk as $k => $v) {
				if (!strlen(trim($v)))
					unset($wrk[$k]);
			}
			$cnt[0] += count($wrk);
			$wrk = implode("\n", $wrk);

			// delete any blanks at end of line
			$wrk = preg_replace('/[ \t]+\n/', "\n", $wrk);
		}
		// else
		//  $gui->putMsg('Source "'.$src.'" untouched');

		// add header to language file
		if (stripos($src, '.po') !== FALSE) {
			$wrk = str_replace("\r\n", "\n", $wrk);
			$wrk = explode("\n", $wrk);
			$wrk = preg_grep('/X-Poedit/', $wrk, PREG_GREP_INVERT);
			$wrk =
			'#'."\n".
			'#	Language file'."\n".
			'#'."\n".
			'# 	@package	sync*gw'."\n".
			'# 	@subpackage	Core'."\n".
			'#	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved'."\n".
		 	'* 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE'."\n".
			'#'."\n".
			"\n".implode("\n", $wrk);
		}

		// add to archive
		if (!$zip->addFromString(str_replace(DIRECTORY_SEPARATOR, '/', substr($dest, 1)), $wrk)) {
			$gui->putMsg('Error adding '.$dest.' to .ZIP file', Util::CSS_ERR);
			return $dest;
		}

		return NULL;
	}

}

?>
<?php
declare(strict_types=1);

/*
 *  XML handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

class XML {

	// module version number
	const VER 			= 11;

	// node types
	const NODETYP 		= [ //3
            XML_ELEMENT_NODE            => 'XML_ELEMENT_NODE', //3
            XML_ATTRIBUTE_NODE          => 'XML_ATTRIBUTE_NODE', //3
    		XML_TEXT_NODE               => 'XML_TEXT_NODE', //3
    		XML_CDATA_SECTION_NODE      => 'XML_CDATA_SECTION_NODE', //3
		    XML_ENTITY_REF_NODE         => 'XML_ENTITY_REF_NODE', //3
		    XML_ENTITY_NODE             => 'XML_ENTITY_NODE',	//3
			XML_PI_NODE                 => 'XML_PI_NODE', //3
			XML_COMMENT_NODE            => 'XML_COMMENT_NODE', //3
			XML_DOCUMENT_NODE           => 'XML_DOCUMENT_NODE', //3
			XML_DOCUMENT_TYPE_NODE      => 'XML_DOCUMENT_TYPE_NODE', //3
			XML_DOCUMENT_FRAG_NODE      => 'XML_DOCUMENT_FRAG_NODE', //3
			XML_NOTATION_NODE           => 'XML_NOTATION_NODE', //3
			XML_HTML_DOCUMENT_NODE      => 'XML_HTML_DOCUMENT_NODE', //3
			XML_DTD_NODE                => 'XML_DTD_NODE', //3
			XML_ELEMENT_DECL_NODE       => 'XML_ELEMENT_DECL_NODE', //3
			XML_ATTRIBUTE_DECL_NODE     => 'XML_ATTRIBUTE_DECL_NODE', //3
			XML_ENTITY_DECL_NODE        => 'XML_ENTITY_DECL_NODE', //3
			XML_NAMESPACE_DECL_NODE     => 'XML_NAMESPACE_DECL_NODE', //3
    ]; //3

    const ENTITY		= [ [ '&', '<', '>', ], [ '&#38;', '&#60;', '&#62;', ] ];

	const AS_BASE 		= 01;
	const AS_AIR 		= 02;

	const AS_FOLDER  	= 10;
	const AS_ESTIMATE	= 11;
	const AS_ITEM 	 	= 12;
	const AS_MOVE     	= 13;
	const AS_PING 		= 14;
	const AS_PROVISION	= 15;
	const AS_SETTING 	= 16;
	const AS_RESOLVE 	= 17;
	const AS_RIGTHM 	= 18;
	const AS_SEARCH 	= 19;
	const AS_CERT 		= 20;
	const AS_MRESPONSE	= 21;
	const AS_DocLib 	= 22;
	const AS_COMPOSE	= 23;
	const AS_FIND 		= 24;

	const AS_CONTACT 	= 30;
	const AS_CONTACT2	= 31;
	const AS_GAL 		= 32;
	const AS_CALENDAR	= 33;
	const AS_TASK 		= 34;
	const AS_NOTE 		= 35;
	const AS_MAIL 		= 36;
	const AS_MAIL2 		= 37;

	const CP 		    = [
		self::AS_BASE 		=> 'activesync:AirSyncBase',
		self::AS_AIR		=> 'activesync:AirSync',

		self::AS_FOLDER		=> 'activesync:FolderHierarchy',
		self::AS_ESTIMATE	=> 'activesync:GetItemEstimate',
		self::AS_ITEM		=> 'activesync:ItemOperations',
		self::AS_MOVE		=> 'activesync:Move',
		self::AS_PING		=> 'activesync:Ping',
		self::AS_PROVISION	=> 'activesync:Provision',
		self::AS_SETTING	=> 'activesync:Settings',
		self::AS_RESOLVE	=> 'activesync:ResolveRecipients',
		self::AS_RIGTHM		=> 'activesync:RightsManagement',
		self::AS_SEARCH		=> 'activesync:Search',
		self::AS_CERT		=> 'activesync:ValidateCert',
		self::AS_MRESPONSE	=> 'activesync:MeetingResponse',
		self::AS_DocLib		=> 'activesync:DocumentLibrary',
		self::AS_COMPOSE	=> 'activesync:ComposeMail',
		self::AS_FIND		=> 'activesybc:Find',

		self::AS_CONTACT	=> 'activesync:Contacts',
		self::AS_CONTACT2	=> 'activesync:Contacts2',
		self::AS_GAL		=> 'activesync:GAL',
		self::AS_CALENDAR	=> 'activesync:Calendar',
		self::AS_TASK		=> 'activesync:Tasks',
		self::AS_NOTE		=> 'activesync:Notes',
		self::AS_MAIL		=> 'activesync:Mail',
		self::AS_MAIL2		=> 'activesync:Mail2',
	];

	/**
	 * 	XML object
	 * 	@var \DOMDocument
	 */
	private $_doc;

	/**
	 * 	Position in object
	 * 	@var \DOMNode
	 */
	private $_pos;

	/**
	 * 	Search object
	 * 	@var \DOMXPath
	 */
	private $_xpath;

	/**
	 * 	List of found DOMNodes objects
	 * 	@var array
	 */
	private $_list;

	/**
	 * 	Object update counter
	 * 	@var int
	 */
	private $_upd;

	/**
	 *  Assigned code page
	 *  @var array
	 */
	private $_cp;

	/**
	 * 	Build class object
	 *
	 * 	@param 	- Optional: Object to copy data from
	 * 	@param	- TRUE = Copy from top (default); FALSE = Copy from current position
	 */
	public function __construct(XML $obj = NULL, bool $top = TRUE) {

		$this->_doc = new \DOMDocument('1.0', 'UTF-8');

		self::setTop();
		$this->_list = [];
		$this->_upd  = 0;
		$this->_cp   = NULL;

		if ($obj)
			self::loadXML($obj->saveXML($top));
	}

	/**
	 * 	Delete class object
	 */
	public function __destruct() {

		$this->_doc   = $this->_pos = NULL;
		$this->_xpath = '';
		$this->_list  = [];
		$this->_upd   = 0;
	}

   /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('XML handler'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Get/Set update status
	 *
	 *	@param	- 0 = Read update counter; 1 = Increment counter; -1 = Reset counter
	 * 	@return	- Number of times modified
	 */
	public function updObj(int $mod = 0): int {

		if ($mod > 0)
			$this->_upd++;
		elseif ($mod < 0)
			$this->_upd = 0;
        Debug::Msg('Update counter is "'.$this->_upd.'"'); //3

		return $this->_upd;
	}

	/**
	 * 	Get document type
	 *
	 * 	@return	- [ 'name', 'publicID', 'link' ]
	 */
	public function getDocType(): array {

		if (!isset($this->_doc->doctype))
			return [ '', '', '' ];

		return [ $this->_doc->doctype->name, $this->_doc->doctype->publicId, $this->_doc->doctype->systemId ];
	}

	/**
	 * 	Set document type
	 *
	 * 	@param	- URI
	 * 	@param	- DTD name
	 * 	@param	- HTTP link
	 */
	public function setDocType(string $uri, string $dtd, string $link): void {

		// be sure to clean strings
		$uri  = self::cnvStr($uri);
		$dtd  = self::cnvStr($dtd);
		$link = self::cnvStr($link);

		Debug::Msg('['.$uri.'], ['.$dtd.'] and ['.$link.']'); //3

		$dim  = new \DOMImplementation();
		$dtd  = $dim->createDocumentType($uri, $dtd, $link);
		$this->_doc = $dim->createDocument('', '', $dtd);

		self::setTop();
		$this->_upd++;
	}

	/**
	 *	Load XML string into object - Warning: You need to take care about cnvStr()!
	 *
	 *  @param	- XML formatted string
	 *  @return	- TRUE = Ok; FALSE = Error
	 */
	public function loadXML(string $data): bool {

		$this->_xpath = '';
		$this->_upd   = 0;
		$rc = $this->_doc->loadXML($data);
		if (!$rc) { //3
			Debug::Save(__FUNCTION__.'%d.xml', $data); //3
			ErrorHandler::Raise(10001, 'loadXML() error'); //3
		} //3
		$this->_pos = $this->_doc;

		return $rc;
	}

	/**
	 *	Save object as XML string
	 *
	 *	@param	- TRUE = Whole document; FALSE = From current position
	 * 	@param	- TRUE = Format output; FALSE = Compress output
	 * 	@return	- XML String
	 */
	public function saveXML(bool $top = TRUE, bool $fmt = FALSE): string {

		Debug::Msg(($top ? 'From top' : 'From current position').' '.($fmt ? 'format output' : 'compress outout')); //3
		$this->_doc->formatOutput = $fmt;

		return $this->_doc->saveXML($top ? $this->_doc : $this->_pos);
	}

	/**
	 * 	Get node type
	 *
	 * 	@return	- Node type
	 */
	public function getType(): int {

        Debug::Msg('['.$this->_pos->nodeName.'] = "'.self::NODETYP[$this->_pos->nodeType].'"'); //3
		return $this->_pos->nodeType;
	}

	/**
	 * 	Current node name
	 *
	 * 	@return	- Current node name
	 */
	public function getName(): string {

		if ($this->_pos->nodeName) //3
			Debug::Msg('['.$this->_pos->nodeName.']'); //3

		return $this->_pos->nodeName ? $this->_pos->nodeName : '';
	}

	/**
	 * 	Rename current node
	 *
	 * 	@param	- New name of node
	 */
	public function setName(string $name): void {

		$old = $this->_pos->nodeName; //3
		Debug::Msg('['.$old.'] = ['.$name.']'); //3

		// rename document?
		if ($this->_pos->parentNode->nodeType == XML_DOCUMENT_NODE) {
			$doc = new \DOMDocument('1.0', 'UTF-8');
			$p = $doc->appendChild($doc->createElement($name));
			// don't forget to copy attributes
			foreach ($this->_pos->attributes as $a)
				$p->setAttribute($a->nodeName, $a->nodeValue);
			foreach ($this->_pos->childNodes as $c)
				$p->appendChild($doc->importNode($c->cloneNode(TRUE), TRUE));
			// swap variables
			$this->_doc = $doc;
			$this->_pos = $p;
		} else {
			// create new node on parent level
			$p = $this->_pos->parentNode->appendChild($this->_doc->createElement($name));
			// swap all child nodes
			foreach ($this->_pos->childNodes as $c)
				$p->appendChild($c->cloneNode(TRUE));
			// swap attributes
			foreach ($this->_pos->attributes as $a)
				$p->setAttribute($a->nodeName, $a->nodeValue);
			// replace node
			$this->_pos = $this->_pos->parentNode->replaceChild($p, $this->_pos);
		}

		$this->_xpath = '';
		$this->_upd++;
	}

	/**
	 * 	Get current node value
	 *
	 * 	@return	- Current node value
	 */
	public function getVal(): string {

		// check for supported nodes
		if (!self::hasChild(XML_TEXT_NODE) && !self::hasChild(XML_CDATA_SECTION_NODE)) {
			Debug::Msg('['.$this->_pos->nodeName.'] = ""'); //3
			return '';
		}

		Debug::Msg('['.$this->_pos->nodeName.'] = "'.$this->_pos->nodeValue.'"'); //3

		return self::cnvStr(strval($this->_pos->nodeValue), FALSE);
	}

	/**
	 * 	Set current node value
	 *
	 * 	@param	- New value to set
	 */
	public function setVal(?string $val): void {

		Debug::Msg('['.$this->_pos->nodeName.'] "'.str_replace([ "\n", "\r" ], [ '.',  '' ], ($val ? $val : '')).'"'); //3

		$this->_pos->nodeValue = ($val ? self::cnvStr($val) : $val);

		$this->_upd++;
	}

	/**
	 * 	Delete variable
	 *
	 * 	@param	- Name of variable; NULL = Delete current node
	 * 	@param	- TRUE = Delete all; FALSE = Delete first found field
	 * 	@return	- TRUE = Ok; FALSE = Not found
	 */
	public function delVar(?string $name = NULL, bool $all = TRUE): bool {

		$this->_upd++;

		if (!$name) {
			Debug::Msg('['.$this->_pos->nodeName.'] - all child nodes'); //3
			$p = $this->_pos;
			if ($this->_pos = $this->_pos->parentNode)
    			$this->_pos->removeChild($p);
			return TRUE;
		}

		$p = $this->_doc->getElementsByTagName($name);
		if ($p->length) {
			if ($all) {
				while ($p->length)
					$p->item(0)->parentNode->removeChild($p->item(0));
			} else
				$p->item(0)->parentNode->removeChild($p->item(0));
			Debug::Msg('['.$name.'] - '.($all ? 'all nodes' : 'first node')); //3
			return TRUE;
		}

		if (!$all) //3
			Debug::Msg('['.$name.'] - not found'); //3

		return FALSE;
	}

	/**
	 * 	Get variable
	 *
	 * 	@param	- Name of variable
	 * 	@param 	- TRUE = Search whole document; FALSE = Search from current position
	 * 	@return	- Variable content or NULL = Not found
	 */
	public function getVar(string $name, bool $top = TRUE): ?string {

		$p = $top ? $this->_doc : $this->_pos;
		if (!$p) {
			Debug::Warn('-- ['.$name.'] from '.($top ? 'top' : 'current').' position - No position'); //3
			return NULL;
		}

		$p = $p->getElementsByTagName($name);
		if (!$p->length) {
			Debug::Msg('['.$name.'] from '.($top ? 'top' : 'current').' position - Not found'); //3
			return NULL;
		} else
			$this->_pos = $p->item(0);

		$val = self::getVal();

		if (is_array($val))	//3
			Debug::Msg($val, '['.$name.'] from '.($top ? 'top' : 'curremt').' position'); //3
	    else { //3
	        $v = str_replace([ "\n", "\r" ], [ '.', '' ], $val); //3
			if (strlen($v) > 128) //3
                $v = substr($v, 0, 128).' ['.strlen($v).'-CUT@128]'; //3
			Debug::Msg('['.$name.'] from '.($top ? 'top' : 'current').' position = "'.$v.'"'); //3
	    } //3

		return $val;
	}

	/**
	 * 	Add variable
	 *
	 * 	@param	- Name of variable
	 * 	@param	- String value to store. NULL = A new sub record is created (default)
	 * 	@param	- TRUE = Save data as CDATA; FALSE = Ignore
	 * 	@param	- Optional attributes to add [key => Val]
	 */
	public function addVar(string $name, ?string $val = NULL, bool $cdata = FALSE, array $attr = []): void {

		$this->_upd++;

		// if node has text content, switch to parent
		if (self::hasChild(XML_TEXT_NODE) && isset($this->_pos->parentNode))
			$this->_pos = $this->_pos->parentNode;

		if ($val === NULL) {
			Debug::Msg('['.$name.']'); //3
			$p = $this->_pos = $this->_pos->appendChild($this->_doc->createElement($name));
		} else {
		    // convert characters to internal format
			if (!$cdata)
		    	$val = self::cnvStr($val);

			if ($cdata) {
				Debug::Msg('['.$name.'] "'.str_replace([ "\n", "\r"] , [ '.', '' ], $val).'" as "CDATA"'); //3
				$this->_pos = $p = $this->_pos->appendChild($this->_doc->createElement($name));
				$p = $this->_pos;
				$this->_pos->appendChild($this->_doc->createCDATASection($val));
			} else {
       	        $v = str_replace([ "\n", "\r" ], [ '.', '' ], $val); //3
       			if (strlen($v) > 128) //3
					$v = substr($v, 0, 1280).' ['.strlen($v).'-CUT@128]'; //3
				Debug::Msg('['.$name.'] "'.$v.'"'); //3
				if (strlen($val))
					$p = $this->_pos = $this->_pos->appendChild($this->_doc->createElement($name, $val));
				else
					$p = $this->_pos->appendChild($this->_doc->createElement($name));
			}
		}

		// swap attributes
		if (is_array($attr)) {
		    foreach ($attr as $k => $v)
   	       		$p->setAttribute($k, self::cnvStr(strval($v)));
		}
	}

	/**
	 * 	Update (or add) variable
	 *
	 * 	@param	- Name of variable
	 * 	@param	- String value to store
	 * 	@param 	- TRUE = Search whole document; FALSE = Search from current position
	 * 	@return	- Old value stored
	 */
	public function updVar(string $name, string $val, bool $top = TRUE): string {

		if (($v = self::getVar($name, $top)) === NULL) {
			if (is_array($val)) //3
				Debug::Msg($val, '['.$name.'] = "array()" from '.($top ? 'top' : 'current').' position'); //3
			else { //3
    	        $v = str_replace([ "\n", "\r" ], [ '.', '' ], $val); //3
    			if (strlen($v) > 128) //3
                    $v = substr($v, 0, 1280).' ['.strlen($v).'-CUT@128]'; //3
			    Debug::Msg('['.$name.'] "'.$v.'" from '.($top ? 'top' : 'current').' position'); //3
			} //3
			self::addVar($name, $val);
		} else {
			if (is_array($val))	//3
				Debug::Msg($val, '['.$name.'] "array()" from '.($top ? 'top' : 'current').' position'); //3
			else { //3
    	        $v = str_replace([ "\n", "\r" ], [ '.', '' ], $v); //3
    			if (strlen($v) > 128) //3
                    $v = substr($v, 0, 1280).' ['.strlen($v).'-CUT@128]'; //3
			    Debug::Msg('['.$name.'] "'.$v.'" from '.($top ? 'top' : 'current').' positon'); //3
			} //3
			self::setVal($val);
		}

		return strval($v);
	}

	/**
	 * 	Add comment
	 *
	 * 	@param	- Comment
	 */
	public function addComment(string $text): void {
		$this->_pos->appendChild($this->_doc->createComment(' '.$text.' '));
	}

	/**
	 * 	Search variable in object
	 *
	 * 	@param	- xpath query string
	 * 	@param 	- TRUE = Search whole document; FALSE = Search from current position
	 * 	@return	- Number of items found
	 */
	public function xpath(string $xpath, bool $top = TRUE): int {

		$this->_xpath = new \DOMXPath($this->_doc);

		$this->_list = [];
		if (!($l = $this->_xpath->query($xpath, $top ? $this->_doc : $this->_pos))) {
			Debug::Warn('"'.$xpath.'" from '.($top ? 'top' : 'current').' position - Failed'); //3
		    return 0;
		}

		for ($i=0; $i < $l->length; $i++)
			$this->_list[] = $l->item($i);

		Debug::Msg('"'.$xpath.'" from '.($top ? 'top' : 'current').' position - '.count($this->_list).' elements found'); //3

		return count($this->_list);
	}

	/**
	 * 	Search value in object and get parent
	 *
	 * 	@param	- Node name
	 * 	@param	- Node value
	 * 	@param	- Optional parent node path (e.g. /ab/de/cd/)
	 * 	@param 	- TRUE = Search whole document; FALSE = Search from current position
	 * 	@return	- Number of items found
	 */
	public function xvalue(string $name, string $val, ?string $par = NULL, bool $top = TRUE): int {

		if (!$this->_xpath)
			$this->_xpath = new \DOMXPath($this->_doc);

		// nothing found
		$this->_list = [];

		// locate all nodes in document
		if (!($l = $this->_xpath->query('//'.$name.'/.', $top ? $this->_doc : $this->_pos))) {
			Debug::Err('"'.$name.'", "'.$val.'", "'.$par.'" from '.($top ? 'top' : 'curremt').' position - Failed'); //3
			return 0;
		}

		$path = $par ? array_reverse(explode('/', substr($par, 1))) : [];

		// set termination flag
		$path[] = '#';

		// find value
		$val = self::cnvStr($val);
		for ($i=0; $i < $l->length; $i++) {
			if (strcasecmp($l->item($i)->nodeValue, $val))
				continue;
			$p = $l->item($i)->parentNode;
			foreach ($path as $n) {
				if (!$n)
					continue;
				if ($n == '#')
					break;
				if ($n != $p->nodeName)
					break;
				$p = $p->parentNode;
			}
			if ($n == '#')
				$this->_list[] = $l->item($i)->parentNode;
		}
		Debug::Msg('"'.$name.'", "'.$val.'", "'.$par.'" from '.($top ? 'top' : 'current').' position- '. //3
				   count($this->_list).' elements found'); //3

		return count($this->_list);
	}

	/**
	 * 	Check if XML node has valid child nodes
	 *
	 * 	@param	- Node type; 0 = Any
	 * 	@return	- TRUE = Yes; FALSE = No
	 */
	public function hasChild(int $typ = XML_ELEMENT_NODE): bool {

		if ($this->_pos->hasChildNodes()) {
			foreach ($this->_pos->childNodes as $c) {
				if ($c->nodeType == $typ || $typ === 0) {
					Debug::Msg('['.$this->_pos->nodeName.'] TRUE'); //3
					return TRUE;
				}
			}
		}
		Debug::Msg('['.$this->_pos->nodeName.'] FALSE'); //3

		return FALSE;
	}

	/**
	 * 	Get XML_ELEMENT_NODE child nodes
	 *
	 * 	@param	- Name of variable to search or NULL for current position
	 * 	@param	- TRUE = Search from top; FALSE = Search from current position
	 * 	@return - Number of child(s) found
	 */
	public function getChild(?string $name = NULL, bool $top = TRUE): int {

		$this->_list = [];

		if ($name && ($this->_pos->nodeName != $name || $top)) {
			if (self::getVar($name, $top) === NULL) {
				Debug::Msg('['.$name.'] from '.($top ? 'top' : 'current').' position - '.count($this->_list).' elements found'); //3
				return 0;
			}
		}

		if (self::hasChild()) {
			foreach ($this->_pos->childNodes as $node) {
				if ($node->nodeType == XML_ELEMENT_NODE)
					$this->_list[] = $node;
			}
		}

		Debug::Msg('['.$name.'] from '.($top ? 'top' : 'current').' position - '.count($this->_list).' elements found'); //3

		return count($this->_list);
	}

	/**
	 *	Set position to item from list
	 *
	 *	@return	- Item value; NULL = No more items
	 */
	public function getItem(): ?string {

		if (count($this->_list)) {
			$this->_pos = array_shift($this->_list);
			$rc = self::getVal();
			Debug::Msg('['.$this->_pos->nodeName.'] TRUE'); //3
			return $rc;
		}

		Debug::Msg('['.$this->_pos->nodeName.'] FALSE'); //3

		return NULL;
	}

	/**
	 * 	Get attribute from current node
	 *
	 * 	@param	- Optional: Attribute name or (NULL for all)
	 * 	@return	- Attribute content or [ $name => $value ]
	 */
	public function getAttr(?string $name = NULL) {

		if (!$name) {
			$val = [];
			if ($this->_pos->hasAttributes()) {
				foreach ($this->_pos->attributes as $attr) {
				    $val[$attr->nodeName] = self::cnvStr($attr->nodeValue, FALSE);
					Debug::Msg('['.$this->_pos->nodeName.'] -> "'.$attr->nodeName.'"="'.$attr->nodeValue.'"');	//3
				}
			}
		} else {
			if ($this->_pos->nodeType != XML_ELEMENT_NODE)
				$val = [];
			else
			    $val = self::cnvStr($this->_pos->getAttribute($name), FALSE);
			Debug::Msg('['.$this->_pos->nodeName.'] -> "'.$name.'"="'.$val.'"'); //3
		}

		return $val;
	}

	/**
	 * 	Set attribute to current node
	 *
	 * 	@param	- [ Attribute name, Attribute value ]
	 */
	public function setAttr(array $attr): void {

		$this->_upd++;
		foreach ($attr as $k => $v) {
    		Debug::Msg('['.$this->_pos->nodeName.'] "'.$k.'" = "'.$v.'"'); //3
    		$this->_pos->setAttribute($k, self::cnvStr($v));
		}
	}

	/**
	 * 	Delete attribute
	 *
	 * 	@param	- Attribute name
	 */
	public function delAttr(string $name): void {

		$this->_upd++;

		Debug::Msg('['.$this->_pos->nodeName.'] "'.$name.'"'); //3

		$this->_pos->removeattribute($name);
	}

	/**
	 * 	Load XML document from file - Warning: You need to take care about cnvStr()!
	 *
	 * 	@param	- File name
	 * 	@param	- TRUE = Append to existing document; FALSE = Replace content
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function loadFile(string $file, bool $append = FALSE): bool {

		$this->_upd = 0;

		if (!file_exists($file)) {
			ErrorHandler::Raise(10001, 'loadXML() - file not found "'.$file.'"'); //3
			return FALSE;
		}

		$wrk = @file_get_contents($file);

		// convert all namespace tags "xmlsns=" to "xml-ns="
		$wrk = str_replace([ 'xmlns=', ], [ 'xml-ns=', ], $wrk);

		// remove XML declaration (REQUIRED)
		if ($append) {
			$wrk = preg_replace('/<\?xml.*\?>/', '', $wrk);
			// remove comments
			$wrk = preg_replace('/<!--(.*?)-->/si', '', $wrk);
			// remove DOCTYPE
			$wrk = preg_replace('/(.*)(<!.*">)(.*)/', '${1}${3}', $wrk);
		}

		// stripp off blank lines and HTML new lines
		$wrk = preg_replace('/>\s+</', '><', $wrk);

		if ($append) {
			$seg = $this->_doc->createDocumentFragment();
			if ($rc = $seg->appendXML($wrk))
				$this->_doc->appendChild($seg);
		} else {
		    $rc = $this->_doc->loadXML($wrk);
			$this->_pos = $this->_doc;
			$this->_xpath = '';
		}

		if ($rc) //3
			Debug::Msg('"'.$file.'" - '.($append ? 'Appending' : 'Replace XML content')); //3
		else { //3
			Debug::Save(__FUNCTION__.'%d.xml', $wrk); //3
			if (!Debug::$Conf['Script']) //3
				ErrorHandler::Raise(10001, 'loadXML() error for "'.$file.'"'); //3
		} //3

		return $rc;
	}

	/**
	 * 	Save XML document to file
	 *
	 * 	@param	- File name
	 * 	@param	- TRUE = Format output; FALSE = Compress output
	 * 	@return	- TRUE = Ok; FALSE = Error
	 */
	public function saveFile(string $file, bool $fmt = FALSE): bool {

		$this->_doc->formatOutput = $fmt;

   		// convert all namespace tags "xml-ns" to "xmlns"
		$wrk = str_replace([ 'xml-ns=', 'xml-ns:', ], [ 'xmlns=',  'xmlns:', ], $this->_doc->saveXML($this->_doc));

		// do not use DOMDocument::save - it sometimes crashes without any reason
		// $rc = $this->_doc->save($file);
		$rc = file_put_contents($file, $wrk);

		if ($rc === FALSE) //3
			Debug::Err('Saving XML object to file "'.$file.'" - failed'); //3
		else //3
			Debug::Msg('Saving to file "'.$file.'" - ('.$rc.') bytes'); //3

		return $rc === FALSE ? $rc : TRUE;
	}

	/**
	 * 	Append one node to another
	 *
	 * 	@param	- Node to append
	 * 	@param	- TRUE = Append whole document; FALSE = Append from current position
	 * 	@param	- TRUE = As first child node; FALSE = As child node
	 */
	public function append(XML &$node, bool $top = TRUE, bool $first = FALSE): void {

		$this->_upd++;

		$xml = new \DOMDocument('1.0', 'UTF-8');
		$rc  = $xml->loadXML($node->saveXML($top));
		if (!$rc) {
			Debug::Save(__FUNCTION__.'%d.xml', $node->saveXML($top)); //3
			ErrorHandler::Raise(10001, 'loadXML() error'); //3
			return;
		}

		// if node has text content, switch to parent
		if (self::hasChild(XML_TEXT_NODE))
			$this->_pos = $this->_pos->parentNode;

		// does already have child nodes
		if ($first && self::hasChild()) {
			foreach ($this->_pos->childNodes as $n) {
				if ($n->nodeType == XML_ELEMENT_NODE)
					break;
			}
			$n->parentNode->insertBefore($this->_doc->importNode($xml->documentElement, TRUE), $n);
		} else
			$this->_pos->appendChild($this->_doc->importNode($xml->documentElement, TRUE));

    	$this->_cp = NULL;
	}

	/**
	 * 	Duplicate current node
	 *
	 * 	@param 	- Number of additional nodes
	 */
	public function dupVar(int $cnt): void {

		if ($cnt < 1)
			return;

		$p   = self::savePos();
		$dup = new XML($this, FALSE);
		$tag = $t = self::getName();
		self::setParent();
		for ($n=1; $n <= $cnt; $n++) {
			$dup->getVar($t);
			$dup->setName($t = $tag.$n);
			$dup->setTop();
			self::append($dup, TRUE, FALSE);
		}
		for ($n=1; $n <= $cnt; $n++) {
			if (self::getVar($tag.$n) === NULL)
				break;
			self::setName($tag);
		}
		self::restorePos($p);
	}

	/**
	 * 	Convert object to HTML output
	 *
	 * 	@param	- TRUE = From current position; FALSE = Current position
	 * 	@param 	- Optional line prefix
	 * 	@return	- HTML string
	 */
	public function mkHTML(bool $top = TRUE, ?string $pref = NULL): string {

		$wrk = str_replace("\r", '&#13;', self::saveXML($top, TRUE));
		$str = '';
		$inco = FALSE;

		$a = explode("\n", $wrk);
		if (!strlen($a[count($a) - 1]))
			array_pop($a);

		foreach ($a as $rec) {
			if (strpos($rec, '<!--') !== FALSE)
				$inco = TRUE;
			if ($inco)
				$str .= '<code style="'.Util::CSS_INFO.'">'.$pref.str_replace(' ', '&nbsp;', htmlspecialchars($rec)).'</code><br />';
			else {
				if (strlen($rec) > 1024)
					$rec = substr($rec, 0, 1024).'['.strlen($rec).'-CUT@1024]';
				$str .= '<code style="'.Util::CSS_CODE.'">'.$pref.str_replace(' ', '&nbsp;', htmlspecialchars($rec)).'</code><br />';
			}
			if (strpos($rec, '-->') !== FALSE)
				$inco = FALSE;
		}

		return $str;
	}

	/**
	 * 	Set position to parent node
	 *
	 * 	@return - TRUE = Ok; FALSE = No parent available
	 */
	public function setParent(): bool {

		if (!$this->_pos->parentNode)
			return FALSE;
		$this->_pos = $this->_pos->parentNode;
		$this->_xpath = '';

		return TRUE;
	}

	/**
	 * 	Set position to next node
	 */
	public function setNext(): void {

		if ($this->_pos->firstChild) {
			$this->_pos = $this->_pos->firstChild;
			return;
		}

		$pos = $this->_pos;

		do {
			if (!($pos = $pos->parentNode))
				return;
		} while(!$pos->nextSibling && $pos->nodeType != XML_DOCUMENT_NODE);

		if ($pos->nodeType != XML_DOCUMENT_NODE)
			$this->_pos = $pos;

		do {
			if ($this->_pos->nextSibling)
				$this->_pos = $this->_pos->nextSibling;
		} while ($this->_pos->nextSibling && $this->_pos->nodeType != XML_ELEMENT_NODE);

		$this->_xpath = '';
	}

	/**
	 * 	Save position
	 *
	 * 	@return	- Current position
	 */
	public function savePos(): array {
		return [ $this->_pos, $this->_list, $this->_xpath ];
	}

	/**
	 * 	Restore position
	 *
	 * 	@param	- Position to restore
	 */
	public function restorePos(array $pos): void {
		list( $this->_pos, $this->_list, $this->_xpath ) = $pos;
	}

	/**
	 * 	Swap XML to associative array
	 *
	 *  @param  - Optional level
	 * 	@return - [] = [ T=Tag; P=Parm; D=Data ]
	 */
	public function XML2Array(int $lvl = 0): array {

		$recs = $rec = [];

		$p = $this->savePos();

		$rec['T'] = $this->getName();
		$rec['P'] = $this->getAttr();
		$this->getChild(NULL, FALSE);
		if(!$lvl) {
			$rec['D'] = self::XML2Array(1);
			$recs[] = $rec;
		} else {
			while (($v = $this->getItem()) !== NULL) {
				$rec['T'] = $this->getName();
				$rec['P'] = $this->getAttr();
				if ($this->hasChild())
					$rec['D'] = self::XML2Array(1);
				else
					$rec['D'] = $v;
				$recs[] = $rec;
			}
		}
		$this->restorePos($p);

		return $recs;
	}

	/**
	 * 	Swap associative array XML
	 *
	 * 	@param	- [ [T] => Tag; [P] => Parm; [D] => Data ]
	 */
	public function Array2XML(array $arr): void {

		// single record?
		if (isset($arr['T']))
			$arr = [ $arr ];

		// walk down all arrays
		foreach ($arr as $unused => $v) {

			if (!isset($v['T'])) {
				self::Array2XML($v);
				continue;
			}

			if (!is_array($v['D']))
				self::addVar($v['T'], $v['D'], FALSE, $v['P']);
			else {
				$p = self::savePos();
				self::addVar($v['T'], NULL, FALSE, $v['P']);
				self::Array2XML($v['D']);
				self::restorePos($p);
			}
		}
		$unused; // disable Eclipse warning
	}

	/**
     *	Set Activesync code page
     *
     *	@param  - Code page number
     *	@param  - TRUE = Force setting; FALSE = Only change if required
     *	@return - [ 'xml-ns' => code page name ]
     */
    public function setCP(int $no, bool $force = FALSE): array {
		$old = ($force ? '' : $this->_cp);
		$this->_cp = self::CP[$no];
		return $old != $this->_cp ? [ 'xml-ns' => $this->_cp ] : [];
    }

	/**
	 * 	Set position to top
	 */
	public function setTop(): void {
		$this->_doc->preserveWhiteSpace = FALSE;
		$this->_pos = $this->_doc;
		$this->_xpath = '';
	}

	/**
	 *  Convert HTML entities and filter unallowed control characters but leave \n and \t
	 *
	 *  @param  - Text to convert (must be UTF-8 encoded)
	 *  @param  - TRUE = Convert HTML entities to internal; FALSE = Decode HTML entities to external
	 *  @return - Converted string
	 */
	static function cnvStr(string $str, bool $cnv = TRUE): string {

	    if ($cnv) {
	    	// https://www.php.net/manual/en/parle.regex.unicodecharclass.php
	    	if (($wrk = preg_replace('/(?!\n|\t)[\p{Cc}]/u', '', $str)) === NULL) {
	    		if (!Debug::$Conf['Script']) { //3
	    			Debug::Err('cnvStr() - error converting string'); //3
	    			Debug::$Conf['Script'] = 'Exit'; //3
	    		} //3
	    		return bin2hex($str);
	    	}
			return str_replace(self::ENTITY[0], self::ENTITY[1], $wrk);
	    } else
			return str_replace(self::ENTITY[1], self::ENTITY[0], $str);
	}

}

?>
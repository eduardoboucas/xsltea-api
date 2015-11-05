<?php

class XSLTeaParser {
	private $namespace = 'http://www.w3.org/1999/XSL/Transform';
	private $utilitiesPath = 'utilitiesSrc/';
	
	private $xml;
	private $xsl;
	private $imports = array();
	private $errors = array();
	private $utilities = array();
	private $usedImports = array();

	public function __construct() {
		$this->utilities = $this->loadServerUtilities();
	}

	/**
	 * Set the content of the XML document
	 * 
	 * @param String $xml XML content
	 * @return DOMDocument
	 */
	public function setXML($xml) {
		$xmlDoc = new DOMDocument;
		
		try {
			$xmlDoc->loadXML($xml);

			$this->xml = $xmlDoc;
		} catch (Exception $exception) {
			$this->errors['xmlparse'][] = $exception->getMessage();
		}

		return $this->xml;
	}

	/**
	 * Set the content of the XSL document
	 * 
	 * @param Stringe $xsl XSL content
	 * @return DOMDocument
	 */
	public function setXSL($xsl) {
		$xslDoc = new DOMDocument;

		try {
			$xslDoc->loadXML($xsl);

			$this->xsl = $xslDoc;
		} catch (Exception $exception) {
			$this->errors['xslparse'][] = $exception->getMessage();
		}

		return $this->xsl;
	}

	/**
	 * Set the import files
	 * 
	 * @param Array $imports The imports array. Path names are keys, XSL content are values 
	 * @return Array
	 */
	public function setImports($imports) {
		$errors = false;
		$importsArray = array();

		foreach ($imports as $name => $content) {
			try {
				$importDoc = new DOMDocument;
				$importDoc->loadXML($content);

				$importsArray[$name] = $importDoc;
			} catch (Exception $exception) {
				$this->errors['imports'][$name] = $exception->getMessage();
				$errors = true;
			}
		}

		$this->imports = $importsArray;
	}	

	/**
	 * Read the utilities manifest file and loads them into an array
	 * 
	 * @return Array
	 */
	private function loadServerUtilities() {
		$rawData = file_get_contents('utilities.json');
		$data = json_decode($rawData);
		
		foreach ($data as $utility) {
			$xmlDoc = new DOMDocument;
			$xmlDoc->load($this->utilitiesPath . $utility->path);

			$this->utilities[$utility->path] = $xmlDoc;
		}

		return $this->utilities;
	}

	/**
	 * Return the utlities array
	 * 
	 * @return type
	 */
	public function getServerUtilities() {
		return $this->utilities;
	}

	/**
	 * Take all the nodes from a stylesheet and insert them into another at
	 * a certain position
	 * 
	 * @param DOMDocument $child The child stylesheet (origin)
	 * @param DOMDocument $parent The parent stylesheet (destination)
	 * @param DOMNode $position The position at which to insert the nodes (the successor)
	 * @return DOMDocument The merged stylesheet
	 */
	private function injectStylesheet($child, $parent, $position) {
		$childNodes = $child->documentElement->childNodes;
		
		foreach ($childNodes as $childNode) {
			$node = $parent->importNode($childNode, true);
			$parent->documentElement->insertBefore($node, $position);
		}

		return $parent;
	}

	/**
	 * Remove import declarations from an XSL document. Returns the document
	 * with the declarations removed.
	 * 
	 * @param DOMDocument $document The XSL document
	 * @return DOMDocument
	 */
	private function deleteImportDeclarations($document) {
		$imports = $document->getElementsByTagNameNS($this->namespace, 'import');

		for ($i = 0, $numImports = $imports->length; $i < $numImports; $i++) {
			$import = $imports->item(0);
			
			$import->parentNode->removeChild($import);
		}

		return $document;
	}

	/**
	 * Resolve import declarations in an XSL document, matching them against import files
	 * sent in the request and also server-side utilities. Injects the dependencies in the
	 * main document.
	 * 
	 * @param DOMDocument $document Main XSL document
	 * @throws Exception If there are any missing dependencies
	 * @return DOMDocument The document with the dependencies resolved
	 */
	private function resolveImports($document) {
		$documentImports = $document->getElementsByTagNameNS($this->namespace, 'import');
		$missingDependencies = array();

		foreach ($documentImports as $import) {
			$href = $import->attributes->getNamedItem('href')->textContent;
			$importDoc = null;

			if (array_key_exists($href, $this->imports)) {
				// Is the dependency provided in the request?
				$importDoc = $this->imports[$href];
				$this->usedImports[] = $href;
			} else if (array_key_exists($href, $this->utilities)) {
				// Is the dependency a server-side utility?
				$importDoc = $this->utilities[$href];
			}

			if ($importDoc) {
				$importPosition = $document->documentElement->childNodes->item(0);
				$document = $this->injectStylesheet($importDoc, $document, $importPosition);				
			} else {
				$missingDependencies[] = $href;
			}
		}

		// Remove import declarations
		$document = $this->deleteImportDeclarations($document);

		if (count($missingDependencies)) {
			throw new Exception('Missing dependencies: ' . implode(', ', $missingDependencies));
		}

		return $document;
	}

	/**
	 * Transform the XML document with the XSL stylesheet
	 * 
	 * @return Array
	 */
	public function transform() {
		$response = array();

		if (isset($this->errors['xmlparse']) || 
			isset($this->errors['xslparse']) || 
			isset($this->errors['imports'])) {
			$response['errors'] = $this->errors;

			return $response;
		}

		try {
			$this->xsl = $this->resolveImports($this->xsl);

			$proc = new XSLTProcessor;
			$proc->importStyleSheet($this->xsl);

			$timeStart = microtime(true);

			$response['result'] = $proc->transformToXML($this->xml);

			$timeEnd = microtime(true);

			$response['time'] = $timeEnd - $timeStart;			
		} catch (Exception $exception) {
			$this->errors['transform'] = $exception->getMessage();
		}

		if (count($this->errors)) {
			$response['errors'] = $this->errors;
		}

		$response['imports'] = $this->usedImports;

		return $response;
	}
}

?>
<?php

class XSLTeaParser {
	private $namespace = 'http://www.w3.org/1999/XSL/Transform';
	private $importBasePath = 'utilities/';

	private function injectStylesheet($child, $parent, $position) {
		$childNodes = $child->documentElement->childNodes;
		
		foreach ($childNodes as $childNode) {
			$node = $parent->importNode($childNode, true);
			$parent->documentElement->insertBefore($node, $position);
		}

		return $parent;
	}

	private function resolveImports($stylesheet) {
		$imports = $stylesheet->getElementsByTagNameNS($this->namespace, 'import');

		foreach ($imports as $import) {
			$importPath = $this->importBasePath . $import->attributes->getNamedItem('href')->value;

			$importDoc = new DOMDocument;
			$importDoc->load($importPath);

			$stylesheet = $this->injectStylesheet($importDoc, $stylesheet, $import);
			$import->parentNode->removeChild($import);
		}

		return $stylesheet;
	}

	public function parse($xml, $xsl, $utilities = array()) {
		$errors = array();
		$response = array();

		/*----------  Parsing XML document  ----------*/
		
		$xmlDoc = new DOMDocument;
		
		try {
			$xmlDoc->loadXML($xml);	
		} catch (Exception $exception) {
			$errors['xmlparse'][] = $exception->getMessage();
		}

		/*----------  Parsing XSL document  ----------*/
		
		$xslDoc = new DOMDocument;
		$xslDoc->preserveWhiteSpace = true;
		$xslDoc->formatOutput = true;

		try {
			$xslDoc->loadXML($xsl);
		} catch (Exception $exception) {
			$errors['xslparse'][] = $exception->getMessage();
		}

		$this->resolveImports($xslDoc);

		$proc = new XSLTProcessor;

		$xslDoc = $this->resolveImports($xslDoc);

		if (!count($errors)) {
			$proc->importStyleSheet($xslDoc);

			try {
				$timeStart = microtime(true);

				$response['result'] = $proc->transformToXML($xmlDoc);

				$timeEnd = microtime(true);

				$response['time'] = $timeEnd - $timeStart;
			} catch (Exception $exception) {
				$errors['transform'] = $exception->getMessage();
			}
		}

		if (count($errors)) {
			$response['errors'] = $errors;
		}

		return $response;
	}	
}

?>
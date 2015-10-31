<?php

require 'vendor/autoload.php';

/**
 *
 * Debug
 *
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);

/**
 *
 * Application
 *
 */

$app = new \Slim\Slim();
$app->get('/test', function () {
	// Load the XML source
	$xml = new DOMDocument;
	$xml->load('collection.xml');

	$xsl = new DOMDocument;
	$xsl->load('collection.xsl');

	// Configure the transformer
	$proc = new XSLTProcessor;
	$proc->importStyleSheet($xsl); // attach the xsl rules

	echo $proc->transformToXML($xml);    
});

$app->run();

?>
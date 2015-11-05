<?php

require 'vendor/autoload.php';
require 'lib/XSLTeaParser.class.php';

/**
 *
 * Debug
 *
 */

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

/**
 *
 * Application
 *
 */

$app = new \Slim\Slim();

$app->get('/utilities', function () use ($app) {
	$response = $app->response();
	$response->header('Access-Control-Allow-Origin', '*');

	$response->write(file_get_contents('utilities.json'));
});

$app->post('/parse', function () use ($app) {
	$response = $app->response();
	$response->header('Access-Control-Allow-Origin', '*');

	$variables = array();
	parse_str($app->request->getBody(), $variables);

	if (isset($variables['xml']) && isset($variables['xsl'])) {
		$parser = new XSLTeaParser();
		$parser->setXML($variables['xml']);
		$parser->setXSL($variables['xsl']);
		
		if (isset($variables['import'])) {
			$parser->setImports($variables['import']);	
		}
		
		$result = $parser->transform();

		if (isset($result['errors'])) {
			$app->response()->status(500);	
		}

		$response->write(json_encode($result));
	}
});

$app->run();

?>
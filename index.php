<?php

require 'vendor/autoload.php';
require 'lib/XSLTeaParser.class.php';

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
$app->post('/parse', function () use ($app) {
	$variables = array();
	parse_str($app->request->getBody(), $variables);

	if (isset($variables['xml']) && isset($variables['xsl'])) {
		$parser = new XSLTeaParser();
		$result = $parser->parse($variables['xml'], $variables['xsl']);

		if (isset($result['errors'])) {
			$app->response()->status(500);	
		}

		echo json_encode($result);
	}
});

$app->run();

?>
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Autovel\FileEditor;
use Autovel\ModelBuilder;
use Autovel\ControllerBuilder;
use Autovel\ViewBuilder;


$laravelRootPath = __DIR__ . "/../"; // change the path based on where Laravel is installed


// Read the config File 
$filename = __DIR__ . '/autovel.json';
$configFile = new Autovel\FileEditor ($filename);
if ($configFile->exists() === false)
{
	exit($filename . ' does not exist!');
};
$params = json_decode($configFile->get(), false);




// process the resources one by one
$resources            = $params->resources;
echo "Total Resources = " . count($resources) . "\n";

foreach ($resources as $r) 
{
	$modelName  = $r->resource;
	$columns    = $r->columns;
	$tableName  = $r->table;

	echo "Processing Resource [" . $modelName . "]...\n";

	//
	// Model - build the model and the migration
	//
	echo "\t--> Creating model and migrations.\n";
	$model = new ModelBuilder ($laravelRootPath, $modelName, $tableName, $r->overwrite);
	$model->addAttributes ($r->columns)
		->addMigration ()
		->migrate($params->migrate);

	
	// 
	// Controller - add controller and router entries
	//
	echo "\t--> Creating controller and router entries.\n";
	$operations = (property_exists($r, "operations") ? $r->operations : ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
	$controller = new ControllerBuilder ($laravelRootPath, $modelName, $tableName, $r->overwrite);
	$controller->addOperations($operations)->addRoutes();

	
	//
	// Views - adding required views
	//
	echo "\t--> Adding Resource Views.\n";
	$view = new ViewBuilder($laravelRootPath, $modelName, $tableName, $r->overwrite);
	$view->createSPAView();

} // foreach








<?php

/**
 * This is the entry point to autovel - Laravel Automatic
 * Code Generator program. 
 * 
 * @author Akash Mitra (akash.mitra@gmail.com)
 * @version 1.0
 * 
 */
require_once __DIR__ . '/vendor/autoload.php';

use Autovel\FileEditor;
use Autovel\ModelBuilder;
use Autovel\ControllerBuilder;
use Autovel\ViewBuilder;

// Read the config File 
$params = _loadParamsFromConfigFile ();                      // read config file
$laravelRootPath = _getLaravelRootDirectory($params);        // get Laravel install directory
$resources = $params->resources;                             // get a list of all resources

echo "Total Resources = " . count($resources) . "\n";

foreach ($resources as $r) 								     // process the resources one by one
{                                 
	$modelName = $r->resource;                               // resource name is the model name
	$columns = $r->attributes;                               // attributes are the columns of the table
	$tableName = $r->table;

	echo "Processing Resource [" . $modelName . "]...\n";

	// Model - build the model and the migration
	echo "\t--> Creating model and migrations.\n";
	$model = new ModelBuilder($laravelRootPath, $modelName, $tableName, $r->overwrite);
	$model->addAttributes($r->columns)
		->addMigration()
		->migrate($params->migrate);

	// Controller - add controller and router entries
	echo "\t--> Creating controller and router entries.\n";
	$operations = (property_exists($r, "operations") ? $r->operations : ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
	$controller = new ControllerBuilder($laravelRootPath, $modelName, $tableName, $r->overwrite);
	$controller->addOperations($operations)->addRoutes();

	// Views - adding required views
	echo "\t--> Adding Resource Views.\n";
	$view = new ViewBuilder($laravelRootPath, $modelName, $tableName, $r->overwrite);
	$view->createSPAView();

} // foreach

echo "All resources are processed.";

function _getLaravelRootDirectory($params)
{
	if (property_exists("root", $params)) 
	{
		return $params->root;
	}
	else 
	{
		return __DIR__ . "/../";
	}
}


function _loadParamsFromConfigFile ()
{
	$filename = _getConfigFileName();
	$configFile = new Autovel\FileEditor($filename);
	if ($configFile->exists() === false) {
		exit($filename . ' does not exist! Config file must be present in the same directory.');
	};
	return json_decode($configFile->get(), false);
}


function _getConfigFileName ()
{
	return __DIR__ . '/autovel.json';
}

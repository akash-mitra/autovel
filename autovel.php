<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Support/helpers.php';
require './FileEditor.php';

$rootpath = root();

// read the autovel file, if not present error out
$filename = __DIR__ . '/autovel.json';
$configFile = new FileEditor ($filename);
if ($configFile === null )
{
	exit($filename . ' does not exist!');
};
$params = json_decode($configFile->get(), false);

// process the resources
$resources = $params->resources;
echo "Total Resources = " . count($resources) . "\n";
foreach ($resources as $r) {

	$m 	 = $r->resource;
	$columns = $r->columns; // array
	echo "Processing Resource [" . $m . "]...\n";
	
	if (model_exists($m)) {
		if ($r->overwrite === true) {
			echo "\t--> Cleaning up existing resource.\n";
			clean_up($m);
		}
		else {
			echo "\t--> This resource already exists. Skipping.\n";
			continue;
		}
	}


	// create structures (models, controllers etc.)
	$output = artisan("make:model -a " . ucfirst($m));
	print_output($output);
	$migration_file = get_migration_file_name ($m);
	
	// add fillable entries to model
	echo "\t--> Adding fillable entries to model.\n";
	add_fillables ($m, $columns);


	// add router entries
	echo "\t--> Creating router entries.\n";
	add_router_entries ($m);

	// creating controller entries
	echo "\t--> Adding Controller methods.\n";
	$operations = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
	if (property_exists($r, "operations")){
		$operations = $r->operations;
	} 
	add_controller_entries ($m, $operations);

	// adding required views
	echo "\t--> Adding Resource Views.\n";
	$view_filename = "$rootpath/resources/views/" . $m . "/index.blade.php";
	shell_exec ("mkdir $rootpath/resources/views/" . $m); // add a separate directory
	shell_exec ("cp $rootpath/autovel/index.blade.php $view_filename");
	
	// modify the view
	replace_file_lines ($view_filename, '<PLACE_HOLDER_1>', $m);
	replace_file_lines ($view_filename, '<PLACE_HOLDER_2>', '"' . str_plural($m) . '",');

	// prepare the migration files
	echo "\t--> Creating Migration Files.\n";
	$col_defs = prepare_col_definitions ($columns);
	insert_col_definitions ($migration_file, $col_defs);

} // foreach


// run the migrations
if ($params->migrate === true) 
{
	echo "Running New Migrations.\n";
	echo artisan('migrate:fresh');
}

// copy the JavaScript SPA library
shell_exec ("cp $rootpath/autovel/spa.js $rootpath/public/js/");


function root ()
{
	return __DIR__ . "/../";
}

function artisan ($command)
{
	//die(PHP_BINARY . '  ' . root() . 'artisan ' . $command);

	return shell_exec (PHP_BINARY . '  ' . root() . 'artisan ' . $command);
}

function model_exists ($m)
{
	return file_exists(root() . "app/" . $m . ".php");
}

function print_output ($a)
{
	//var_dump($a);
	//print_r(explode(PHP_EOL, $a), false);
	foreach (explode(PHP_EOL, $a) as $value) {
		echo "\t--> " . $value . "\n";
	}
}
function run_command($command)
{	
	$output = "";
	$ret_var = 0;
	try {
		chdir(__DIR__ . "/../");
		exec($command, $output, $ret_var);
		return $output;
	} catch (Exception $e) {
		die("\n Error! " . $output);
	}
}

function get_migration_file_name ($model)
{
	$wild_card_name = __DIR__ . "/../database/migrations/*_create_" . str_plural($model) . "_table.php";
	$migration_files = glob($wild_card_name);
	return $migration_files[0];
}

function prepare_col_definitions ($cols)
{
	$defs = [];
	foreach ($cols as $col) {
		
		// evaluate the column type
		$type_function = "string"; // default column type function
		if (property_exists($col, "datatype")) 
			$type_function = $col->datatype;
		
		// evaluate the size property
		// size is only applicable to certain data types
		if (! in_array($type_function, ["string", "float", "double", "decimal", "char"])) {
			$size = 0;
		}
		else {
			if (property_exists($col, "size")) {
				// if the size is applicable to the data type
				// and size has been provided, use that
				$size = $col->size;
			}
			else {
				// otherwise use the defaults
				$size_map = ["string" => "64", "float" => "8, 2", "decimal" => "5, 2", 
				"double" => "15, 8", "char" => "8"];
				$size = $size_map[$type_function];
			}
		}

		
		
		// evaluate whether the column is optional or mandatory
		$nullable = false; // default
		if (property_exists($col, "optional")) 
			$nullable = $col->optional;

		$def = '            $table->' 
			. $type_function 
			. '("' . $col->name . '"' . ($size!=0? ', ' . $size : '') . ')'
			. ($nullable ? "->nullable();" : ";");

		array_push($defs, $def);
	}

	return $defs;
}

function insert_col_definitions ($migration_file, $defs)
{
	$token = '$table->increments('; 
	$string = '';
	foreach ($defs as $def) {
		$string .= $def . "\n";
	}
	replace_file_lines ($migration_file, $token, $string);
}

function replace_file_lines ($filename, $token, $replacement)
{
	$new_file_contents = '';
	$file = file($filename); 
	foreach ($file as $line) {
		if (stristr($line, $token)) 
			$new_file_contents .= $replacement;
		else $new_file_contents .= $line;
	}

	file_put_contents($filename, $new_file_contents);
}

function add_router_entries ($m)
{
	$routefile = get_router_file_name();
	$lines = "\nRoute::resource('" . str_plural($m) . "', '" . ucfirst($m) . "Controller');"
		. "\nRoute::get('metadata/" . str_plural($m) . "', '" . ucfirst($m) . "Controller@getMetadata');";
	file_put_contents ($routefile, $lines, FILE_APPEND);
}

function clean_up ($m)
{
	$model = get_model_file_name($m);
	//echo "deleting model " . $model;
	$controller = get_controller_file_name($m);
	$factory = __DIR__ . "/../database/factories/" . ucfirst($m) . "Factory.php";
	$view = __DIR__ . "/../resources/views/" . $m;
	$migration = __DIR__ . "/../database/migrations/*_create_" . str_plural($m) . "_table.php";
	$router = get_router_file_name();

	shell_exec('rm -f ' . $model);
	shell_exec('rm -f ' . $controller);
	shell_exec('rm -f ' . $factory);
	shell_exec('rm -rf ' . $view);

	$migration_files = glob($migration);
	foreach($migration_files as $file){
     		//@unlink($image);
     		shell_exec('rm -f ' . $file);
	}

	delete_line_by_search_string ($router, ucfirst($m) . "Controller");	
}

function get_model_file_name ($model)
{
	return  __DIR__ . "/../app/" . ucfirst($model) . ".php";
}

function get_controller_file_name ($model)
{
	return  __DIR__ . "/../app/Http/Controllers/" . ucfirst($model) . "Controller.php";
}

function get_router_file_name ($type = 'web')
{
	return  __DIR__ . "/../routes/" . $type . ".php";
}

function add_fillables ($model, $columns)
{
	// generate fillable entries for all columns
	// where datatype is not increments
	$fillables = array();
	$mf = get_model_file_name($model);
	foreach ($columns as $col) {
		if ($col->datatype != "increments")
			array_push($fillables, $col->name);
	}

	$struct = json_encode($columns);
	$insert_string = "\tprotected " . '$fillable = [' . "'" . implode("', '", $fillables) . "'];\n"
		. "\n\tpublic static function getMetadata()\n\t{\n\t\treturn '" . $struct . "';\n\t}\n}";

	replace_file_lines ($mf, "}", $insert_string);
}

function add_controller_entries ($model, $ops)
{
	$cf = get_controller_file_name($model);

	// 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy'
	foreach ($ops as $op) {

		$line_no = get_line_number($cf, 'public function ' . $op . '(');

		if ($op === "index") 	
			$code = "\t\t" . '$' . str_plural($model) . ' = ' . ucfirst($model) . '::all();'
			. "\n\t\tif (request()->input('contentType') == 'JSON')"
			. "\n\t\t\treturn " . '$' . str_plural($model) . ';'
			. "\n\t\telse return view('" . $model . ".index', compact('" . str_plural($model) . "'));";
		
		if ($op === "create") 
			$code = "\t\treturn view('" . $model . ".create');";

		if ($op === "store") 
			$code = "\t\t" . '$' . $model . ' = new ' . ucfirst($model) . '($request->input());' 
        			. "\n\t\t" . '$' . $model . '->save();'
        			. "\n\t\t" . 'return response()->json(["status" => "success","message" => "New resource created"]);';

		if ($op === "show") 
        		$code = "\t\tif (request()->input('contentType') == 'JSON')"
        		. "\n\t\t\t" . 'return $' . $model . ';'
        		. "\n\t\telse return view('" . $model . ".show', compact('" . $model . "'));";

		if ($op === "edit") 
			$code = "\t\treturn " . '$' . $model . ";";

		if ($op === "update") 
			$code = "\t\t" . '$' . $model . '->fill($request->input())->save();'
			. "\n\t\t" . 'return response()->json(["status" => "success","message" => "Resource updated"]);';

		if ($op === "destroy") 
			$code = "\t\t" . '$' . $model . "->delete();"
			. "\n\t\t" . 'return response()->json(["status" => "success","message" => "Resource deleted"]);';

		insert_after_line ($cf, $line_no + 2, $code);
	}

	// add the metadata controller entry
	$line_no = get_line_number($cf, 'Controller extends Controller');
	$code = "\tpublic function getMetadata() {\n\t\treturn " . ucfirst($model) . "::getMetadata();\n\t}";
	insert_after_line ($cf, $line_no + 1, $code);
}

function get_line_number ($filename, $needle)
{
	$file = fopen($filename, "rb");
	$pos = 1;
	while (($buffer = fgets($file)) !== false) {
    		if (stripos($buffer, $needle) !== false) 
    			return $pos;
    		$pos = $pos + 1;
    	}
    	fclose($file);
    	return null;
}

function delete_line_by_search_string ($filename, $needle)
{
	$pre_rows = file($filename);    
	$post_rows = array_filter($pre_rows, function($row) use ($needle) {
		return stripos($row, $needle) === false;
	});
	file_put_contents($filename, implode('', $post_rows));
}

function insert_after_line ($filename, $line_no, $text)
{
	$f = fopen($filename, "r+b");

	// read lines with fgets() until you have reached the right one
	for ($i = 0; $i < $line_no; $i++) fgets($f);
	$pos = ftell($f);                   // save current position
	$trailer = stream_get_contents($f); // read trailing data
	fseek($f, $pos);                    // go back
	ftruncate($f, $pos);                // truncate the file at current position
	fputs($f, $text . "\n");            // add line
	fwrite($f, $trailer);               // restore trailing data

	fclose($f);
}

// function delete_line ($filename, $line_no)
// {
// 	$f = fopen($filename, "w+b");
// 	for ($i = 0; $i < $line_no; $i++) fgets($f); // read lines before line_no
// 	$pos = ftell($f);                   // save current position
// 	fgets($f);                          // skip the line to be deleted
// 	$trailer = stream_get_contents($f); // read trailing data
// 	fseek($f, $pos);                    // go back
// 	ftruncate($f, $pos);                // truncate the file at current position
// 	fwrite($f, $trailer);               // restore trailing data

// 	fclose($f);
// }



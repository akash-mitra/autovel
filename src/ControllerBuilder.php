<?php namespace Autovel;

use Autovel\LaravelObjectBuilder;
// use Autovel\FileEditor;

/**
 * Controller Builder class contains methods to
 * create controllers and routing files. Typical use
 * as : $ctrl->addOperations(['create', 'index'])->addRoutes();
 * 
 */
class ControllerBuilder extends LaravelObjectBuilder
{
    private $model;
    private $pluralName;
    
    
    /**
     * Creates a new Resource controller using 
     * the init cap version of supplied model name
     */
    public function __construct ($laravelInstallDir, $model, $pluralName, $overwrite = true)
    {
        $this->laravelInstallDir = $laravelInstallDir;
        $this->model = $model;
        $this->pluralName = $pluralName;
        if ($overwrite == true) {
            $this->cleanUp();
        }

        $this->artisan("make:controller -m " . ucfirst($this->model) . " " . ucfirst($this->model) . "Controller");
    }



    /**
     * Add CRUD type operation handlers in the controller class
     */
    public function addOperations (array $ops)
    {
        $controllerFile = new FileEditor($this->getFileName());


	    // 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy'
        foreach ($ops as $op) 
        {

            $line_no = $controllerFile->find('public function ' . $op . '(');

            if ($op === "index")
                $code = "\t\t" . '$' . $this->pluralName . ' = ' . ucfirst($this->model) . '::all();'
                . "\n\t\tif (request()->input('contentType') == 'JSON')"
                . "\n\t\t\treturn " . '$' . $this->pluralName . ';'
                . "\n\t\telse return view('" . $this->model . ".index', compact('" . $this->pluralName . "'));";

            if ($op === "create")
                $code = "\t\treturn view('" . $this->model . ".create');";

            if ($op === "store")
                $code = "\t\t" . '$' . $this->model . ' = new ' . ucfirst($this->model) . '($request->input());'
                . "\n\t\t" . '$' . $this->model . '->save();'
                . "\n\t\t" . 'return response()->json(["status" => "success","message" => "New resource created"]);';

            if ($op === "show")
                $code = "\t\tif (request()->input('contentType') == 'JSON')"
                . "\n\t\t\t" . 'return $' . $this->model . ';'
                . "\n\t\telse return view('" . $this->model . ".show', compact('" . $this->model . "'));";

            if ($op === "edit")
                $code = "\t\treturn " . '$' . $this->model . ";";

            if ($op === "update")
                $code = "\t\t" . '$' . $this->model . '->fill($request->input())->save();'
                . "\n\t\t" . 'return response()->json(["status" => "success","message" => "Resource updated"]);';

            if ($op === "destroy")
                $code = "\t\t" . '$' . $this->model . "->delete();"
                . "\n\t\t" . 'return response()->json(["status" => "success","message" => "Resource deleted"]);';

            $controllerFile->insertAt($line_no + 2, $code);
        }

	    // add the metadata controller entry
        $line_no = $controllerFile->find('Controller extends Controller');
        $code = "\tpublic function getMetadata() {\n\t\treturn " . ucfirst($this->model) . "::getMetadata();\n\t}";
        $controllerFile->insertAt($line_no + 1, $code);

        return $this;
    }



    /**
     * Adds resourceful routing to the router file
     */
    public function addRoutes ()
    {
        $routefile = $this->getRouterFileName();

        $lines     = "\nRoute::resource('" . $this->pluralName . "', '" . ucfirst($this->model) . "Controller');"
            . "\nRoute::get('metadata/" . $this->pluralName . "', '" . ucfirst($this->model) . "Controller@getMetadata');";

        file_put_contents($routefile, $lines, FILE_APPEND);

        return $this;
    }



    /**
     * Returns the Controller File Name
     */
    public function getFileName ()
    {
        return $this->laravelInstallDir . "/app/Http/Controllers/" . $this->model . "Controller.php";
    }


    /**
     * Returns the Router file name
     */
    public function getRouterFileName ($type = 'web')
    {
        return $this->laravelInstallDir . "/routes/" . $type . ".php";
    }



    private function cleanUp()
    {
        // delete controller file
        $controllerFileName = $this->getFileName();
        $this->output[] = shell_exec('rm -f ' . $controllerFileName);

        // delete router entry
        $routerFile = new FileEditor($this->getRouterFileName());
        // resourceful entry
        $line = $routerFile->find(ucfirst($this->model) . "Controller");
        $routerFile->replace($line);
        // metadata entry
        $line = $routerFile->find(ucfirst($this->model) . "Controller@getMetadata");
        $routerFile->replace($line);
        
    }


    // private function artisan($command)
    // {
    //     $this->output[] = shell_exec(PHP_BINARY . '  ' . $this->laravelInstallDir . 'artisan ' . $command);
    // }

}
<?php namespace Autovel;

use Autovel\LaravelObjectBuilder;

/**
 * View Builder class contains methods for creation
 * of various types of views
 */
class ViewBuilder extends LaravelObjectBuilder
{
    private $modelViewDir;
    private $viewFileName;
    private $modelName;
    private $pluralName;


    /**
     * Creates the view sub-directory under laravel resources/views 
     */
    public function __construct($laravelInstallDir, $modelName, $pluralName, $overwrite = true)
    {
        $this->laravelInstallDir = $laravelInstallDir;
        $this->modelName = $modelName;
        $this->pluralName = $pluralName;
    
        $this->modelViewDir = $this->getLaravelViewDirPath() . $modelName . "/";

        if ($overwrite == true) {
            $this->cleanUp();
        }

        $this->output[] = shell_exec("mkdir " . $this->modelViewDir); 

    }



    /**
     * Injects a SPA view for the model. Also adds the 
     * required JavaScript file in the public directory (default).
     */
    public function createSPAView ($operations, $publicJSDir = null)
    {
        if (empty($publicJSDir)) 
        {
            $publicJSDir = $this->laravelInstallDir . '/public/js/';
        }

        $this->output[] = shell_exec("cp " . __DIR__ . "/index.blade.php " . $this->getModelViewDirPath());
        $this->output[] = shell_exec("cp -f " . __DIR__ . "/spa.js " . $publicJSDir);
	
        // make in-place modifications in the file
        $this->viewFileName = $this->getModelViewDirPath() . "/index.blade.php";
        $viewFile = new FileEditor($this->viewFileName);
        
        // page title
        $line1 = $viewFile->find('<PLACE_HOLDER_1>');
        $viewFile->replace($line1, ucfirst($this->modelName));

        // spa resource name
        $line2 = $viewFile->find('<PLACE_HOLDER_2>');
        $viewFile->replace($line2, '"' . $this->pluralName . '",');

        // enable disable operations
        $line3 = $viewFile->find('<PLACE_HOLDER_3>');
        $viewFile->replace($line3, $this->getOperationsString($operations));

        return $this;
    }



    /**
     * Handy function to return Laravel View Diretory path
     */
    public function getLaravelViewDirPath()
    {
        return $this->laravelInstallDir . "/resources/views/"; 
    }



    /**
     * Handy function to return model specific view directory path
     */
    public function getModelViewDirPath ()
    {
        return $this->modelViewDir;
    }



    /**
     * Cleans up old codes for the same model
     */
    private function cleanUp ()
    {
        $this->output[] = shell_exec('rm -rf ' . $this->getModelViewDirPath());
    }



    /**
     * Returns a SPA compatible string for the supported operations
     */
    private function getOperationsString($operations)
    {
        $opsString = "";

        if (! in_array('show', $operations)) {
            $opsString .= "\t\t" .  '"enableShow": false,' . \PHP_EOL;
        }

        if (!in_array('edit', $operations)) {
            $opsString .= "\t\t" . '"enableEdit": false,' . \PHP_EOL;
        }

        if (!in_array('destroy', $operations)) {
            $opsString .= "\t\t" . '"enableDelete": false,' . \PHP_EOL;
        }

        if (!in_array('create', $operations)) {
            $opsString .= "\t\t" . '"enableCreate": false,' . \PHP_EOL;
        }

        return $opsString;
    }

}
<?php namespace Autovel;

use Autovel\FileEditor;

class LaravelObjectBuilder {

    protected $output = [];
    protected $laravelInstallDir;
    
    protected function artisan($command)
    {
        $this->output[] = shell_exec(PHP_BINARY . '  ' . $this->laravelInstallDir . 'artisan ' . $command);
    }
}
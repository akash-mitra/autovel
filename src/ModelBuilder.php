<?php namespace Autovel;

use Autovel\LaravelObjectBuilder;
// use Autovel\FileEditor;

/**
 * Model Builder class contains methods 
 * to create model and migrations for a resource.
 * Use: $model->addAttributes($columns)->addMigration()->migrate();
 */
class ModelBuilder extends LaravelObjectBuilder
{
    private $resourceName;
    private $tableName;
    private $columns           = [];
    private $columnDefinitions = [];
    private $fillables         = [];
    private $migrationFileName = null;


    /**
     * Constructor 
     * Creates the Laravel Model and an empty Migration file
     */
    public function __construct ($laravelInstallDir, $resourceName, $tableName, $overwrite = true)
    {
        $this->laravelInstallDir = $laravelInstallDir;
        $this->resourceName = $resourceName;
        $this->tableName    = $tableName;

        if ($overwrite == true)
        {
            $this->cleanUp();
        }

        // create new model file and add contents
        (new FileEditor($this->getModelFile()))
            ->create($this->getModelFileTemplate());
    }



    /**
     * Adds a new method "getMetadata()" to the model. 
     * getMetadata method returns the metadata structure of
     * the model to the client for using in dynamic JavaScript 
     * based form-bindings. It also adds fillable entries to the model.
     */
    public function addAttributes ($columns)
    {
        $this->columns = $columns;

        foreach ($this->columns as $col) 
        {
            if ($col->datatype != "increments")
                array_push($this->fillables, $col->name);
        }

        $struct = json_encode($this->columns);
        $insert_string = "\tprotected " . '$fillable = [' . "'" . implode("', '", $this->fillables) . "'];\n"
            . "\n\tpublic static function getMetadata()\n\t{\n\t\treturn '" . $struct . "';\n\t}";

        // add the info to the model file
        $modelFile = new FileEditor($this->getModelFile());
        $lineNo = $modelFile->find("TODO");
        $modelFile->insertAt ($lineNo, $insert_string);


        return $this;
    }




    /**
     * Creates the migration file template and fills
     * it up with information taken from column definitions.
     * If column information are not provided via addAttribute()
     * method before, then blank migration files will be generated.
     */
    public function addMigration ()
    {
        $contents = $this->getMigrationFileTemplate();
        (new FileEditor($this->getMigrationFile()))->create($contents);

        $this->prepareColumnDefinitions();
        $this->insertColumnDefinitions();

        return $this;
    }



    /**
     * Performs an actual database migration 
     * by calling the artisan migrate command
     */
    public function migrate ($flag = true)
    {
        // run the migrations
        if ($flag === true) 
        {
            $this->artisan('migrate:fresh');

        }
    }



    /**
     * Returns a list of all fillables
     */
    public function getFillables ()
    {
        return $this->fillables;
    }


    /**
     * Returns an array of objects containing the column definitions
     */
    public function getColumnDefinistions ()
    {
        return $this->columnDefinitions;
    }



    /**
     * Returns the File Name of the model
     */
    public function getModelFile()
    {
        return $this->laravelInstallDir . "/app/" . ucfirst($this->resourceName) . ".php";
    }



    /**
     * Returns the migration file name
     */
    public function getMigrationFile()
    {
        if (empty($this->migrationFileName))
        {
            $this->migrationFileName = $this->getMigrationDir() . date('Y_m_d_His') . '_create_' . $this->tableName . '_table.php';
        }
        
        return $this->migrationFileName;
    }



    /**
     * Finds previously created migration file for the same model
     */
    public function getOldMigrationFile ()
    {
        $wild_card_name = $this->getMigrationDir() . "*_create_" . $this->tableName . "_table.php";
        $migration_files = glob($wild_card_name);
        return empty($migration_files)? null : $migration_files[0];
    }



    /**
     * Laravel Specific Migration directory
     */
    public function getMigrationDir ()
    {
        return $this->laravelInstallDir . "database/migrations/";
    }



    //----------------------------------------------------------------//

    /**
     * Inserts the column definitions in the migration file
     */
    private function insertColumnDefinitions ()
    {
        $string = '';

        foreach ($this->columnDefinitions as $def) {
            $string .= "\t\t\t" . $def . "\n";
        }

        $string .= "\t\t\t" . '$table->timestamps();';  // add timestamp columns

        $migrationFile = new FileEditor($this->getMigrationFile ());
        $line = $migrationFile->find("TODO");
        $migrationFile->insertAt($line, $string);
    }



    /**
     * Prepares column definitions as per Blueprint schema syntax
     */
    private function prepareColumnDefinitions ()
    {   
        foreach ($this->columns as $col) 
        {
            // evaluate the column type
            $type_function = "string"; // default 
            if (property_exists($col, "datatype")) 
            {
                $type_function = $col->datatype;
            }
        
            // evaluate the size property
            // size is only applicable to certain data types
            if (!in_array($type_function, ["string", "float", "double", "decimal", "char"])) 
            {
                $size = 0;
            }
            else 
            {
                if (property_exists($col, "size")) 
                {
                    // if the size is applicable to the data type
                    // and size has been provided, use that
                    $size = $col->size;
                } 
                else 
                {
                    // otherwise use the defaults
                    $size_map = [
                        "string" => "64", "float" => "8, 2", "decimal" => "5, 2",
                        "double" => "15, 8", "char" => "8"
                    ];
                    $size = $size_map[$type_function];
                }
            } // else

            
            // evaluate whether the column is optional or mandatory
            $nullable = false; // default
            if (property_exists($col, "optional")) 
            {
                $nullable = $col->optional;
            }

            $def = '$table->'
                . $type_function
                . '("' . $col->name . '"' . ($size != 0 ? ', ' . $size : '') . ')'
                . ($nullable ? "->nullable();" : ";");

            array_push($this->columnDefinitions, $def);
        } // foreach
    }




    /**
     * Cleans up the model file and the 
     * migration files required for this model.
     */
    private function cleanUp ()
    {
        // delete the model file
        $modelFile = $this->getModelFile();
        $this->output[] = shell_exec('rm -f ' . $modelFile);

        // delete migration file
        $migrationFile = $this->getOldMigrationFile();
        if (! empty ($migrationFile)) 
        {
            $this->output[] = shell_exec('rm -f ' . $migrationFile);
        }
    }



    /**
     * Returns the generic template for model file
     */
    private function getModelFileTemplate()
    {
        return "<?php\n\nnamespace App;\n\nuse Illuminate\Database\Eloquent\Model;\n\nclass "
            . ucfirst($this->resourceName)
            . " extends Model\n{\n\t// TODO \n}";
    }



    /**
     * Returns the generic template of migration files
     */
    private function getMigrationFileTemplate ()
    {
        $contents = "<?php\n\nuse Illuminate\Support\Facades\Schema;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Database\Migrations\Migration;\n\nclass Create" 
        . ucfirst($this->tableName)
        . "Table extends Migration\n{\n\tpublic function up()\n\t{\n\t\tSchema::create('" 
        . $this->tableName 
        . "', function (Blueprint " . '$table' . ") {\n\t\t\t//TODO\n\t\t});\n\t}\n\tpublic function down()\n\t{\n\t\tSchema::dropIfExists('" 
        . $this->tableName 
        . "');\n\t}\n}";

        return $contents;

    }

}
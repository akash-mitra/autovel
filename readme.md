# Automatic Code Generator for Laravel

Stop creating the same Models, Create/Update/Delete Views, Controllers and migrations manually. Just tell *Autovel* about your deata structures and let it automatically create all of those for you.

In a few seconds, you get automatically created model, view, controller for each resource with fully working New, Edit, Delete functionalities in the frontend.

# Setup

1. Install Laravel and create scaffolding (`php artisan make:auth`)
2. Within the Laravel Install Directory, Download *Autovel* from https://github.com/akash-mitra/autovel.git
3. Modify the `autovel.json` as per your project datastructure (see below for options)
4. Run autovel.php
5. Fullstack automation complete with both backend and frontend work.

## Example `autovel.json` Config File

Autovel gets all the information from `autovel.json` config file. An example config file is provided with the code base. You can open the same config file and change it according to your project's need.

````

{
  "resources": [
    {
      "resource": "department",
      "table": "departments",
      "attributes": [
        {"name": "id", "datatype": "increments"},
        {"name": "name", "datatype": "string", "size": 30},
        {"name": "desc", "datatype": "string", "size": 255, "optional": true},
        {"name": "col1", "datatype": "double", "optional": true},
        {"name": "col2", "datatype": "string", "optional": false}
      ],
      "opeartions": ['index', 'create'],
      "overwrite": true
    },
    {
      "resource": "employee",
      "table": "employees",
      "attributes": [
        {"name": "id", "datatype": "increments"},
        {"name": "name", "datatype": "string", "size": 30},
        {"name": "dob", "datatype": "date", "optional": true},
        {"name": "salary", "datatype": "double"}
      ],
      "overwrite": true
    }
  ],
  "migrate": false,
  "root": "/Users/mitra/Laravel/myBlog"
}

````

### Options


Property     | Options      | Descriptions
---          | ---          | ---
|`resources` | -            | An array of other `resource` objects.
|`resource`  | -            | Name of the resource - this name in plural form will be used to name the database table.
|`table`     | -            | Name of the database table to be created for this resource. This is generally the plural form of resource name.
|`overwrite` | -            | Whether or not to overwite existing codes (model, migrations, controllers etc.) pertaining to this resource while re-executing autovel.
|`columns`   | -            | Columns or properties needed for this resource.
|            | `name`       | Name of the column / property
|            | `datatype`   | Datatype of the column (should be similar to Laravel supported datatypes)
|            | [`size`]     | This is an OPTIONAL property. Size of the datatype. If not provided, uses sensible defaults.
|            | [`optional`] | This is an OPTIONAL property. Depicts whether the value in the column is mandatory or optional.
|[`operations`]| -          | OPTIONAL. An array of supported operations, e.g. ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']
|`migrate`   | -            | Whether or not to actually run the migrations.
|[`root`]    | -            | OPTIONAL. Laravel Installation directory. If it is left blank, the parent directory of the current directory where `autovel.php` is located, is considered as the Laravel installation directory.


# Features

1. Automatically Creates Model, Controller, Controller Methods, Views to support CRUD operations, migration files etc.
2. Automatically design all the Create, Update form controls
3. Optionally executes migrations 

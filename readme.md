# Automatic Code Generator for Laravel

Stop creating those same Models, Create Update Delete Views, Controllers, migrations manually. Just tell autovel the table structures and let it create all of them in a second for you. Then you can of course customize if you need.

# Setup

1. Create a new directory under the laravel working directory.
2. Download all the files in the new directory
3. Modify the `autovel.json` as per your need
4. Run autovel.php

## Example `autovel.json` Config File

Autovel gets all the information from `autovel.json` config file. An example config file is provided with the code base. You can open the same config file and change it according to your project's need.

````

{
  "resources": [
    {
      "resource": "department",
      "overwrite": true,
      "columns": [
        {"name": "id", "datatype": "increments"},
        {"name": "name", "datatype": "string", "size": 30},
        {"name": "desc", "datatype": "string", "size": 255, "optional": true},
        {"name": "col1", "datatype": "double", "optional": true},
        {"name": "col2", "datatype": "string", "optional": false}
      ]
    },
    {
      "resource": "employee",
      "overwrite": true,
      "columns": [
        {"name": "id", "datatype": "increments"},
        {"name": "name", "datatype": "string", "size": 30},
        {"name": "dob", "datatype": "date", "optional": true},
        {"name": "salary", "datatype": "double"}
      ]
    }
  ],
  "migrate": false
}

````

### Options


Property     | Options      | Descriptions
---          | ---          | ---
|`resources` | -            | An array of other `resource` objects.
|`resource`  | -            | Name of the resource - this name in plural form will be used to name the database table.
|`overwrite` | -            | Whether or not to overwite existing codes (model, migrations, controllers etc.) pertaining to this resource while re-executing autovel.
|`columns`   | -            | Columns or properties needed for this resource.
|            | `name`       | Name of the column / property
|            | `datatype`   | Datatype of the column (should be similar to Laravel supported datatypes)
|            | [`size`]     | This is an OPTIONAL property. Size of the datatype. If not provided, uses sensible defaults.
|            | [`optional`] | This is an OPTIONAL property. Depicts whether the value in the column is mandatory or optional.
|`migrate`   | -            | Whether or not to actually run the migrations.


# Features

1. Automatically Creates Model, Controller, Controller Methods, Views to support CRUD operations, migration files etc.
2. Automatically design all the Create, Update form controls
3. Optionally executes migrations 

# Example 
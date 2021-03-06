<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use function PHPUnit\Framework\fileExists;

class CrudGeneratorCommand extends Command
{

    protected $signature = 'crud:generator
    {name : Class (singular) for example User}';

    protected $description = 'Create CRUD functionality';

    //Form files types collection
    protected $typeLookup = [
        'string' => 'text',
        'char' => 'text',
        'varchar' => 'text',
        'text' => 'textarea',
        'mediumtext' => 'textarea',
        'longtext' => 'textarea',
        'json' => 'textarea',
        'jsonb' => 'textarea',
        'binary' => 'textarea',
        'password' => 'password',
        'email' => 'email',
        'number' => 'number',
        'integer' => 'number',
        'bigint' => 'number',
        'mediumint' => 'number',
        'tinyint' => 'number',
        'smallint' => 'number',
        'decimal' => 'number',
        'double' => 'number',
        'float' => 'number',
    ];

    public function handle()
    {
        $name = $this->argument('name');

        //Default variables ( if user chooses not to input any parameters )
        $fields='';
        $validation='';
        $pk='';
        $pagination=5;
        $fk='';
        $relationships='';

        //Handling user input
        if($this->confirm('Do you want to configure the generator parameters?', true)) {

            $this->info('Press ENTER to insert defaults');

            $fields = $this->ask('Please insert field names and parameters (e.g. integer(\'amount\');string(\'name\');');

            $validation = $this->ask('Validation rules for the fields (e.g. \'title\' => \'required\', \'name\' => \'min:10\')');

            $pk = $this->ask('Primary key (Default - none) (e.g. id)');

            $pagination = $this->ask('Insert pagination number (Default - 5)');
            if($pagination == null){
                $pagination = 5;
            }

            $fk = $this->ask('Foreign key (Default - none) (e.g. foreign(\'state\')->references(\'id\')->on(\'states\')->onDelete(\'cascade\')');

            $relationships = $this->ask('Relationships to other models (Keys are optional) - structure Name;Type;\'Key1\',\'Key2\'  (e.g. Post;belongsTo;\'foreign_key\', \'owner_key\')');

        }
        $startTime= microtime(true);

        //Creating files
        $this->controller($name, $pagination);
        $this->model($name, $pk, $relationships, $fields);
        $this->request($name, $validation);
        $this->migration($name, $fields, $fk);

        //Creating views
        $this->viewIndex($name, $fields);
        $this->viewShow($name, $fields);
        $this->viewEdit($name, $fields);
        $this->viewCreate($name, $fields);

        //Appending new API routes to file
        File::append(base_path('routes/api.php'), 'Route::resource(\'' . Str::plural(strtolower($name)) . "', App\Http\Controllers\\{$name}Controller::class);");

        //Generation complete message

        $this->info('Files generated successfully!');

        //Catching execution time
        $endTime= microtime(true);
        $execTime=($endTime-$startTime);
        $this->info('It took '.$execTime.' seconds to generate the files!');
    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }

    protected function getFile($migrationFile){
        return file_get_contents("database/migrations/$migrationFile");
    }

    protected function getNames($fields)
    {
        $seperator ='\'';

        $newFieldNames = explode($seperator,$fields);
        $records = count($newFieldNames);

        for ($i=0; $i<=$records; $i=$i+2) {
            unset($newFieldNames[$i]);
        }

        return $newFieldNames;
    }

    protected function getAttributes($fields)
    {
        $seperator ='(';
        $result[]='';

        $newFieldAttributes = explode($seperator,$fields);
        $records = count($newFieldAttributes);

        $result[0] = $newFieldAttributes[0];

        for ($i=1;$i<=$records-2;$i++){
            $temp = explode(';', $newFieldAttributes[$i]);
            $result[$i]=$temp[1];
        }

        return $result;
    }

    protected function getFolderName($name)
    {
        return strtolower($name)."Views";
    }

    protected function model($name, $pk, $relationships, $fields)
    {
        $fillableUp='';
        $tabIndent = '    ';

        //Primary Key
        if(!empty($pk)){
            $pk = "protected \$primaryKey ='".$pk."';";
        }

        //Relationships
        if(!empty($relationships) ) {
            $brokenRelationships = explode(';', $relationships);
            if(count($brokenRelationships)==2) {
                $relationshipsName = $brokenRelationships[0];
                $relationshipsType = $brokenRelationships[1];

                $relationshipsUp = "public function " . strtolower($relationshipsName) . "()\n"
                    . $tabIndent . "{\n"
                    . $tabIndent . $tabIndent . "return \$this->" . $relationshipsType . "(" . $relationshipsName . "::class);\n"
                    . $tabIndent . "}";
            }
            if(count($brokenRelationships)==3) {
                $relationshipsName = $brokenRelationships[0];
                $relationshipsType = $brokenRelationships[1];
                $relationshipsKey = $brokenRelationships[2];

                $relationshipsUp = "public function " . strtolower($relationshipsName) . "()\n"
                    . $tabIndent . "{\n"
                    . $tabIndent . $tabIndent . "return \$this->" . $relationshipsType . "(" . $relationshipsName . "::class,". $relationshipsKey .");\n"
                    . $tabIndent . "}";
            }
            else $relationshipsUp ='';
        }
        else $relationshipsUp ='';

        //Adding Fillable from $fields
        $fieldNames = $this->getNames($fields);

        foreach($fieldNames as $field){
            $fillableUp = $fillableUp.$tabIndent.$tabIndent."'".$field."',\n";
        }

        $template= str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{pk}}',
                '{{relationships}}',
                '{{fillableUp}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                $pk,
                $relationshipsUp,
                $fillableUp
            ],
            $this->getStub('Model')
        );

        file_put_contents(app_path("/{$name}.php"), $template);
    }

    protected function request($name, $validation)
    {

        $template= str_replace(
            [
                '{{modelName}}',
                '{{validation}}'
            ],
            [
                $name,
                $validation
            ],
            $this->getStub('Request')
        );

        if(!file_exists($path = app_path('/Http/Requests')))
            mkdir($path, 0777, true);

        file_put_contents(app_path("/Http/Requests/{$name}Request.php"), $template);
    }

    protected function controller($name, $pagination)
    {
        $template= str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{pagination}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name),
                $pagination
            ],
            $this->getStub('Controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $template);

    }

    protected function migration($name, $fields, $fk)
    {
        $fieldsUp='';
        $tabIndent = '            ';

        //Creating migration
        Artisan::call('make:migration create_' . strtolower(Str::plural($name)) . '_table --create=' . strtolower(Str::plural($name)));

        //Handling fields
        $differentFields = explode(';',$fields);

        if(!empty($fields)) {
            foreach ($differentFields as $field) {
                $fieldsUp = $fieldsUp . "\$table->$field;\n" . $tabIndent;
            }
        }

        //Handling foreign key
        if(!empty($fk)) {
            $fieldsUp .= "\$table->$fk;";
        }

        //Finding file name to edit and add the fields
        $files=scandir('database/migrations', SCANDIR_SORT_DESCENDING);
        $migrationFile = $files[0];

        $template= str_replace(
            [
                '//fields'
            ],
            [
                $fieldsUp
            ],
            $this->getFile($migrationFile)
        );

        file_put_contents("database/migrations/{$migrationFile}", $template);

    }

    protected function viewIndex($name, $fields)
    {
        $tabIndent='    ';
        $columnsUp='';
        $lowerName =  strtolower($name);
        $recordsUp='';

        //Table column names
        $getFieldNames= $this->getNames($fields);

        foreach ($getFieldNames as $fields) {
            $columnsUp = $columnsUp . $tabIndent . $tabIndent . $tabIndent . "<th>".$fields."<th>\n";
        }

        //Records
        foreach ($getFieldNames as $fields) {
            $recordsUp = $recordsUp . $tabIndent . $tabIndent . $tabIndent . "<td>{{\$".$lowerName."->".$fields."}}<td>\n";
        }

        //Set new folder name for views
        $folderName = $this->getFolderName($name);

        $template= str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{columnsUp}}',
                '{{recordsUp}}',
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name),
                $columnsUp,
                $recordsUp,
            ],
            $this->getStub('viewIndex')
        );

        if(!file_exists("resources/views/$folderName"))
            mkdir("resources/views/$folderName", 0777, true);

        file_put_contents("resources/views/$folderName/index.blade.php", $template);
    }

    protected function viewShow($name, $fields)
    {
        $recordUp='';
        $columnsUp='';
        $tabIndent='    ';

        $lowerName =  strtolower($name);
        $getFieldNames= $this->getNames($fields);
        $folderName = $this->getFolderName($name);

        foreach ($getFieldNames as $fields) {
            $columnsUp = $columnsUp . $tabIndent . $tabIndent . $tabIndent . "<th>".$fields."<th>\n";
        }

        foreach ($getFieldNames as $fields) {
            $recordUp = $recordUp . $tabIndent . "<td>{{\$".$lowerName."->".$fields."}}<td>\n";
        }

        $template= str_replace(
            [
                '{{modelName}}',
                '{{recordUp}}',
                '{{columnsUp}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}'
            ],
            [
                $name,
                $recordUp,
                $columnsUp,
                strtolower(Str::plural($name)),
                strtolower($name)
            ],
            $this->getStub('viewShow')
        );

        file_put_contents("resources/views/$folderName/show.blade.php", $template);

    }

    protected function viewEdit($name, $fields)
    {
        $updateUp='';
        $tabIndent='    ';
        $lowerName=strtolower($name);

        $arr=$this->getNames($fields);
        $getAttributeNames = $this->getAttributes($fields);
        $getFieldNames= array_values($arr);
        $folderName = $this->getFolderName($name);

        $i=0;

        foreach($getFieldNames as $field){

            $updateUp= $updateUp.$tabIndent.$tabIndent."<div class=\"form-group\">\n".
            $tabIndent.$tabIndent.$tabIndent."<label for='".$field."'>".$field."</label>\n".
            $tabIndent.$tabIndent.$tabIndent."<input type=\"".$this->typeLookup[$getAttributeNames[$i]]."\" name=\"".$field."\" value=\"{{\$".$lowerName."->".$field."}}\" class=\"form-control\" id=\"".$field."\">\n".
            $tabIndent.$tabIndent."</div>\n";
            $i++;
        }

        $template= str_replace(
            [
                '{{modelName}}',
                '{{modelNameSingularLowerCase}}',
                '{{modelNamePluralLowerCase}}',
                '{{updateUp}}'
            ],
            [
                $name,
                strtolower($name),
                strtolower(Str::plural($name)),
                $updateUp
            ],
            $this->getStub('viewEdit')
        );

        file_put_contents("resources/views/$folderName/edit.blade.php", $template);
    }

    protected function viewCreate($name, $fields)
    {
        $createUp='';
        $tabIndent='    ';
        $arr=$this->getNames($fields);
        $getAttributeNames = $this->getAttributes($fields);
        $getFieldNames= array_values($arr);
        $folderName = $this->getFolderName($name);

        $i=0;

        foreach($getFieldNames as $field){

            $createUp= $createUp.$tabIndent.$tabIndent."<div class=\"form-group\">\n".
                $tabIndent.$tabIndent.$tabIndent."<label for=\"".$field."\">".$field."</label>\n".
                $tabIndent.$tabIndent.$tabIndent."<input type=\"".$this->typeLookup[$getAttributeNames[$i]]."\" name=\"".$field."\" class=\"form-control\" id=\"".$field."\">\n".
                $tabIndent.$tabIndent."</div>\n";
            $i++;
        }

        $template= str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{createUp}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                $createUp
            ],
            $this->getStub('viewCreate')
        );

        file_put_contents("resources/views/$folderName/create.blade.php", $template);
    }
}

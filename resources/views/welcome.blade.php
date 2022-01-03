@extends('layouts.app')
@section('content')
        <div class="container bg-light">
            <h1 class="pt-3 pb-2">Laravel - CRUD generator</h1>
            <h6>An empty Laravel project that provides CRUD, Controller, Model, Migration and View generation for easy development of your applications.</h6>
            <hr>
            <h3 class="text-center">Getting started</h3>
            <hr>
            <p>Simply use this command in your terminal to access the generator:</p>
            <div class="bg-dark bg-opacity-25">
                <small class="px-3">php artisan crud:generator {Name}</small>
            </div>
            <p>Don't forget to run the migrations to create new table inside your database:</p>
            <div class="bg-dark bg-opacity-25">
                <small class="px-3">php artisan migrate</small>
            </div>
            <p class="pt-2">Next you will have an option to configure some parameters. Write 'yes' or 'no' accordingly.</p>
            <p>Moving forward just follow the messages in your terminal, you will have an option to fill : </p>
            <ul>
                <li>Custom field names.</li>
                <li>Validation rules.</li>
                <li>Primary key.</li>
                <li>Pagination number.</li>
                <li>Foreign key.</li>
                <li>Set relationships to other Models in the application.</li>
            </ul>
            <b>NOTE: please fill the fields as it is shown in the examples. Otherwise the generator will fail.</b>
            <hr>
            <h3 class="text-center">To reach your generated views</h3>
            <hr>
            <p>To reach your generated views from your web browser simply follow this route:</p>
            <div class="bg-dark bg-opacity-25">
                <small class="px-3">yourCurrentRoute/api/{name}         name being what you used when initiating the generator but plural and lowercase.</small>
            </div>
            <hr>
            <h3 class="text-center">Supported fields</h3>
            <hr>
            <p>Feel free to use any fields from the list:</p>
            <ul>
                <li>string</li>
                <li>char</li>
                <li>varchar</li>
                <li>text</li>
                <li>mediumtext</li>
                <li>longtext</li>
                <li>json</li>
                <li>jsonb</li>
                <li>binary</li>
                <li>password</li>
                <li>email</li>
                <li>number</li>
                <li>integer</li>
                <li>bigint</li>
                <li>mediumint</li>
                <li>tinyint</li>
                <li>smallint</li>
                <li>decimal</li>
                <li>double</li>
                <li>float</li>
            </ul>
            <hr>
            <h3 class="text-center">Configuration options and templates</h3>
            <hr>
            <table class="table table-striped">
                <tr>
                    <th>Parameter</th>
                    <th>Description</th>
                    <th>Example</th>
                </tr>
                <tr>
                    <td><p>Generator command</p></td>
                    <td><p>Generates files for CRUD functionality. Structure - Name must be singular and uppercase</p></td>
                    <td><p>php artisan crud:generator Dog</p></td>
                </tr>
                <tr>
                    <td><p>Fields</p></td>
                    <td><p>Set the fields for Migration and View files. Structure - attributeType('fieldName');attributeType('fieldName')</p></td>
                    <td><p>string('name');integer('age');string('breed')</p></td>
                </tr>
                <tr>
                    <td><p>Validation</p></td>
                    <td><p>Set the validation rules. Structure - 'fieldName' => 'validationRules', 'fieldName' => 'validationRules'</p></td>
                    <td><p>'name' => 'required', 'age' => 'min:1'</p></td>
                </tr>
                <tr>
                    <td><p>Primary key</p></td>
                    <td><p>Primary key to be set inside the Model. Structure - primaryKeyName</p></td>
                    <td><p>id</p></td>
                </tr>
                <tr>
                    <td><p>Pagination</p></td>
                    <td><p>Number of records on one page inside Views. Structure - paginationNumber</p></td>
                    <td><p>6</p></td>
                </tr>
                <tr>
                    <td><p>Foreign key</p></td>
                    <td><p>Foreign key to be put inside Migration to link two tables together. Structure - foreign('NAME')->references('NAME') ( option to put more parameters following the same structure.) </p></td>
                    <td><p>foreign('pet_id')->references('id')->on('pets')->onDelete('cascade')'</p></td>
                </tr>
                <tr>
                    <td><p>Relationships</p></td>
                    <td><p>Relationships to connect created Model with other Models inside application. Structure - Name;Type;'Key1', 'Key2' ( Keys are optional.)</p></td>
                    <td><p>Owner;hasMany;'pet_id'</p></td>
                </tr>
            </table>
            <hr>
            <div class="ml-4 text-center text-sm text-gray-500">
                Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
            </div>
        </div>
@endsection

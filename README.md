# Installation

Execute composer command.

    composer require dsa-io/csv-validator:1.*

Register the service provider in app.php

    'providers' => [
        ...Others...,
        Dsaio\CsvValidator\CsvValidatorServiceProvider::class,
    ]

Also alias

    'aliases' => [
        ...Others...,  
        'CsvValidator' => Dsaio\CsvValidator\Facades\CsvValidator::class,
    ]
    
# Basic usage

    $csv_path = 'test.csv';
    $rules = [
        0 => 'required',
        1 => 'required|integer',
        2 => 'required|min:4'
    ];
    $csv_validator = CsvValidator::make($csv_path, $rules);
    
    if($csv_validator->fails()) {
        $errors = $csv_validator->getErrors();
    }    

# Rules

You can set keys instead of indexes like so.

    $rules = [
        'First Name' => 'required',
        'Last Name' => 'required',
        'Email' => 'required|email'
    ];

In this case, the heading row of the CSV need to have `First Name`, `Last Name` and `Email`.  
And This keys will be used as attribute names for error message.

* [See](https://laravel.com/docs/5.2/validation#available-validation-rules) the details of the rules. 

# Trimming

You can set the 3rd argument a boolean value. If it is set to true, it will trim the cells of the csv. (Default: true)

    CsvValidator::make($csv_path, $rules, true, 'SJIS-win');

# Encoding

You can set a specific encoding as the 4th argument. (Default: UTF-8)

    CsvValidator::make($csv_path, $rules, 'SJIS-win');

# Error messages

You can get error messages after calling fails().

    $errors = $csv_validator->getErrors();
    
    foreach ($errors as $row_index => $error) {
    
        foreach ($error as $col_index => $messages) {
    
            echo 'Row '. $row_index .', Col '.$col_index .': '. implode(',', $messages) .'<br>';
    
        }
    
    }

# Exception

If the validator is expecting a heading row (i.e it receives an associative rules array) and the CSV is empty, an exception will be thrown.

# License

This package is licensed under the MIT License.

Copyright 2017 DSA

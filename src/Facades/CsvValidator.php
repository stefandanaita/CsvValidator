<?php namespace Dsaio\CsvValidator\Facades;

/**
 * Created by Stefan Danaita.
 * stefan@tribepad.com
 * stefan @ PhpStorm
 * 23/01/2017
 */

use Illuminate\Support\Facades\Facade;

class CsvValidator extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'csv-validator';
    }

}
<?php namespace Dsaio\CsvValidator;
/**
 * Created by Stefan Danaita.
 * stefan@tribepad.com
 * stefan @ PhpStorm
 * 23/01/2017
 */

class CsvValidator {

	private $csv,
        $rules = [],
        $headingKeys = [],
        $errors = [],
        $trim = true,
        $encoding = 'UTF-8';

	public function make($csv, $rules, $trim = true, $encoding = 'UTF-8')
    {
        // Validate the CSV file
        $v = \Validator::make(['file' => $csv], [
            'file' => 'required|mimes:csv,txt'
        ]);

        if($v->fails()) {
            throw new \RuntimeException('This file is not a valid CSV file.');
        }

        $this->trim = $trim;
        $this->encoding = $encoding;

        // Set the $rules and $headingKeys
        $this->setRules($rules);

        // Instantiate the CSV reader and set flags
        $this->csv = new \SplFileObject($csv);
        $this->csv->setFlags(
            \SplFileObject::READ_CSV      |
            \SplFileObject::READ_AHEAD    |
            \SplFileObject::SKIP_EMPTY    |
            \SplFileObject::DROP_NEW_LINE
        );

		return $this;
	}

	public function fails()
    {
	    $lineIndex = 0;
		$errors = [];
        $csv = $this->csv;
        $headingRow = [];

        // Pull the header row from the csv if it exists
        if($this->hasHeadingRow()) {
            $headingRow = $csv->fgetcsv();

            // Trim the elements and convert them to the right encoding
            $headingRow = array_map('encodeCell', $headingRow);

            if(empty($headingRow)) {
                throw new \RuntimeException('The CSV does not contain a heading row');
            }

            $errors[0] = $this->validateHeadingRow($headingRow);
        }

        while ($row = $csv->fgetcsv()) {
            $lineIndex++;

            // Trim the elements and convert them to the right encoding
            $row = array_map('encodeCell', $row);

            // Build assoc array between header and row elements
            $combined = !empty($headingRow) ? $this->combineRowHeader($row, $headingRow) : $row;

            // Validate the combined line
            $v = \Validator::make($combined, $this->rules);

            if($v->fails()) {
                $errors[$lineIndex] = $v->message()->toArray();
            }
        }

		$this->setErrors($errors);

		return (!empty($this->errors));
	}

    /**
     * @param $row
     * @return array
     */
    private function validateHeadingRow($row)
    {
	    $errors = [];

	    foreach($row as $index => $heading) {
	        if(!in_array($heading, $this->headingKeys)) {
	            $errors[$index] = $heading;
            }
        }

        return $errors;
    }

    /**
     * @param $errors
     * @return void
     */
    private function setErrors($errors)
    {
	    $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
		return $this->errors;
	}

    /**
     * @param $rules
     * @return void
     */
    private function setRules($rules)
    {
		$this->rules = $rules;
		$this->headingKeys = array_keys($rules);
	}

    /**
     * @return bool
     */
    private function hasHeadingRow()
    {
        foreach($this->headingKeys as $key) {
            if(!is_int($key)) {
                return true;
            }
        }

        return false;
	}

    /**
     * @param $row
     * @param $headingRow
     * @return array
     */
    private function combineRowHeader($row, $headingRow)
    {
        $combined = [];

        foreach($headingRow as $key => $value)
        {
            $combined[$value] = $row[$key];
        }

        return $combined;
    }

    /**
     * @param $content
     * @return string
     */
    private function encodeCell($content)
    {
        if($this->trim) {
            $content = trim($content);
        }

        return iconv(mb_detect_encoding($content, mb_detect_order(), true), $this->encoding, $content);
    }
}
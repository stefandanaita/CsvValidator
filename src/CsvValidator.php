<?php namespace Dsaio\CsvValidator;
/**
 * Created by Stefan Danaita.
 * stefan@tribepad.com
 * stefan @ PhpStorm
 * 23/01/2017
 */

class CsvValidator
{
    protected $csv,
        $rules = [],
        $headingKeys = [],
        $errors = [],
        $requiredHeadings = [],
        $trim = true,
        $encoding = 'UTF-8';

    /**
     * @param \SplFileObject $csv
     * @param $rules
     * @param array $requiredHeadings
     * @param bool $trim
     * @param string $encoding
     * @return $this
     */
    public function make(\SplFileObject $csv, $rules, $requiredHeadings = [], $trim = true, $encoding = 'UTF-8')
    {
        // Reset the variables
        $this->rules = $this->errors = $this->headingKeys = $this->requiredHeadings = [];

        $this->setRequiredHeadings($requiredHeadings);
        $this->setTrim($trim);
        $this->setEncoding($encoding);
        $this->setCSV($csv);

        // Set the $rules and $headingKeys
        $this->setRules($rules);

        return $this;
    }

    /**
     * This is where the validation is happening
     * The CSV file is read row by row to save memory
     * @return bool
     */
    public function fails()
    {
        $csv = $this->getCSV();

        $lineIndex = 0;
        $headingRow = [];

        // Pull the header row from the csv if it exists
        if ($this->hasHeadingRow()) {
            $headingRow = $csv->fgetcsv();

            // Trim the elements and convert them to the right encoding
            $headingRow = array_map([$this, 'encodeCell'], $headingRow);

            if (empty($headingRow)) {
                throw new \RuntimeException('The CSV does not contain a heading row');
            }

            // If the heading row is invalid, don't go any further
            if(!$this->validateHeadingRow($headingRow)) {
                return true;
            }
        }

        while ($row = $csv->fgetcsv()) {
            $lineIndex++;

            // Trim the elements and convert them to the right encoding
            $row = array_map([$this, 'encodeCell'], $row);

            // Build assoc array between header and row elements
            $combined = $this->hasHeadingRow() ? $this->combineRowHeader($row, $headingRow) : $row;

            // Validate the combined line
            $v = \Validator::make($combined, $this->getRules());

            if ($v->fails()) {
                $this->setErrors($lineIndex, $v->messages()->toArray());
            }
        }

        return (!empty($this->getErrors()));
    }

    /**
     * Returns an associative array of errors.
     * $errors[0] contains the errors from the Heading row, if exists
     * $errors[n] contains the errors from the n-th csv row
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $row
     * @return bool
     */
    protected function validateHeadingRow($row)
    {
        $errors = [];

        // Check if the existing headings are valid
        foreach ($row as $index => $heading) {
            if (!in_array($heading, $this->getHeadingKeys())) {
                $errors[$heading][0] = 'Heading ' . $heading . ' is not a valid heading for this CSV.';
            }
        }

        // Check if all the required headings are present
        if(!empty($this->getRequiredHeadings()) && $this->getRequiredHeadings() !== array_intersect($this->getRequiredHeadings(), $row)) {
            foreach($this->getRequiredHeadings() as $index => $heading) {
                if(!in_array($heading, $row)) {
                    $errors[$heading][0] = 'Heading ' . $heading . ' is missing from the CSV.';
                }
            }
        }

        if(!empty($errors)) {
            $this->setErrors(0, $errors);
        }

        return empty($errors);
    }

    /**
     * @param $csv
     * @return void
     */
    protected function setCSV($csv)
    {
        $csv->setFlags(
            \SplFileObject::READ_CSV |
            \SplFileObject::READ_AHEAD |
            \SplFileObject::SKIP_EMPTY |
            \SplFileObject::DROP_NEW_LINE
        );

        $this->csv = $csv;
    }

    /**
     * @return \SplFileObject
     */
    private function getCSV()
    {
        return $this->csv;
    }

    /**
     * @param $keys
     * @return void
     */
    private function setHeadingKeys($keys)
    {
        $this->headingKeys = $keys;
    }

    /**
     * @return array
     */
    private function getHeadingKeys()
    {
        return $this->headingKeys;
    }

    /**
     * @param $key
     * @param $errors
     */
    protected function setErrors($key, $errors)
    {
        $this->errors[$key] = $errors;
    }

    /**
     * This sets the rules and the expected heading keys
     * @param $rules
     * @return void
     */
    private function setRules($rules)
    {
        $this->rules = $rules;
        $this->setHeadingKeys(array_keys($rules));
    }

    /**
     * @return array
     */
    private function getRules()
    {
        return $this->rules;
    }

    /**
     * @param $trim
     * @return void
     */
    private function setTrim($trim)
    {
        $this->trim = $trim;
    }

    /**
     * @return bool
     */
    private function getTrim()
    {
        return $this->trim;
    }

    /**
     * @param $encoding
     * @return void
     */
    private function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * @return string
     */
    private function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @return bool
     */
    private function hasHeadingRow()
    {
        $headingKeys = $this->headingKeys;

        return $headingKeys !== array_filter($headingKeys, 'is_int');
    }

    /**
     * @param $row
     * @param $headingRow
     * @return array
     */
    protected function combineRowHeader($row, $headingRow)
    {
        $combined = [];

        foreach ($headingRow as $key => $value) {
            $combined[$value] = $row[$key];
        }

        return $combined;
    }

    /**
     * @param $headings
     * @return void
     */
    private function setRequiredHeadings($headings)
    {
        $this->requiredHeadings = $headings;
    }

    /**
     * @return array
     */
    public function getRequiredHeadings()
    {
        return $this->requiredHeadings;
    }

    /**
     * @param $content
     * @return string
     */
    public function encodeCell($content)
    {
        if ($this->getTrim()) {
            $content = trim($content);
        }

        return iconv(mb_detect_encoding($content, mb_detect_order(), true), $this->getEncoding(), $content);
    }
}
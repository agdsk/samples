<?php

namespace AppBundle\Library\Airtable;

class AirtableDataObject
{
    protected $fields = [];

    public function getFields()
    {
        return array_keys($this->fields);
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }
}
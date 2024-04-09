<?php

namespace AppBundle\Library\Airtable;

class AirtableRecord extends AirtableDataObject
{
    public function getField($key)
    {
        if (array_key_exists($key, $this->fields)) {
            return $this->fields[$key];
        }

        return null;
    }

    public function getFieldFirst($key)
    {
        if (array_key_exists($key, $this->fields)) {
            if (empty($this->fields[$key])) {
                return null;
            }

            return current($this->fields[$key]);
        }

        return null;
    }

    public function setData($record)
    {
        foreach (array_keys($this->fields) as $key) {
            if (array_key_exists($key, $record['fields'])) {
                $this->fields[$key] = $record['fields'][$key];
            }
        }
    }
}
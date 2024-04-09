<?php

namespace AppBundle\Library\Airtable;

class AirtableRecordCollection extends AirtableDataObject
{
    private $records = [];

    public function get($id)
    {
        if (array_key_exists($id, $this->records)) {
            return $this->records[$id];
        }

        return null;
    }

    public function addRecord($record)
    {
        $AirtableRecord = new AirtableRecord();

        $AirtableRecord->setFields($this->fields);
        $AirtableRecord->setData($record);

        $this->records[$record['id']] = $AirtableRecord;
    }

    public function all()
    {
        return $this->records;
    }
}
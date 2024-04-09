<?php

namespace AppBundle\Library\Airtable;

class Airtable
{
    protected $url = 'https://api.airtable.com/v0';
    protected $appId = '';
    protected $apiKey = '';
    protected $table = '';
    protected $view = '';

    public $Records = null;

    function __construct($appId, $apiKey, $view, $table)
    {
        $this->appId  = $appId;
        $this->apiKey = $apiKey;
        $this->table  = $table;
        $this->view   = $view;

        $this->getRecords();
    }

    public function getOptions()
    {
        return $this->options;
    }

    public static function slug($text1, $text2)
    {
        $text1 = preg_replace('~[^\\pL\d]+~u', '-', $text1);
        $text1 = trim($text1, '-');
        $text1 = iconv('utf-8', 'us-ascii//TRANSLIT', $text1);
        $text1 = strtolower($text1);
        $text1 = preg_replace('~[^-\w]+~', '', $text1);

        $text2 = preg_replace('~[^\\pL\d]+~u', '-', $text2);
        $text2 = trim($text2, '-');
        $text2 = iconv('utf-8', 'us-ascii//TRANSLIT', $text2);
        $text2 = strtolower($text2);
        $text2 = preg_replace('~[^-\w]+~', '', $text2);

        return $text1 . '-' . $text2;
    }

    private function getUrl()
    {
        return $this->url . '/' . $this->appId . '/' . urlencode($this->table) . '?maxRecords=1000&view=' . urlencode($this->view) . '&api_key=' . $this->apiKey;
    }

    private function detectFields($records)
    {
        $knownFields = [];
        $knownValues = [];

        foreach ($records as $record) {
            foreach ($record['fields'] as $k => $v) {
                if (is_array($v)) {
                    $knownFields[$k] = [];
                } else {
                    $knownFields[$k] = '';
                }
            }
        }

        return [$knownFields, $knownValues];
    }

    private function getRecords()
    {
        $records = [];

        $offset = 0;

        while ($offset !== null) {
            $json = file_get_contents($this->getUrl() . '&offset=' . $offset);
            $php  = json_decode($json, true);

            if (array_key_exists('offset', $php)) {
                $offset = $php['offset'];
            } else {
                $offset = null;
            }

            $records = array_merge(array_values($records), array_values($php['records']));
        }

        list($knownFields, $knownValues) = $this->detectFields($records);

        $AirtableRecordCollection = new AirtableRecordCollection();
        $AirtableRecordCollection->setFields($knownFields);

        $this->options = $knownValues;

        foreach ($records as $record) {
            $AirtableRecordCollection->addRecord($record);
        }

        $this->Records = $AirtableRecordCollection;
    }
}
<?php

/** @noinspection PhpReturnValueOfMethodIsNeverUsedInspection */

/** @noinspection SqlCaseVsIf */

/** @noinspection SqlConstantExpression */

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use application\apis\Slack;
use application\exceptions\CurlErrorException;
use application\exceptions\DatabaseQueryFailed;
use application\exceptions\DatabaseRecordNotFoundException;
use application\exceptions\FileNotReadableException;
use application\exceptions\FileNotWritableException;
use application\exceptions\UnexpectedResultException;
use application\types\Email\Email;

class counts_model extends base_model
{
    /**
     *
     */
    const ADMIN_EMAIL = 'jd@xcelmg.com';

    /**
     * Cache keys for redis
     */
    const CACHE_KEY = [
        'DEBUG'                   => 'counts:debug',
        'PART_OPTIONS'            => 'counts:part-options',
        'STATISTICS'              => 'counts:statistics',
        'SUPPRESSIONS_STATISTICS' => 'counts:suppressions-statistics',
        'BREAKDOWNS'              => 'counts:breakdowns',
        'ZIPCODES'                => 'counts:zipcodes',
    ];

    /**
     *
     */
    const COLUMNS_DATASET_PROSPECTS_IMPORT = [
        'CustomerNumber'  => ['customernumber', 'custnum', 'customernum'],
        'First'           => ['first', 'fname'],
        'Last'            => ['last', 'lname'],
        'Address'         => ['address', 'address_1'],
        'City'            => ['city'],
        'State'           => ['state', 'st'],
        'Zip'             => ['zip', 'zipcode'],
        'Email'           => ['email'],
        'Home'            => ['homephone', 'home', 'phone'],
        'Cell'            => ['cellphone', 'cell'],
        'Year'            => ['year'],
        'Make'            => ['make'],
        'Model'           => ['model'],
        'Vin'             => ['vin'],
        'VehicleType'     => ['newused'],
        'DealType'        => ['dealtype'],
        'LeaseExpiring'   => ['leaseexp', 'leaseexpiration'],
        'SoldDate'        => ['solddate', 'deldate'],
        'LastServiceDate' => ['lastserviced', 'lastactive', 'lastservice'],
    ];

    /**
     * Columns used in exported records
     * Key: Database table column name
     * Value: Exported columnn header value
     */
    const COLUMNS_EXPORT = [
        'SourceId'    => 'SourceId',
        'First'       => 'First',
        'Last'        => 'Last',
        'Address'     => 'Address',
        'City'        => 'City',
        'State'       => 'State',
        'Zip'         => 'Zip',
        'Distance'    => 'Distance',
        'Vin'         => 'VIN',
        'VehicleType' => 'Type',
        'Year'        => 'Year',
        'Make'        => 'Make',
        'Model'       => 'Model',
        'Email'       => 'Email',
        'Home'        => 'Home Phone',
        'Cell'        => 'Cell Phone',
        'DataType'    => 'Data Type',
    ];

    /**
     * Columns used in exported suppressions
     * Key: Database table column name
     * Value: Exported columnn header value
     */
    const COLUMNS_SUPPRESSION_EXPORT = [
        'First'   => 'First',
        'Last'    => 'Last',
        'Address' => 'Address',
        'City'    => 'City',
        'State'   => 'State',
        'Zip'     => 'Zip',
    ];

    /**
     *
     */
    const COLUMNS_SUPPRESSION_IMPORT = [
        'First'   => ['first', 'fname'],
        'Last'    => ['last', 'lname'],
        'Address' => ['address', 'address_1'],
        'City'    => ['city'],
        'State'   => ['state', 'st'],
        'Zip'     => ['zip', 'zipcode'],
    ];

    /**
     * Available filter field names
     */
    const COUNT_FILTERS = [
        'GlobalRadius',
        'GlobalZips',
        'GlobalVehicleYearMin',
        'GlobalVehicleYearMax',
        'GlobalMakes',
        'GlobalModels',
        'SalesInclude',
        'SalesVehicleType',
        'SalesMonthsMin',
        'SalesMonthsMax',
        'SalesServiceInclude',
        'SalesServiceMonthsMin',
        'SalesServiceMonthsMax',
        'SalesOptionCashPurchases',
        'LeasesInclude',
        'LeasesMonthsMin',
        'LeasesMonthsMax',
        'LeasesServiceInclude',
        'LeasesServiceMonthsMin',
        'LeasesServiceMonthsMax',
        'LeasesMonthsExpiring',
        'ServicesInclude',
        'ServicesMonthsMin',
        'ServicesMonthsMax',
    ];

    /**
     *
     */
    const COUNT_PRESETS = [
        'Reset'            => [
            'GlobalRadius'             => null,
            'GlobalVehicleYearMin'     => null,
            'GlobalVehicleYearMax'     => null,
            'SalesInclude'             => null,
            'SalesVehicleType'         => null,
            'SalesMonthsMin'           => null,
            'SalesMonthsMax'           => null,
            'SalesOptionCashPurchases' => null,
            'LeasesInclude'            => null,
            'LeasesMonthsMin'          => null,
            'LeasesMonthsMax'          => null,
            'LeasesMonthsExpiring'     => null,
            'ServicesInclude'          => null,
            'ServicesMonthsMin'        => null,
            'ServicesMonthsMax'        => null,
        ],
        'Bryant\'s Way'    => [
            'GlobalRadius'             => 50,
            'GlobalVehicleYearMin'     => 2008,
            'GlobalVehicleYearMax'     => 2023,
            'SalesInclude'             => 1,
            'SalesVehicleType'         => null,
            'SalesMonthsMin'           => 19,
            'SalesMonthsMax'           => 84,
            'SalesOptionCashPurchases' => null,
            'LeasesInclude'            => 1,
            'LeasesMonthsMin'          => 19,
            'LeasesMonthsMax'          => 84,
            'LeasesMonthsExpiring'     => null,
            'ServicesInclude'          => 1,
            'ServicesMonthsMin'        => null,
            'ServicesMonthsMax'        => null,
        ],
        'Rod\'s Way'       => [
            'GlobalRadius'             => 100,
            'GlobalVehicleYearMax'     => 2023,
            'GlobalVehicleYearMin'     => 2012,
            'SalesInclude'             => 1,
            'SalesVehicleType'         => null,
            'SalesMonthsMin'           => 30,
            'SalesMonthsMax'           => 72,
            'SalesOptionCashPurchases' => 1,
            'LeasesInclude'            => 1,
            'LeasesMonthsMin'          => null,
            'LeasesMonthsMax'          => null,
            'LeasesMonthsExpiring'     => 6,
            'ServicesInclude'          => 1,
            'ServicesMonthsMin'        => null,
            'ServicesMonthsMax'        => 24,
        ],
        'Inactive Service' => [
            'GlobalRadius'             => 100,
            'GlobalVehicleYearMax'     => 2023,
            'GlobalVehicleYearMin'     => 2012,
            'SalesInclude'             => 1,
            'SalesVehicleType'         => null,
            'SalesMonthsMin'           => 12,
            'SalesMonthsMax'           => 48,
            'SalesOptionCashPurchases' => null,
            'SalesServiceInclude'      => 1,
            'SalesServiceMonthsMin'    => 12,
            'SalesServiceMonthsMax'    => 36,
            'LeasesInclude'            => 1,
            'LeasesMonthsMin'          => 12,
            'LeasesMonthsMax'          => 48,
            'LeasesMonthsExpiring'     => null,
            'LeasesServiceInclude'     => 1,
            'LeasesServiceMonthsMin'   => 12,
            'LeasesServiceMonthsMax'   => 36,
            'ServicesInclude'          => 1,
            'ServicesMonthsMin'        => 12,
            'ServicesMonthsMax'        => 36,
        ],
    ];

    /**
     *
     */
    const COUNT_STATUS_NEW = 'NEW';

    /**
     *
     */
    const COUNT_STATUS_PENDING = 'PENDING';

    /**
     *
     */
    const COUNT_STATUS_READY = 'READY';

    /**
     *
     */
    const COUNT_STATUS_REQUESTED = 'REQUESTED';

    /**
     *
     */
    const DATASET_TYPES = [
        'HT',
        'HT-R',
        'M1',
        'M1-R',
        'Polk',
        'Polk (Unused)',
    ];

    /**
     *
     */
    const NCOA_STATE_FINISHED = 'FINISHED';

    /**
     *
     */
    const NCOA_STATE_STARTED = 'STARTED';

    /**
     *
     */
    const NCOA_STATUS_DOWNLOAD = 'DOWNLOAD';

    /**
     *
     */
    const NCOA_STATUS_EXECUTE = 'EXECUTE';

    /**
     *
     */
    const NCOA_STATUS_EXPORT = 'EXPORT';

    /**
     *
     */
    const NCOA_STATUS_IMPORT = 'IMPORT';

    /**
     *
     */
    const NCOA_STATUS_QUOTE = 'QUOTE';

    /**
     *
     */
    const NCOA_STATUS_UPLOAD = 'UPLOAD';

    /**
     *
     */
    const SOURCE_TYPE_COUNT = 'COUNT';

    /**
     *
     */
    const SOURCE_TYPE_DMS = 'DMS';

    /**
     *
     */
    const SOURCE_TYPE_FILE = 'FILE';

    /**
     *
     */
    const SOURCE_TYPE_JOB = 'JOB';

    /**
     *
     */
    const SOURCE_TYPE_RADIUS = 'RADIUS';

    /**
     *
     */
    const SOURCE_TYPE_RESPONSE = 'RESPONSE';

    /**
     *
     */
    const SOURCE_TYPE_SET = 'SET';

    /**
     * @var bool
     */
    private $cacheEnabled = true;

    /**
     * @var array
     */
    private $debug;

    /**
     * @var float
     */
    private $debugStartTime;

    /**
     * @var string
     */
    private $dmsTableSuffix;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->debugStartTime = microtime(true);

        $this->load->model('common_model');
    }

    /**
     * Friendly names for Accutrace status message field names
     *
     * @param string $field
     * @return mixed|string
     */
    public static function friendlyAccuzipFieldName($field)
    {
        $fields = [
            'task_name'                 => 'Task',
            'task_percentage_completed' => 'Percent',
            'success'                   => 'Success',
            'total_records'             => 'Records',
            'task_state'                => 'State',
            'additional_output_fields'  => 'Fields',
            'task_process'              => 'Process',
        ];

        if (array_key_exists($field, $fields)) {
            return $fields[$field];
        }

        return $field;
    }

    /**
     * Flush the cache for a specific count
     *
     * @param int $countId
     * @return void
     */
    public function cacheFlushPublic($countId)
    {
        $this->cacheFlush($countId);

        foreach ($this->getCountParts($countId) as $part) {
            $this->cacheFlush($countId, $part['SourceId']);
        }
    }

    /**
     * @param string   $zip
     * @param int|null $clientId
     * @param int|null $userId
     * @return int
     * @throws DatabaseQueryFailed
     * @throws InvalidArgumentException
     */
    public function createCount($zip, $clientId = null, $userId = null)
    {
        if (!preg_match('/^\d{5}$/', $zip)) {
            throw new InvalidArgumentException('Invalid zip code ' . $zip);
        }

        $clientId = (int)$clientId ?: null;
        $userId   = (int)$userId ?: null;

        /** @noinspection SqlInsertValues */
        $sql = 'INSERT INTO application_counts (ClientId, UserId, Zip, Created) VALUES (' . ($clientId ?: 'NULL') . ', ' . ($userId ?: 'NULL') . ', "' . $zip . '", NOW())';

        if (!$this->common_model->execute_query($sql)) {
            throw new DatabaseQueryFailed('Failed to create new count. MySQL said: ' . mysql_error());
        }

        $count_id = $this->common_model->mysql_insert_id();

        $this->updateCountStatusMessage($count_id, __FUNCTION__);

        $count_id = mysql_insert_id();

        return $count_id;
    }

    /**
     * @param int    $countId
     * @param string $filepath
     * @return void
     * @throws FileNotReadableException
     */
    public function createCountSuppressionsFromFile($countId, $filepath)
    {
        $this->updateCountStatusMessage($countId, 'Create suppressions from file');

        // Attempt to open the CSV file for reading
        if (!$fh = fopen($filepath, 'rb')) {
            throw new FileNotReadableException('Failed to open ' . $filepath);
        }

        // Initialize line number counter
        $line_number = 0;

        // Initialize column map
        $column_map = [];

        // Read the CSV file line by line
        while (($row = fgetcsv($fh)) !== false) {
            // Increment line counter
            $line_number++;

            // Define column map if this is the first line
            if ($line_number == 1) {
                // Map imported column names to internal column names
                foreach ($row as $imported_column_position => $imported_column_name) {
                    $imported_column_name = strtolower($imported_column_name);
                    $imported_column_name = trim($imported_column_name);

                    // Check if the imported column name matches any known aliases
                    foreach (self::COLUMNS_SUPPRESSION_IMPORT as $column_name => $column_aliases) {
                        $column_name = strtolower($column_name);

                        if (in_array($imported_column_name, $column_aliases)) {
                            $column_map[$imported_column_position] = $column_name;
                        }
                    }
                }

                continue;
            }

            // Escape and quote each value in the row
            foreach ($row as $key => $value) {
                $row[$key] = "'" . mysql_escape_string($value) . "'";
            }

            // Trim and convert all values to uppercase
            $row = array_map(static function ($value) {
                return strtoupper(trim($value));
            }, $row);

            // Remove any columns that don't exist in the column map
            $row = array_intersect_key($row, $column_map);

            // Ensure the number of columns matches the expected count
            if (count($column_map) != count($row)) {
                throw new UnexpectedValueException('Column count mismatch on line ' . $line_number . '. Column names are: ' . implode(', ', $column_map) . '. Row data is: ' . implode(', ', $row));
            }

            // Combine the row data with the column map to create an associative array
            $insert_data = array_combine($column_map, $row);

            // Initialize the source ID to the current timestamp
            $sourceId = time();

            // Construct the SQL query to insert the row into the suppressions table
            /** @noinspection SqlInsertValues */
            $sql = 'INSERT INTO application_counts_suppressions (
                        `CountId`,
                        `SourceType`,
                        `SourceId`,
                        ' . implode(',', $column_map) . '
                    ) VALUES (
                        "' . $countId . '",
                        "' . self::SOURCE_TYPE_FILE . '",
                        "' . $sourceId . '",
                        ' . implode(',', $insert_data) . '
                    )';

            // Execute the SQL query
            $this->common_model->execute_query($sql);
        }

        $this->cacheFlush($countId);

        // Close the file handle
        fclose($fh);
    }

    /**
     * @param int $countId
     * @return void
     */
    public function createCountSuppressionsFromRecentlyContactedProspects($countId)
    {
        $this->updateCountStatusMessage($countId, 'Suppressing recently contacted records');

        $count = $this->getCount($countId);

        $sql = 'SELECT Id
                  FROM application_counts
                 WHERE Zip IN (SELECT target FROM application_counts_zips WHERE source = "' . $count['Zip'] . '")
                   AND Created > DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH) AND Id != "' . $countId . '"
                   AND ClientId != "' . $count['ClientId'] . '"';

        $count_ids = $this->common_model->array_result_field($sql, 'Id');

        if (empty($count_ids)) {
            return;
        }

        $sql = 'INSERT INTO application_counts_suppressions (CountId, SourceType, SourceId, First, Last, Address, City, State, Zip)
                SELECT "' . $count['Id'] . '",
                       "' . self::SOURCE_TYPE_RADIUS . '",
                       CountId,
                       First,
                       Last,
                       Address,
                       City,
                       State,
                       LEFT(Zip, 5) AS Zip
                  FROM application_counts_prospects
                 WHERE CountId IN (' . implode(',', $count_ids) . ') AND flag_selected = 1
                ';

        $this->common_model->execute_query($sql);
    }

    /**
     * @param string $name
     * @param string $filepath
     * @param string $filename
     * @param int    $clientId
     * @param string $type
     * @return void
     * @throws DatabaseQueryFailed
     * @throws FileNotReadableException
     */
    public function createDataset($name, $filepath, $filename, $clientId = null, $type = null)
    {
        // Insert a dataset record into the database
        $sql = 'INSERT INTO application_counts_datasets (`ClientId`, `Name`, `Type`, `Filename`, `Created`) VALUES (' . ($clientId ?: 'NULL') . ', "' . $name . '", ' . ('"' . $type . '"' ?: 'NULL') . ', "' . mysql_escape_string($filename) . '", NOW())';

        // Execute the query and check for errors
        if (!$this->common_model->execute_query($sql)) {
            throw new DatabaseQueryFailed('Failed to create new dataset. MySQL said: ' . mysql_error());
        }

        // Get the ID of the newly created dataset
        $dataset_id = mysql_insert_id();

        // Open the CSV file for reading
        if (!$handle = fopen($filepath, 'rb')) {
            throw new FileNotReadableException('Failed to open ' . $filepath);
        }

        // Initialize line number counter
        $line_number = 0;

        // Initialize column map
        $column_map = [];

        // Read the CSV file line by line
        while (($row = fgetcsv($handle)) !== false) {
            // Increment line counter
            $line_number++;

            // If this is the first line, treat it as headers
            if ($line_number == 1) {
                // Map imported column names to internal column names
                foreach ($row as $imported_column_position => $imported_column_name) {
                    $imported_column_name = stringCleanAscii($imported_column_name);
                    $imported_column_name = strtolower($imported_column_name);
                    $imported_column_name = trim($imported_column_name);

                    // Check if the imported column name matches any known aliases
                    foreach (self::COLUMNS_DATASET_PROSPECTS_IMPORT as $column_name => $column_aliases) {
                        $column_name = strtolower($column_name);

                        if (in_array($imported_column_name, $column_aliases)) {
                            $column_map[$imported_column_position] = $column_name;
                        }
                    }
                }

                continue;
            }

            // Trim and convert all values to uppercase
            $row = array_map(static function ($value) {
                return strtoupper(trim($value));
            }, $row);

            // Remove any columns that don't exist in the column map
            $row = array_intersect_key($row, $column_map);

            // Ensure the number of columns matches the expected count
            if (count($column_map) != count($row)) {
                throw new UnexpectedValueException('Column count mismatch on line ' . $line_number . '. Column names are: ' . implode(', ', $column_map) . '. Row data is: ' . implode(', ', $row));
            }

            // Combine the row data with the column map to create an associative array
            $insert_data = array_combine($column_map, $row);

            // Add the dataset ID to the insert data
            $insert_data['DatasetId'] = $dataset_id;

            // Default DealType to SALE if not provided
            if (!array_key_exists('dealtype', $insert_data)) {
                $insert_data['dealtype'] = 'SALE';
            }

            // Format date fields if they exist in the data
            if (array_key_exists('solddate', $insert_data)) {
                $insert_data['solddate'] = !empty($insert_data['solddate']) ? date('Y-m-d', strtotime($insert_data['solddate'])) : null;
            }

            if (array_key_exists('lastservicedate', $insert_data)) {
                $insert_data['lastservicedate'] = !empty($insert_data['lastservicedate']) ? date('Y-m-d', strtotime($insert_data['lastservicedate'])) : null;
            }

            if (array_key_exists('leaseexpiring', $insert_data)) {
                $insert_data['leaseexpiring'] = !empty($insert_data['leaseexpiring']) ? date('Y-m-d', strtotime($insert_data['leaseexpiring'])) : null;
            }

            $this->common_model->mysql_insert('application_counts_datasets_prospects', $insert_data);
        }
    }

    /**
     * Add a message to the internal debug log
     *
     * @param string $message
     * @return void
     */
    public function debug($message)
    {
        $this->debug[] = [$message, microtime(true) - $this->debugStartTime];
    }

    /**
     * Get the current debug log
     *
     * @return array
     */
    public function debugGetCurrent()
    {
        return $this->debug;
    }

    /**
     * Save the current debug log to cache
     *
     * @param int $countId
     * @return bool
     */
    public function debugSave($countId)
    {
        $json = json_encode($this->debug);

        $sql = 'UPDATE application_counts SET Debug = ' . $this->db->escape($json) . ' WHERE Id = ' . $this->db->escape($countId);

        return $this->common_model->execute_query($sql);
    }

    /**
     * @param int $countId
     * @return true
     * @throws DatabaseQueryFailed
     */
    public function excludeCountProspectsDuplicates($countId)
    {
        $this->updateCountStatusMessage($countId, 'Excluding duplicate records');

        // Reset flag_duplicate on prospects
        $sql = 'UPDATE application_counts_prospects SET flag_duplicate = 0 WHERE CountId = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        $temp_table = 'temp_prospects_' . $countId;

        $sql = 'DROP TABLE IF EXISTS ' . $temp_table;
        $this->common_model->execute_query($sql);

        $sql = 'CREATE TEMPORARY TABLE ' . $temp_table . "  AS
                SELECT id
                FROM application_counts_prospects AS sub
                WHERE countid = '" . $countId . "'
                  AND id NOT IN (
                                SELECT MAX(t2.id)
                                FROM application_counts_prospects AS t2
                                INNER JOIN (
                                           SELECT address, LEFT(zip, 5) AS zip_prefix,
                                                  MAX(GREATEST(COALESCE(SoldDate, '0000-00-00'), COALESCE(LastServiceDate, '0000-00-00'))) AS max_latest_date
                                           FROM application_counts_prospects
                                           WHERE countid = '" . $countId . "'
                                           GROUP BY address, LEFT(zip, 5)
                                           ) AS latest_dates
                                           ON t2.address = latest_dates.address
                                               AND LEFT(t2.zip, 5) = latest_dates.zip_prefix
                                               AND GREATEST(COALESCE(t2.SoldDate, '0000-00-00'), COALESCE(t2.LastServiceDate, '0000-00-00')) = latest_dates.max_latest_date
                                WHERE t2.countid = '" . $countId . "'
                                GROUP BY t2.address, LEFT(t2.zip, 5)
                                );
                ";

        if (!$this->common_model->execute_query($sql)) {
            throw new DatabaseQueryFailed('Could not create temporary table for duplicates');
        }

        $sql = 'UPDATE application_counts_prospects AS t1
                    JOIN ' . $temp_table . ' AS t2 ON t1.id = t2.id
                SET t1.flag_duplicate = 1
                WHERE t1.countid = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $temp_table;
        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return bool
     */
    public function existsCount($countId)
    {
        return $this->common_model->exists('application_counts', 'Id', $countId);
    }

    /**
     * @param int $datasetId
     * @return bool
     */
    public function existsDataset($datasetId)
    {
        return $this->common_model->exists('application_counts_datasets', 'Id', $datasetId);
    }

    /**
     * @param int $countId
     * @return string
     */
    public function filenameCountReport($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->counts_model->getCount($countId);

        $filename = $count['client__Name'] . ' - #' . $countId . ' - Prospect Count - ' . date('Y-m-d') . '.xls';

        return $filename;
    }

    /**
     * @param int $countId
     * @return string
     */
    public function filenameCountSuppressionAll($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->counts_model->getCount($countId);

        $filename = 'Suppression ' . $countId . ' - ' . ($count['client__Name'] ?: $count['Zip']) . ' - ' . date('Y-m-d') . ' - All.csv';

        return $filename;
    }

    /**
     * @param int $countId
     * @return string
     */
    public function filenameCountSuppressionSelected($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->counts_model->getCount($countId);

        $filename = 'Suppression ' . $countId . ' - ' . ($count['client__Name'] ?: $count['Zip']) . ' - ' . date('Y-m-d') . ' - Selected.csv';

        return $filename;
    }

    /**
     * @param int $countId
     * @return string
     */
    public function filepathCountReport($countId)
    {
        return path_to_resources('counts/reports/' . $countId . '.xls');
    }

    /**
     * @param int $countId
     * @return string
     */
    public function filepathCountSuppressionAll($countId)
    {
        return path_to_resources('counts/suppression/' . $countId . '-all.csv');
    }

    /**
     * @param int $countId
     * @return string
     */
    public function filepathCountSuppressionSelected($countId)
    {
        return path_to_resources('counts/suppression/' . $countId . '-selected.csv');
    }

    /**
     * @param int $countId
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generateCountExcelReport($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->getCount($countId);
        $parts = $this->getCountParts($countId);

        $report = $this->getCountReport($countId);

        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();

        // Remove the default sheet
        $spreadsheet->removeSheetByIndex(0);

        // Set global styles
        $spreadsheet->getDefaultStyle()->getFont()->setSize(13);

        // Define header styles
        $headerStyle = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF777777'],
                ],
            ],
            'fill'    => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF8DCB3B',
                ],
            ],
            'font'    => [
                'name'  => 'Verdana',
                'color' => [
                    'argb' => 'FF444444',
                ],
            ],
        ];

        // Define text cell styles
        $textCellStyle = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF777777'],
                ],
            ],
            'font'    => [
                'name'  => 'Verdana',
                'color' => [
                    'argb' => 'FF555555',
                ],
            ],
        ];

        // Define numeric cell style
        $numericCellStyle = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FF777777'],
                ],
            ],
            'font'    => [
                'name'  => 'Courier New',
                'color' => [
                    'argb' => 'FF000000',
                ],
            ],
        ];

        // For each part
        foreach ($parts as $part) {
            $reportPart = $report[$part['Id']];

            if (empty($reportPart['years'])) {
                continue;
            }

            // Create a new spreadsheet
            $sheet = $spreadsheet->createSheet();

            // Title the spreadsheet
            if ($part['SourceType'] == self::SOURCE_TYPE_DMS) {
                $sheet->setTitle('DMS');
            } else {
                $sheet->setTitle(substr($part['Name'], 0, 30));
            }

            // Create an array of letters for the columns ranging from A-Z and AA-ZZ (52 available columns)
            $letters = array_merge(range('A', 'Z'), array_map(static function ($letter) {
                return 'A' . $letter;
            }, range('A', 'Z')));

            // Foreach subreport
            foreach ($reportPart as $subreport) {
                $column_headers = array_keys($subreport[0]);

                $reportLetters = [];

                foreach ($column_headers as $value) {
                    $reportLetters[] = array_shift($letters);

                    $cellLetter = end($reportLetters);
                    $cellNUmber = 1;

                    $sheet->setCellValue($cellLetter . $cellNUmber, $value);
                    $sheet->getStyle($cellLetter . $cellNUmber)->applyFromArray($headerStyle);
                }

                reset($reportLetters);
                $rowNumber = 2;

                foreach ($subreport as $line) {
                    foreach ($line as $key => $value) {
                        $cellLetter = current($reportLetters);
                        $cellNUmber = $rowNumber;

                        if ($key === 'Distance') {
                            $sheet->setCellValue($cellLetter . $cellNUmber, $value);
                            $sheet->getStyle($cellLetter . $cellNUmber)->getNumberFormat()->setFormatCode('0.00');
                            $sheet->getStyle($cellLetter . $cellNUmber)->applyFromArray($numericCellStyle);
                        } elseif (in_array($key, ['Count', 'Running'])) {
                            $sheet->setCellValue($cellLetter . $cellNUmber, $value);
                            $sheet->getStyle($cellLetter . $cellNUmber)->applyFromArray($numericCellStyle);
                        } else {
                            $sheet->setCellValueExplicit($cellLetter . $cellNUmber, $value, DataType::TYPE_STRING);
                            $sheet->getStyle($cellLetter . $cellNUmber)->applyFromArray($textCellStyle);
                        }

                        next($reportLetters);

                        $sheet->getColumnDimension($cellLetter)->setAutoSize(true);
                    }

                    reset($reportLetters);
                    $rowNumber++;
                }

                // This leaves a blank column between reports
                $letter = array_shift($letters);
                $sheet->getColumnDimension($letter)->setWidth(4.0);
            }
        }

        $writer = new Xls($spreadsheet);

        $writer->save($this->filepathCountReport($count['Id']));

        return $this->filepathCountReport($count['Id']);
    }

    /**
     * @param int $countId
     * @return string
     */
    public function generateCountSuppressionsAllCsv($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->counts_model->getCount($countId);

        $columns = self::COLUMNS_SUPPRESSION_EXPORT;

        $sql = 'SELECT ' . implode(',', array_keys($columns)) . ' 
                FROM application_counts_prospects 
                WHERE CountId = ' . $count['Id'] . ' 
                UNION 
                SELECT first, last, address, city, state, zip 
                FROM application_do_not_mail 
                WHERE address != "" 
                UNION 
                SELECT first, last, address, city, state, zip 
                FROM application_counts_suppressions 
                WHERE countid = ' . $count['Id'] . ' 
                GROUP BY address, zip';

        $fp = fopen($this->filepathCountSuppressionAll($countId), 'wb');

        fputcsv($fp, $columns);

        $res = $this->common_model->execute_query($sql);

        while ($row = mysql_fetch_assoc($res)) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $this->filepathCountSuppressionAll($countId);
    }

    /**
     * @param int $countId
     * @return string
     */
    public function generateCountSuppressionsSelectedCsv($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->counts_model->getCount($countId);

        $columns = self::COLUMNS_SUPPRESSION_EXPORT;

        $sql = 'SELECT ' . implode(',', array_keys($columns)) . ' 
                FROM application_counts_prospects 
                WHERE CountId = ' . $count['Id'] . ' AND flag_selected = 1 
                UNION 
                SELECT first, last, address, city, state, zip 
                FROM application_do_not_mail 
                WHERE address != "" 
                UNION 
                SELECT first, last, address, city, state, zip 
                FROM application_counts_suppressions 
                WHERE countid = ' . $count['Id'] . ' 
                GROUP BY address, zip';

        $fp = fopen($this->filepathCountSuppressionSelected($countId), 'wb');

        fputcsv($fp, $columns);

        $res = $this->common_model->execute_query($sql);

        while ($row = mysql_fetch_assoc($res)) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $this->filepathCountSuppressionSelected($countId);
    }

    /**
     * @param int $countId
     * @return string
     * @noinspection HtmlDeprecatedAttribute
     */
    public function generateEmailBody($countId)
    {
        $count = $this->getCount($countId);

        $linkToCount = urlController(counts::class, 'view', $count['Id']);

        $data = [
            'parts'      => $this->counts_model->getCountParts($countId),
            'statistics' => $this->counts_model->getCountStatistics($countId),
        ];

        foreach ($data['parts'] as $part) {
            $data['statistics_parts'][$part['SourceId']] = $this->counts_model->getCountStatistics($countId, $part['SourceId']);
        }

        $body = Email::simpleStandardTemplateHeader();

        $body .= '<h1>' . $count['client__Name'] . '</h1>';

        $body .= '<a href="' . $linkToCount . '">View Count</a>';
        $body .= '<h2>Summary</h2>';
        $body .= '<table>';
        $body .= '  <thead>';
        $body .= '    <tr>';
        $body .= '      <th>Data Set</th>';
        $body .= '      <th>Total</th>';
        $body .= '    </tr>';
        $body .= '  </thead>';
        $body .= '  <tbody>';

        foreach ($data['parts'] as $part) {
            $body .= '    <tr>';
            $body .= '      <td>';

            if ($part['SourceId'] == 0) {
                $body .= 'Client DMS Data';
            } else {
                $body .= $part['Type'] . ' Dataset: ' . $part['Name'] . ' - ' . date('Y-m-d', strtotime($part['Created']));
            }

            $body .= '      </td>';
            $body .= '      <td align="right">' . number_format($data['statistics_parts'][$part['SourceId']]['available']) . '</td>';
            $body .= '    </tr>';
        }

        $body .= '  <tfoot>';
        $body .= '    <tr>';
        $body .= '      <td>Total</td>';
        $body .= '      <td align="right">' . number_format($data['statistics']['available']) . ' </td>';
        $body .= '    </tr>';
        $body .= '  </tfoot>';
        $body .= '  </tbody>';
        $body .= '</table>';
        $body .= '<br><br>';

        foreach ($data['parts'] as $part) {
            $body .= '<h2>';

            if ($part['SourceId'] == 0) {
                $body .= 'Client DMS Data';
            } else {
                $body .= $part['Type'] . ' Dataset: ' . $part['Name'] . ' - ' . date('Y-m-d', strtotime($part['Created']));
            }

            $body .= '</h2>';

            $body .= '<table><tr><td style="vertical-align: top; border: 0; padding: 0;">';

            $body .= '<table >';
            $body .= '  <thead>';
            $body .= '    <tr>';
            $body .= '      <th>Filter</th>';
            $body .= '      <th>Value</th>';
            $body .= '    </tr>';
            $body .= '  </thead>';
            $body .= '  <tbody>';

            foreach (self::COUNT_FILTERS as $filter) {
                if (!empty($part[$filter])) {
                    $body .= '    <tr>';
                    $body .= '      <td nowrap>' . htmlspecialchars(preg_replace('/(?<!^)([A-Z])/', ' $1', $filter)) . '</td>';
                    $body .= '      <td>';

                    if (in_array($filter, ['SalesInclude', 'SalesServiceInclude', 'LeasesInclude', 'LeasesServiceInclude', 'ServicesInclude'])) {
                        $body .= $part[$filter] == 1 ? 'Yes' : 'No';
                    } elseif (in_array($filter, ['GlobalMakes', 'GlobalModels', 'GlobalZips'])) {
                        $body .= str_replace(',', ', ', $part[$filter]);
                    } else {
                        $body .= htmlspecialchars($part[$filter]);
                    }

                    $body .= '      </td>';
                    $body .= '    </tr>';
                }
            }

            $body .= '  </tbody>';
            $body .= '</table>';

            $body .= '</td><td style="vertical-align: top; border: 0; padding: 0 0 0 20px;">';

            $body .= '<table>';
            $body .= '  <thead>';
            $body .= '    <tr>';
            $body .= '      <th>Type</th>';
            $body .= '      <th>Total</th>';
            $body .= '    </tr>';
            $body .= '  </thead>';
            $body .= '  <tbody>';
            $body .= '    <tr>';
            $body .= '      <td>Sales</td>';
            $body .= '      <td align="right">' . number_format($data['statistics_parts'][$part['SourceId']]['sales']) . '</td>';
            $body .= '    </tr>';
            $body .= '    <tr>';
            $body .= '      <td>Leases</td>';
            $body .= '      <td align="right">' . number_format($data['statistics_parts'][$part['SourceId']]['leases']) . '</td>';
            $body .= '    </tr>';
            $body .= '    <tr>';
            $body .= '      <td>Service</td>';
            $body .= '      <td align="right">' . number_format($data['statistics_parts'][$part['SourceId']]['services']) . '</td>';
            $body .= '    </tr>';
            $body .= '  </tbody>';
            $body .= '  <tfoot>';
            $body .= '    <tr>';
            $body .= '      <td>Total</td>';
            $body .= '      <td align="right">' . number_format($data['statistics_parts'][$part['SourceId']]['available']) . '</td>';
            $body .= '    </tr>';
            $body .= '  </tfoot>';
            $body .= '</table>';
            $body .= '<br>';

            $body .= '</td></tr></table>';
        }

        $body .= Email::simpleStandardTemplateFooter();

        return $body;
    }

    public function getClientDatasetIds($clientId)
    {
        $sql = 'SELECT application_counts_datasets.Id
                  FROM application_counts_datasets
                 WHERE ClientId = ' . $this->db->escape($clientId) . '
             ORDER BY application_counts_datasets.Id DESC
             ';

        return $this->common_model->array_result_field($sql, 'Id');
    }

    /**
     * @param int $clientIds
     * @return array
     */
    public function getClientDatasets($clientIds)
    {
        $sql = 'SELECT application_counts_datasets.*,
                       DATE_FORMAT(application_counts_datasets.created, "%Y-%m-%d") AS __created
                  FROM application_counts_datasets
                 WHERE ClientId = ' . $clientIds . '
             ORDER BY application_counts_datasets.Id DESC
             ';

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int $clientId
     * @return array
     */
    public function getClientFeeds($clientId)
    {
        $sql = "SELECT Id,
                       Fcid,
                       DMSType,
                       SalesEnd,
                       ServiceEnd,
                       DATEDIFF(now(), SalesEnd)   AS days_since_last_sale,
                       DATEDIFF(now(), ServiceEnd) AS days_since_last_service
                  FROM application_fclient
                 WHERE Cid = '" . $clientId . "'
                 ORDER BY Id";

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int $clientId
     * @return array
     */
    public function getClientJobs($clientId)
    {
        $sql = "SELECT application_campaign.Name,
                       application_campaign.Type,
                       application_campaign_drop.Job,
                       application_campaign_drop.Qty,
                       application_campaign_drop.Homedate,
                       application_campaign_drop.CampaignId,
                       application_barcode_branch.Tablename
                  FROM application_campaign_drop 
             LEFT JOIN application_campaign ON application_campaign_drop.CampaignId = application_campaign.Id
             LEFT JOIN application_barcode_branch ON application_barcode_branch.CampaignId = application_campaign_drop.CampaignId
                 WHERE application_campaign.ClientId = '" . $clientId . "'
                   AND application_campaign_drop.job IS NOT NULL
                   AND application_barcode_branch.Tablename IS NOT NULL
                   AND application_campaign_drop.Homedate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              GROUP BY CampaignId, Job
              ORDER BY application_campaign_drop.Job DESC
              ";

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @return array
     */
    public function getClients()
    {
        $sql = "SELECT client.Id AS Id,
                       client.Name AS Name,
                       client.AccountExecutive,
                       LEFT(client.Zip, 5) AS Zip,
                       feed.Fcid AS Fcid,
                       IF(!ISNULL(parent.id), CONCAT(parent.Name), '') AS __parent_name,
                       associate.associateid
                  FROM application_client AS client
                LEFT JOIN application_client_associate AS associate
                       ON client.Id = associate.ClientId
                LEFT JOIN application_client AS parent
                       ON associate.AssociateId = parent.Id
                LEFT JOIN application_fclient AS feed
                       ON client.Id = feed.Cid
                LEFT JOIN application_fclient AS parent_feed
                       ON parent.Id = parent_feed.Cid  -- Joining fclient for parent
                 WHERE client.Name != ''
                   AND client.Ctype = '3'
                   AND (
                        associate.AssociateId IS NULL
                        OR associate.AssociateId NOT IN (
                            2555, -- Dealer Funnel
                            2098, -- DealerKi
                            3603, -- Driven Media Group DF
                            86    -- IAM
                        )
                        OR (
                            associate.AssociateId = 2555  -- Dealer Funnel
                            AND feed.id IS NOT NULL  -- Exception: include if parent has an entry in fclient
                        )
                   )
                GROUP BY client.id, client.Name
                ORDER BY client.Name;";

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int $countId
     * @return array|false
     */
    public function getCount($countId)
    {
        $sql = "SELECT application_counts.*,
                       application_client.Name AS client__Name,
                       application_user.Name AS user__Name,
                       (SELECT COUNT(*) FROM application_counts_prospects WHERE CountId = application_counts.Id) AS __total_prospects
                  FROM application_counts
             LEFT JOIN application_client
                       ON application_counts.ClientId = application_client.Id
             LEFT JOIN application_user
                       ON application_counts.UserId = application_user.Id
                 WHERE application_counts.Id = '" . $countId . "'";

        return $this->common_model->single_result_assoc($sql);
    }

    /**
     * @param int $countId
     * @return array
     */
    public function getCountAccuZipFiles($countId)
    {
        $files = [];

        foreach (['input', 'output'] as $type) {
            $files[$type]['name'] = $countId . '-' . $type . '.csv';
            $files[$type]['path'] = path_to_resources('counts/ncoa/' . $files[$type]['name']);

            if (file_exists($files[$type]['path'])) {
                $files[$type]['url']    = urlSharedResource('counts', 'ncoa', $files[$type]['name']);
                $files[$type]['exists'] = true;
                $files[$type]['time']   = filemtime($files[$type]['path']);
                $files[$type]['size']   = filesize($files[$type]['path']);
            } else {
                $files[$type]['url']    = null;
                $files[$type]['exists'] = false;
                $files[$type]['size']   = 0;
            }
        }

        return $files;
    }

    /**
     * @param int $countId
     * @return false|string
     */
    public function getCountAccuZipStatus($countId)
    {
        $count = $this->getCount($countId);

        if (empty($count['NcoaGuid'])) {
            return false;
        }

        $response = @file_get_contents('https://cloud2.iaccutrace.com/servoy-service/rest_ws/ws_360/v2_0/job/' . $count['NcoaGuid'] . '/QUOTE');

        try {
            $response = json_decode($response, true);
        } catch (Exception $e) {
            $response = ['Message' => 'Failed to decode JSON response'];
        }

        return $response;
    }

    /**
     * @param int   $countId
     * @param int   $sourceId
     * @param array $part
     * @return array
     */
    public function getCountBreakdowns($countId, $sourceId = null, $part = null)
    {
        if ($sourceId === null && $part === null) {
            $this->updateCountStatusMessage($countId, 'Calculate breakdowns for entire count');
        } elseif ($part === null) {
            $this->updateCountStatusMessage($countId, 'Calculate breakdowns for part ' . ($sourceId ?: 'DMS'));
        }

        if ($part == null && $data = $this->cacheGet(self::CACHE_KEY['BREAKDOWNS'], $countId, $sourceId)) {
            return $data;
        }

        $subquery_sql = $this->selectionSql($countId, $sourceId, $part);

        $data = [
            'years'  => $this->common_model->array_result_assoc('SELECT COUNT(*) AS count, Year                                       FROM (' . $subquery_sql . ') AS embedded GROUP BY Year  ORDER BY Year DESC'),
            'makes'  => $this->common_model->array_result_assoc('SELECT COUNT(*) AS count, Make                                       FROM (' . $subquery_sql . ') AS embedded GROUP BY Make  ORDER BY count DESC'),
            'states' => $this->common_model->array_result_assoc('SELECT COUNT(*) AS count, State                                      FROM (' . $subquery_sql . ') AS embedded GROUP BY State ORDER BY State'),
            'zips'   => $this->common_model->array_result_assoc('SELECT COUNT(*) AS count, City, State, LEFT(Zip, 5) AS Zip, Distance FROM (' . $subquery_sql . ') AS embedded GROUP BY LEFT(Zip, 5) ORDER BY Distance'),
        ];

        if ($part == null) {
            $this->cacheSet(self::CACHE_KEY['BREAKDOWNS'], $data, $countId, $sourceId);
        }

        return $data;
    }

    /**
     * @param int $countId
     * @return array
     */
    public function getCountNcoaSummary($countId)
    {
        $sql   = 'SELECT COUNT(id) AS count FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND flag_selected = 1';
        $total = $this->common_model->single_result_field($sql, 'count');

        $sql     = 'SELECT COUNT(id) AS count FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND flag_selected = 1 AND (LastNCOA >= NOW() - INTERVAL 1 MONTH)';
        $current = $this->common_model->single_result_field($sql, 'count');

        $data = [
            'total_selected' => $total,
            'total_current'  => $current,
        ];

        return $data;
    }

    /**
     * @param int $countId
     * @return array
     */
    public function getCountParts($countId)
    {
        $sql = "SELECT *
                  FROM application_counts_parts
                  LEFT JOIN application_counts_datasets
                            ON application_counts_parts.SourceId = application_counts_datasets.Id
                 WHERE CountId = '" . $countId . "'
                 ORDER BY application_counts_datasets.Type, application_counts_datasets.Created DESC";

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int $countId
     * @return array
     */
    public function getCountPartsOptions($countId)
    {
        $this->updateCountStatusMessage($countId, 'Get options for parts');

        if ($data = $this->cacheGet(self::CACHE_KEY['PART_OPTIONS'], $countId)) {
            return $data;
        }

        $parts = $this->getCountParts($countId);

        $data = [];

        foreach ($parts as $part) {
            $sql = "SELECT MIN(Year) AS Year FROM application_counts_prospects WHERE CountId = '" . $countId . "' AND SourceId = '" . $part['SourceId'] . "'";

            $data[$part['SourceId']]['year_min'] = $this->common_model->single_result_field($sql, 'Year');

            $sql = "SELECT MAX(Year) AS Year FROM application_counts_prospects WHERE CountId = '" . $countId . "' AND SourceId = '" . $part['SourceId'] . "'";

            $data[$part['SourceId']]['year_max'] = $this->common_model->single_result_field($sql, 'Year');

            $sql = "SELECT DISTINCT(Make) FROM application_counts_prospects WHERE CountId = '" . $countId . "' AND SourceId = '" . $part['SourceId'] . "' AND TRIM(Make) != '' ORDER BY Make";

            $data[$part['SourceId']]['makes'] = $this->common_model->array_result_field($sql, 'Make');

            $sql = "SELECT DISTINCT(CONCAT(Make, ' ', Model)) AS __full_name, Make, Model FROM application_counts_prospects WHERE CountId = '" . $countId . "' AND SourceId = '" . $part['SourceId'] . "' AND TRIM(Model) != '' ORDER BY __full_name";

            $data[$part['SourceId']]['models'] = $this->common_model->array_result_assoc($sql);

            $sql = "SELECT DISTINCT(LEFT(Zip, 5)) AS Zip FROM application_counts_prospects WHERE CountId = '" . $countId . "' AND SourceId = '" . $part['SourceId'] . "' AND TRIM(Zip) != '' ORDER BY Zip";

            $data[$part['SourceId']]['zips'] = $this->common_model->array_result_field($sql, 'Zip');
        }

        $this->cacheSet(self::CACHE_KEY['PART_OPTIONS'], $data, $countId);

        return $data;
    }

    /**
     * @param int $countId
     * @param int $range
     * @return array
     */
    public function getCountPhoneReport($countId, $range = 18)
    {
        $sql = 'SELECT countid,
                       id,
                       sourceid,
                       sourcetype,
                       first,
                       last,
                       Address,
                       city,
                       state,
                       zip,
                       IF(cell IS NOT NULL AND cell != "", cell, home) AS cell,
                       SoldDate,
                       LastServiceDate,
                       LeaseExpiring,
                       email,
                       dealtype,
                       distance,
                       year,
                       make,
                       model,
                       vin,
                       CASE
                           WHEN LastServiceDate > DATE_SUB(CURRENT_DATE(), INTERVAL ' . $range . ' MONTH)
                               THEN "ServicedWithin-' . $range . '-Months"
                           ELSE "None"
                       END                                             AS Filter,
                       DealType
                  FROM application_counts_prospects
                 WHERE countid = ' . $this->db->escape($countId) . '
                   AND flag_selected = 1
                 ORDER BY SoldDate DESC,
                          LastServiceDate DESC';

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int   $countId
     * @param int   $sourceId
     * @param array $part
     * @return array
     */
    public function getCountProspects($countId, $sourceId = null, $part = null)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $sql = $this->selectionSql($countId, $sourceId, $part);

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int   $countId
     * @param int   $sourceId
     * @param array $part
     * @return null|string
     */
    public function getCountProspectsSql($countId, $sourceId = null, $part = null)
    {
        $sql = $this->selectionSql($countId, $sourceId, $part);

        // Remove the first 16 spaces from each line of $sql
        $sql = preg_replace('/^ {16}/m', '', $sql);

        return $sql;
    }

    /**
     * @param int $countId
     * @return array
     */
    public function getCountReport($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $parts = $this->getCountParts($countId);

        $report = [];

        foreach ($parts as $part) {
            $report[$part['Id']] = [
                'years'        => $this->common_model->array_result_assoc('SELECT Year, COUNT(*) AS Count                                                              FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected = 1 GROUP BY Year  ORDER BY Year DESC'),
                'makes'        => $this->common_model->array_result_assoc('SELECT Make, COUNT(*) AS Count                                                              FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected = 1 GROUP BY Make  ORDER BY Count DESC'),
                'makes_models' => $this->common_model->array_result_assoc('SELECT Make, Model, COUNT(*) AS Count                                                       FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected = 1 GROUP BY Make, Model ORDER BY Make, count DESC'),
                'states'       => $this->common_model->array_result_assoc('SELECT State, COUNT(*) AS Count                                                             FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected = 1 GROUP BY State ORDER BY State'),
                'zips'         => $this->common_model->array_result_assoc('SELECT City, State, LEFT(Zip, 5) AS Zip, Distance AS Distance, COUNT(*) AS Count FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected = 1 GROUP BY Zip, Distance ORDER BY Distance'),
            ];

            $sql = "SELECT CONCAT('Within ', rounded_distance, ' Miles') AS Description, Count,
                    (SELECT SUM(count)
                     FROM (
                         SELECT CEIL(Distance / 5) * 5 AS rounded_distance,
                                COUNT(*) AS Count
                         FROM application_counts_prospects
                         WHERE Distance >= 0
                           AND CountId = '" . $countId . "'
                           AND SourceId = '" . $part['SourceId'] . "'
                           AND flag_selected = 1
                         GROUP BY rounded_distance
                     ) AS subquery
                     WHERE subquery.rounded_distance <= main.rounded_distance) AS Running
                FROM (
                    SELECT CEIL(Distance / 5) * 5 AS rounded_distance,
                           COUNT(*) AS Count
                    FROM application_counts_prospects
                    WHERE Distance >= 0
                      AND CountId = '" . $countId . "'
                      AND SourceId = '" . $part['SourceId'] . "'
                      AND flag_selected = 1
                    GROUP BY rounded_distance
                ) AS main
                ORDER BY rounded_distance;
        ";

            $report[$part['Id']]['radius'] = $this->common_model->array_result_assoc($sql);
        }

        return $report;
    }

    /**
     * @param int $countId
     * @return array
     */
    public function getCountSelectionZipcodeStatistics($countId)
    {
        $this->updateCountStatusMessage($countId, 'Get selection zipcode statistics');

        if ($data = $this->cacheGet(self::CACHE_KEY['ZIPCODES'], $countId)) {
            return $data;
        }

        $sql = 'SELECT COUNT(*) AS selected, SourceId, City, State, LEFT(Zip, 5) AS Zip, Distance FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND flag_selected = 1 GROUP BY SourceId, Distance, Zip ORDER BY SourceId, Distance, Zip';

        $data = $this->common_model->array_result_assoc($sql);

        $this->cacheSet(self::CACHE_KEY['ZIPCODES'], $data, $countId);

        return $data;
    }

    /**
     * @param int   $count_id
     * @param int   $sourceId
     * @param array $part
     * @return array
     */
    public function getCountStatistics($count_id, $sourceId = null, $part = null)
    {
        if ($sourceId === null && $part === null) {
            $this->updateCountStatusMessage($count_id, 'Calculate statistics for entire count');
        } elseif ($part === null) {
            $this->updateCountStatusMessage($count_id, 'Calculate statistics for part ' . ($sourceId ?: 'DMS'));
        }

        if ($part == null && $data = $this->cacheGet(self::CACHE_KEY['STATISTICS'], $count_id, $sourceId)) {
            return $data;
        }

        // Input string
        $data = [
            'available'                => 0,
            'sales'                    => 0,
            'leases'                   => 0,
            'services'                 => 0,
            'phones'                   => 0,
            'emails'                   => 0,
            'earliest_sale_date'       => null,
            'latest_sale_date'         => null,
            'earliest_lease_date'      => null,
            'latest_lease_date'        => null,
            'earliest_lastservicedate' => null,
            'latest_lastservicedate'   => null,
            'min_year'                 => null,
            'max_year'                 => null,
            'matching'                 => 0,
            'company'                  => 0,
            'do_not_mail'              => 0,
            'duplicate'                => 0,
            'invalid'                  => 0,
            'nonresidential'           => 0,
            'suppressed'               => 0,
        ];

        $sql = $this->selectionSql($count_id, $sourceId, $part, false);

        $prospects = $this->common_model->array_result_assoc($sql);

        foreach ($prospects as $prospect) {
            $data['matching']++;

            if ($prospect['flag_company'] == 1) {
                $data['company']++;

                continue;
            }

            if ($prospect['flag_do_not_mail'] == 1) {
                $data['do_not_mail']++;

                continue;
            }

            if ($prospect['flag_duplicate'] == 1) {
                $data['duplicate']++;

                continue;
            }

            if ($prospect['flag_invalid'] == 1) {
                $data['invalid']++;

                continue;
            }

            if ($prospect['flag_nonresidential'] == 1) {
                $data['nonresidential']++;

                continue;
            }

            if ($prospect['flag_suppressed'] == 1) {
                $data['suppressed']++;

                continue;
            }

            $data['available']++;

            $prospect['DealType'] = strtoupper($prospect['DealType']);

            if (in_array($prospect['DealType'], sales_service_model::DMS_DEAL_TYPE_SALE)) {
                $data['sales']++;

                if (!empty($prospect['SoldDate']) && ($data['earliest_sale_date'] == null || $prospect['SoldDate'] < $data['earliest_sale_date'])) {
                    $data['earliest_sale_date'] = $prospect['SoldDate'];
                }

                if (!empty($prospect['SoldDate']) && ($data['latest_sale_date'] == null || $prospect['SoldDate'] > $data['latest_sale_date'])) {
                    $data['latest_sale_date'] = $prospect['SoldDate'];
                }
            } elseif (in_array($prospect['DealType'], sales_service_model::DMS_DEAL_TYPE_LEASE)) {
                $data['leases']++;

                if (!empty($prospect['SoldDate']) && ($data['earliest_lease_date'] == null || $prospect['SoldDate'] < $data['earliest_lease_date'])) {
                    $data['earliest_lease_date'] = $prospect['SoldDate'];
                }

                if (!empty($prospect['SoldDate']) && ($data['latest_lease_date'] == null || $prospect['SoldDate'] > $data['latest_lease_date'])) {
                    $data['latest_lease_date'] = $prospect['SoldDate'];
                }
            } elseif ($prospect['LastServiceDate'] != null && $prospect['LastServiceDate'] != '0000-00-00' && ($prospect['SoldDate'] == null || $prospect['SoldDate'] == '0000-00-00')) {
                $data['services']++;

                if (!empty($prospect['LastServiceDate']) && ($data['earliest_lastservicedate'] == null || $prospect['LastServiceDate'] < $data['earliest_lastservicedate'])) {
                    $data['earliest_lastservicedate'] = $prospect['LastServiceDate'];
                }

                if (!empty($prospect['LastServiceDate']) && ($data['latest_lastservicedate'] == null || $prospect['LastServiceDate'] > $data['latest_lastservicedate'])) {
                    $data['latest_lastservicedate'] = $prospect['LastServiceDate'];
                }
            }
        }

        $sql = 'SELECT (
                           SELECT COUNT(DISTINCT CASE
                                                     WHEN (Cell IS NOT NULL AND Cell != "")
                                                         THEN Cell
                                                     WHEN (Home IS NOT NULL AND Home != "")
                                                         THEN Home
                                                 END)
                             FROM (' . $this->selectionSql($count_id, $sourceId, $part) . ') as embedded
                       ) AS phones,
                       (
                           SELECT COUNT(DISTINCT (Email))
                             FROM (' . $this->selectionSql($count_id, $sourceId, $part) . ') as embedded
                            WHERE Email != ""
                       ) AS emails';

        $contactInfo = $this->common_model->single_result_assoc($sql);

        $data['phones'] += $contactInfo['phones'];
        $data['emails'] += $contactInfo['emails'];

        // This is hacky code to remove the matching, invalid, duplicate, do_not_mail, suppressed, nonresidential, company counts if the part is null
        if ($part === null) {
            unset($data['matching'], $data['invalid'], $data['duplicate'], $data['do_not_mail'], $data['suppressed'], $data['nonresidential'], $data['company']);
        }

        if ($part == null) {
            $this->cacheSet(self::CACHE_KEY['STATISTICS'], $data, $count_id, $sourceId);
        }

        return $data;
    }

    /**
     * @param int $countId
     * @return array
     */
    public function getCountSuppressionStatistics($countId)
    {
        $this->updateCountStatusMessage($countId, 'Get suppression statistics');

        if ($data = $this->cacheGet(self::CACHE_KEY['SUPPRESSIONS_STATISTICS'], $countId)) {
            return $data;
        }

        $data = [];
        $sql  = 'SELECT SourceType, COUNT(*) FROM application_counts_suppressions WHERE CountId = ' . $countId . ' GROUP BY SourceType';

        foreach ($this->common_model->array_result_assoc($sql) as $row) {
            $data[$row['SourceType']] = $row['COUNT(*)'];
        }

        $this->cacheSet(self::CACHE_KEY['SUPPRESSIONS_STATISTICS'], $data, $countId);

        return $data;
    }

    /**
     * Get a summary of suppressions used for a count
     *
     * @param int $countId
     * @return array
     */
    public function getCountSuppressionsSummary($countId)
    {
        $sql = 'SELECT SourceType, SourceId, COUNT(*) AS Total FROM application_counts_suppressions WHERE CountId = ' . $countId . ' GROUP BY SourceType, SourceId';

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @return string
     */
    public function getCountSystemStatus($countId)
    {
        $key = 'counts:status:' . $countId;

        $status = (string)cacheGet($key);

        $status = str_replace('csv', 'CSV', $status);
        $status = str_replace('dms', 'DMS', $status);
        $status = str_replace(['ncoa', 'Ncoa'], 'NCOA', $status);

        return $status;
    }

    /**
     * @return array
     */
    public function getCounts()
    {
        /** @noinspection SqlResolve */
        $sql = 'SELECT application_counts.*,
                       DATE_FORMAT(application_counts.created, "%Y-%m-%d") AS __created, 
                       application_client.Keyid AS client__Keyid,
                       application_client.Name AS client__Name,
                       application_user.Id AS user__Id,
                       application_user.Strid AS user__Strid,                       
                       application_user.Name AS user__Name
                  FROM application_counts
             LEFT JOIN application_client
                       ON application_counts.ClientId = application_client.Id
             LEFT JOIN application_user
                       ON application_counts.UserId = application_user.Id
                 ORDER BY application_counts.Id DESC';

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int $datasetId
     * @return array|false
     */
    public function getDataset($datasetId)
    {
        $sql = "SELECT application_counts_datasets.*,
                       application_client.Name AS client__Name
                  FROM application_counts_datasets
             LEFT JOIN application_client
                       ON application_counts_datasets.ClientId = application_client.Id
                 WHERE application_counts_datasets.Id = '" . $datasetId . "'";

        return $this->common_model->single_result_assoc($sql);
    }

    /**
     * @param int $datasetId
     * @return array
     */
    public function getDatasetProspects($datasetId)
    {
        $sql = 'SELECT * FROM application_counts_datasets_prospects WHERE DatasetId = ' . $datasetId;

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @return array
     */
    public function getDatasetStatistics()
    {
        $data = [
            'total' => [],
        ];

        $sql = 'SELECT DatasetId, COUNT(*) AS total FROM application_counts_datasets_prospects GROUP BY DatasetId';

        foreach ($this->common_model->array_result_assoc($sql) as $row) {
            $data['total'][$row['DatasetId']] = $row['total'];
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getDatasets()
    {
        /** @noinspection SqlResolve */
        $sql = 'SELECT application_counts_datasets.*,
                       DATE_FORMAT(application_counts_datasets.created, "%Y-%m-%d") AS __created,
                       application_client.Id                                        AS client__Id,
                       application_client.Keyid                                     AS client__Keyid,
                       application_client.Name                                      AS client__Name
                  FROM application_counts_datasets
                  LEFT JOIN application_client ON application_counts_datasets.ClientId = application_client.Id
                 ORDER BY application_counts_datasets.Id DESC';

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @return array
     */
    public function getFeeds()
    {
        $sql = 'SELECT application_fclient.Id,
                       application_fclient.Cid,
                       Fcid,
                       DMSType,
                       SalesStart,
                       SalesEnd,
                       ServiceStart,
                       ServiceEnd,
                       application_client.Name AS client__name
                  FROM application_fclient
                  LEFT JOIN application_client
                            ON application_fclient.Cid = application_client.Id';

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        $sql = 'SELECT UserId AS Id,
                       application_user.Name
                  FROM setup_account_executive
                  JOIN application_user
                       ON application_user.Id = setup_account_executive.UserId
                 UNION
                SELECT UserId AS Id,
                       application_user.Name
                  FROM setup_account_manager
                  JOIN application_user
                       ON application_user.Id = setup_account_manager.UserId
                 UNION
                SELECT Id,
                       Name
                  FROM application_user
                 WHERE Id IN (9373, 9374)
                 ORDER BY Name';

        return $this->common_model->array_result_assoc($sql);
    }

    /**
     * @param int $countId
     * @return bool
     */
    public function isCountLocked($countId)
    {
        $sql = 'SELECT Locked FROM application_counts WHERE Id = ' . $this->db->escape($countId);

        return (bool)$this->common_model->single_result_field($sql, 'Locked');
    }

    /**
     * @param int $countId
     * @return bool
     */
    public function lockCount($countId)
    {
        $sql = 'UPDATE application_counts SET Locked = 1 WHERE Id = ' . $this->db->escape($countId);

        return $this->common_model->execute_query($sql);
    }

    /**
     * NCOA process step 5
     *
     * @param int $countId
     * @return true
     * @throws CurlErrorException
     * @throws DatabaseRecordNotFoundException
     */
    public function ncoaDownload($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        if (!$this->existsCount($countId)) {
            throw new DatabaseRecordNotFoundException('Count ' . $countId . ' does not exist');
        }

        $this->ncoaStatus($countId, self::NCOA_STATUS_DOWNLOAD, self::NCOA_STATE_STARTED);

        $count = $this->getCount($countId);
        $url   = 'https://cloud2.iaccutrace.com/ws_360_webapps/download.jsp?guid=' . $count['NcoaGuid'] . '&ftype=csv';
        $file  = $this->filepathCountNcoaOutput($count['Id']);

        $ch = curl_init();

        $fp = fopen($file, 'wb+');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

        curl_exec($ch);

        if (curl_error($ch)) {
            throw new CurlErrorException($ch);
        }

        curl_close($ch);

        fclose($fp);

        $this->ncoaStatus($countId, self::NCOA_STATUS_DOWNLOAD, self::NCOA_STATE_FINISHED);

        return true;
    }

    /**
     * NCOA process step 4
     *
     * @param int $countId
     * @return mixed
     * @throws CurlErrorException
     * @throws DatabaseRecordNotFoundException
     */
    public function ncoaExecute($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        if (!$this->existsCount($countId)) {
            throw new DatabaseRecordNotFoundException('Count ' . $countId . ' does not exist');
        }

        $this->ncoaStatus($countId, self::NCOA_STATUS_EXECUTE, self::NCOA_STATE_STARTED);

        $count = $this->getCount($countId);
        $url   = 'https://cloud2.iaccutrace.com/servoy-service/rest_ws/ws_360/v2_0/job/' . $count['NcoaGuid'] . '/NCOA';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);

        $result = curl_exec($ch);

        // Ignore operation time out errors because we're deliberately doing this asynchronously
        if (curl_error($ch) && curl_errno($ch) != CURLE_OPERATION_TIMEDOUT) {
            throw new CurlErrorException($ch);
        }

        $response_decoded = json_decode($result, true);
        $response_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = [
            'NcoaMessage' => $result,
            'NcoaCode'    => $response_code,
        ];

        $this->ncoaStatus($countId, self::NCOA_STATUS_EXECUTE, self::NCOA_STATE_FINISHED, $data);

        return $response_decoded;
    }

    /**
     * NCOA process step 1
     *
     * @param int $countId
     * @return true
     * @throws DatabaseRecordNotFoundException
     * @throws FileNotWritableException
     */
    public function ncoaExport($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        if (!$this->existsCount($countId)) {
            throw new DatabaseRecordNotFoundException('Count ' . $countId . ' does not exist');
        }

        $this->ncoaStatus($countId, self::NCOA_STATUS_EXPORT, self::NCOA_STATE_STARTED);

        $count = $this->getCount($countId);

        $file = $this->filepathCountNcoaInput($count['Id']);

        if (file_exists($file) && !is_writable($file)) {
            throw new FileNotWritableException('File ' . $file . ' is not writable');
        }

        if (!$fp = fopen($file, 'wb')) {
            throw new FileNotWritableException('Failed to open ' . $file . ' for writing');
        }

        fputcsv($fp, ['Id', 'Name', 'Address', 'City', 'State', 'Zip']);

        $sql = 'SELECT Id, CONCAT(First, " ", Last) as Name, Address, City, State, Zip from application_counts_prospects WHERE CountId = "' . $count['Id'] . '"';

        if ($count['NcoaMode'] == 'TEST') {
            $sql .= ' LIMIT 20';
        }

        $res = $this->common_model->execute_query($sql);

        while ($row = mysql_fetch_assoc($res)) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        if (fileowner($file) === posix_getuid()) {
            chmod($file, 0777);
        }

        $this->ncoaStatus($countId, self::NCOA_STATUS_EXPORT, self::NCOA_STATE_FINISHED);

        return true;
    }

    /**
     * NCOA process step 6
     *
     * @param int $countId
     * @return void
     * @throws DatabaseQueryFailed
     * @throws DatabaseRecordNotFoundException
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function ncoaImport($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        if (!$this->existsCount($countId)) {
            throw new DatabaseRecordNotFoundException('Count ' . $countId . ' does not exist');
        }

        $this->ncoaStatus($countId, self::NCOA_STATUS_IMPORT, self::NCOA_STATE_STARTED);

        // Reset flag_invalid on prospects
        $sql = 'UPDATE application_counts_prospects SET flag_invalid = 0 WHERE CountId = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        // Reset flag_nonresidential on prospects
        $sql = 'UPDATE application_counts_prospects SET flag_nonresidential = 0 WHERE CountId = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        $count = $this->getCount($countId);
        $file  = $this->filepathCountNcoaOutput($count['Id']);

        $headerMap = [];

        if (($handle = fopen($file, 'rb')) !== false) {
            if (($header = fgetcsv($handle)) !== false) {
                foreach ($header as $offset => $columnName) {
                    $headerMap[$columnName] = $offset;
                }
            }

            fclose($handle);
        }

        $fh = fopen($file, 'rb');

        $line_number = 0;

        while (($column = fgetcsv($fh, 0)) !== false) {
            $line_number++;

            if ($line_number == 1) {
                continue;
            }

            $data = [
                'First'               => $column[$headerMap['Name']],
                'Last'                => $column[$headerMap['last']],
                'Address'             => $column[$headerMap['Address']],
                'City'                => $column[$headerMap['City']],
                'State'               => $column[$headerMap['State']],
                'Zip'                 => substr($column[$headerMap['Zip']], 0, 5),
                'flag_invalid'        => $column[$headerMap['status_']] == 'V' ? 0 : 1,
                'flag_nonresidential' => $column[$headerMap['rdi_']] != 'Y' ? 1 : 0,
                'AddressValid'        => $column[$headerMap['status_']] == 'V' ? 1 : 0,
                'LastNcoa'            => date('Y-m-d H:i:s'),
            ];

            if ($column[0] != '') {
                $this->common_model->mysql_update('application_counts_prospects', $data, $column[0]);
            }
        }

        fclose($fh);

        $this->excludeCountProspectsCompanies($count['Id']);
        $this->excludeCountProspectsDuplicates($count['Id']);
        $this->excludeCountProspectsDoNotMail($count['Id']);
        $this->excludeCountProspectsSuppressed($count['Id']);
        $this->updateCountProspectsDistance($count['Id']);
        $this->updateCountProspectsSelected($count['Id']);

        $this->ncoaStatus($count['Id'], self::NCOA_STATUS_IMPORT, self::NCOA_STATE_FINISHED);

        $this->cacheFlushPublic($count['Id']);

        $this->notifyReady($count['Id']);
    }

    /**
     * NCOA process step 3
     *
     * @param int $countId
     * @return mixed
     * @throws CurlErrorException
     * @throws DatabaseRecordNotFoundException
     */
    public function ncoaQuote($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        if (!$this->existsCount($countId)) {
            throw new DatabaseRecordNotFoundException('Count ' . $countId . ' does not exist');
        }

        $this->ncoaStatus($countId, self::NCOA_STATUS_QUOTE, self::NCOA_STATE_STARTED);

        $count = $this->getCount($countId);
        $url   = 'https://cloud2.iaccutrace.com/servoy-service/rest_ws/ws_360/job/' . $count['NcoaGuid'] . '/QUOTE';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['additional_output_fields' => 'dpv_;dpvnotes_;status_;type_;dfl_;matchflag_;nxi_;ank_;rdi_;movedate_']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if (curl_error($ch)) {
            throw new CurlErrorException($ch);
        }

        $response_decoded = json_decode($result, true);
        $response_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = [
            'NcoaMessage' => $result,
            'NcoaCode'    => $response_code,
        ];

        $this->ncoaStatus($countId, self::NCOA_STATUS_QUOTE, self::NCOA_STATE_FINISHED, $data);

        return $response_decoded;
    }

    /**
     * NCOA process step 2
     *
     * @param int  $countId
     * @param bool $useBackupKey
     * @return mixed
     * @throws CurlErrorException
     * @throws DatabaseRecordNotFoundException
     */
    public function ncoaUpload($countId, $useBackupKey = false)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        if (!$this->existsCount($countId)) {
            throw new DatabaseRecordNotFoundException('Count ' . $countId . ' does not exist');
        }

        $this->ncoaStatus($countId, self::NCOA_STATUS_UPLOAD, self::NCOA_STATE_STARTED);

        $count = $this->getCount($countId);
        $file  = $this->filepathCountNcoaInput($count['Id']);
        $url   = 'https://cloud2.iaccutrace.com/ws_360_webapps/uploadProcess.jsp?manual_submit=false';

        $data = [
            'backOfficeOption' => 'json',
            'apiKey'           => ACCUTRACE_PRIMARY_KEY,
            'callbackURL'      => 'https://webhook.site/54aa5b8b-ae57-42ff-bf65-3b37d9310353',
            'guid'             => '',
        ];

        if ($useBackupKey) {
            $data['apiKey'] = ACCUTRACE_BACKUP_KEY;
        }

        $files = [
            'file' => $file,
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // invalid characters for "name" and "filename"
        static $disallow = ["\0", '"', "\r", "\n"];

        $body = [];

        // build normal parameters
        foreach ($data as $k => $v) {
            $k      = str_replace($disallow, '_', $k);
            $body[] = implode("\r\n", [
                'Content-Disposition: form-data; name="' . $k . '"',
                '',
                filter_var($v),
            ]);
        }

        // build file parameters
        foreach ($files as $k => $v) {
            $v    = realpath(filter_var($v));
            $data = file_get_contents($v);
            /** @noinspection VariableFunctionsUsageInspection */
            $v      = call_user_func('end', explode(DIRECTORY_SEPARATOR, $v));
            $k      = str_replace($disallow, '_', $k);
            $v      = str_replace($disallow, '_', $v);
            $body[] = implode("\r\n", [
                'Content-Disposition: form-data; name="' . $k . '"; filename="' . $v . '"',
                'Content-Type: application/octet-stream',
                '',
                $data,
            ]);
        }

        // generate safe boundary
        do {
            $boundary = '---------------------' . md5(mt_rand() . microtime());
        } while (preg_grep('/' . $boundary . '/', $body));

        // add boundary for each parameters
        array_walk($body, static function (&$part) use ($boundary) {
            $part = '--' . $boundary . "\r\n" . $part;
        });

        // add final boundary
        $body[] = '--' . $boundary . '--';
        $body[] = '';

        // set options
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => implode("\r\n", $body),
            CURLOPT_HTTPHEADER => [
                'Expect: 100-continue',
                'Content-Type: multipart/form-data; boundary=' . $boundary, // change Content-Type
            ],
        ]);

        $result = curl_exec($ch);

        if (curl_error($ch)) {
            throw new CurlErrorException($ch);
        }

        $response_decoded = json_decode($result, true);
        $response_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (!$useBackupKey && isset($response_decoded['message']) && $response_decoded['message'] == 'ERROR Over Month Limit!') {
            return $this->ncoaUpload($countId, true);
        }

        $data = [
            'NcoaGuid'    => $response_decoded['guid'],
            'NcoaMessage' => $result,
            'NcoaCode'    => $response_code,
        ];

        $this->ncoaStatus($countId, self::NCOA_STATUS_UPLOAD, self::NCOA_STATE_FINISHED, $data);

        return $response_decoded;
    }

    /**
     * @param int $countId
     * @return void
     */
    public function notifyExportRequested($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->getCount($countId);

        // Draft subject
        $subject = 'Count export requested for ' . $count['client__Name'] . ' by ' . explode(' ', $count['user__Name'])[0];

        // Draft link
        $linkToCount = urlController(counts::class, 'view', $count['Id']);

        // Draft body
        $body   = [];
        $body[] = '<p>' . explode(' ', $count['user__Name'])[0] . ' has requested an export for ' . $count['client__Name'] . '</p>';
        $body[] = !empty($count['SpecialInstructions']) ? '<p><b>Special Instructions</b>: ' . nl2br($count['SpecialInstructions']) . '</p>' : '';
        $body[] = '<p>' . $linkToCount . '</p>';
        $body   = implode("\n", $body);

        // Email admin
        $mail = new Email(self::ADMIN_EMAIL, $subject, $body);
        $mail->send();

        // Prepare Slack buttons
        $buttons = [
            ['View Count', $linkToCount],
        ];

        // Slack channel
        $slackbot = new Slack();
        $slackbot->sendToChannel('application-production', $subject, $buttons);
    }

    /**
     * @param int $countId
     * @return void
     */
    public function notifyNcoaRequested($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->getCount($countId);

        // Get the available selected records
        $sql       = 'SELECT COUNT(*) AS count FROM application_counts_prospects WHERE CountId = ' . $countId . ' AND flag_selected = 1';
        $available = $this->common_model->single_result_field($sql, 'count');

        // Draft subject
        $subject = 'Count requested for ' . $count['client__Name'] . ' by ' . explode(' ', $count['user__Name'])[0];

        // Draft link
        $linkToCount = urlController(counts::class, 'view', $count['Id']);

        // Draft body
        $body   = [];
        $body[] = '<p>' . explode(' ', $count['user__Name'])[0] . ' requested a count for ' . $count['client__Name'] . '</p>';
        $body[] = '<p>' . number_format($available) . ' records are ready for NCOA</p>';
        $body[] = !empty($count['SpecialInstructions']) ? '<p><b>Special Instructions</b>: ' . nl2br($count['SpecialInstructions']) . '</p>' : '';
        $body[] = '<p>' . $linkToCount . '</p>';
        $body   = implode("\n", $body);

        // Email admin
        $mail = new Email(self::ADMIN_EMAIL, $subject, $body);
        $mail->send();

        // Prepare Slack buttons
        $buttons = [
            ['View Count', $linkToCount],
        ];

        // Slack channel
        $slackbot = new Slack();
        $slackbot->sendToChannel('application-production', $subject, $buttons);
    }

    /**
     * @param int $countId
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @noinspection HtmlDeprecatedAttribute
     */
    public function notifyReady($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $count = $this->getCount($countId);

        $data = [
            'parts' => $this->counts_model->getCountParts($countId),
        ];

        foreach ($data['parts'] as $part) {
            $data['statistics_parts'][$part['SourceId']] = $this->counts_model->getCountStatistics($countId, $part['SourceId']);
        }

        // Generate the Excel report
        $attachmentReport     = $this->generateCountExcelReport($countId);
        $attachmentReportName = $this->filenameCountReport($countId);

        // Generate the suppression
        $attachmentSuppression     = $this->generateCountSuppressionsAllCsv($countId);
        $attachmentSuppressionName = 'Suppression ' . $countId . ' - ' . ($count['client__Name'] ?: $count['Zip']) . ' - ' . date('Y-m-d') . ' - All.csv';

        // Draft subject
        $requestor    = $this->client_user_model->getUser($count['UserId']);
        $subject      = 'Count complete for ' . $count['client__Name'];
        $linkToCount  = urlController(counts::class, 'view', $count['Id']);
        $linkToReport = urlController(counts::class, 'export_report', $count['Id']);

        $body = $this->generateEmailBody($countId);

        // Prepare Slack buttons
        $buttons = [
            ['View Count', $linkToCount],
            ['Export Report', $linkToReport],
        ];

        // Slack requestor and channel
        $slackbot = new Slack();
        $slackbot->sendToUserByEmail($requestor['Email'], $subject, $buttons);
        $slackbot->sendToChannel('application-production', $subject, $buttons);

        // Email requestor
        $mail          = new Email();
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->addAttachment($attachmentReport, $attachmentReportName);
        $mail->addAttachment($attachmentSuppression, $attachmentSuppressionName);
        $mail->addAddress($requestor['Email']);
        $mail->addAddress(self::ADMIN_EMAIL);

        if (!empty($count['UserIdsCopied'])) {
            $userIds = explode(',', $count['UserIdsCopied']);

            foreach ($userIds as $userId) {
                $sql  = 'SELECT Name, Email FROM application_user WHERE Id = ' . $userId;
                $user = $this->common_model->single_result_assoc($sql);

                $mail->addAddress($user['Email']);
            }
        }

        $mail->send();
    }

    /**
     * Delete a count and its parts. Because deleting lots of records is slow in InnoDB,
     * prospects and suppressions are not deleted here and are instead just orphaned.
     * A counts_janitor task exists to clean up orphaned records.
     *
     * @param int $countId
     * @return true
     */
    public function removeCount($countId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $sql = 'DELETE FROM application_counts WHERE Id = ' . $this->db->escape($countId);
        $this->common_model->execute_query($sql);

        $sql = 'DELETE FROM application_counts_parts WHERE CountId = ' . $this->db->escape($countId);
        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return true
     */
    public function removeCountSuppressionsFromFiles($countId)
    {
        $this->updateCountStatusMessage($countId, 'Remove suppressions from files');

        $sql = 'DELETE FROM application_counts_suppressions WHERE CountId = "' . $countId . '" AND `SourceType` = "' . self::SOURCE_TYPE_FILE . '"';
        $this->common_model->execute_query($sql);

        $this->cacheFlush($countId);

        return true;
    }

    /**
     * @param int $datasetId
     * @return true
     */
    public function removeDataset($datasetId)
    {
        $sql = 'DELETE FROM application_counts_datasets WHERE Id = ' . $this->db->escape($datasetId);
        $this->common_model->execute_query($sql);

        $sql = 'DELETE FROM application_counts_datasets_prospects WHERE DatasetId = ' . $this->db->escape($datasetId);
        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return bool
     */
    public function unlockCount($countId)
    {
        $sql = 'UPDATE application_counts SET Locked = 0 WHERE Id = ' . $this->db->escape($countId);

        return $this->common_model->execute_query($sql);
    }

    /**
     * @param int   $countId
     * @param array $input
     * @return true
     * @throws DatabaseQueryFailed
     * @throws FileNotReadableException
     * @throws UnexpectedResultException
     */
    public function updateCount($countId, array $input = null)
    {
        $this->updateCountStatusMessage($countId, 'Update count');

        $input['_include_sets']            = isset($input['_include_sets']) && is_array($input['_include_sets']) ? $input['_include_sets'] : [];
        $input['_part_filter']             = isset($input['_part_filter']) && is_array($input['_part_filter']) ? $input['_part_filter'] : [];
        $input['_suppress_jobs']           = isset($input['_suppress_jobs']) && is_array($input['_suppress_jobs']) ? $input['_suppress_jobs'] : [];
        $input['_suppress_responses']      = isset($input['_suppress_responses']) && is_array($input['_suppress_responses']) ? $input['_suppress_responses'] : [];
        $input['_suppress_counts']         = isset($input['_suppress_counts']) && is_array($input['_suppress_counts']) ? $input['_suppress_counts'] : [];
        $input['_suppress_file']           = !empty($input['_suppress_file']) ? $input['_suppress_file'] : [];
        $input['_suppress_file_operation'] = isset($input['_suppress_file_operation']) ? $input['_suppress_file_operation'] : '';

        $data = [
            'UseDms'            => $input['UseDms'],
            'Modified'          => date('Y-m-d H:i:s'),
            'SuppressJobs'      => json_encode($input['_suppress_jobs']),
            'SuppressResponses' => json_encode($input['_suppress_responses']),
            'SuppressCounts'    => json_encode($input['_suppress_counts']),
        ];

        if (isset($input['DmsTable']) && $input['DmsTable'] == 'processed') {
            $this->dmsTableSuffix = '_processed';
        }

        $this->common_model->mysql_update('application_counts', $data, $countId);

        // Get the count from the database
        $count = $this->getCount($countId);

        $partsBefore        = $this->getCountParts($countId);
        $partsBeforeIds     = array_column($partsBefore, 'Id');
        $suppressionsBefore = $this->getCountSuppressionsSummary($countId);

        // Create or remove prospects
        $this->createOrRemoveCountProspectsFromDms($countId);
        $this->createOrRemoveCountProspectsFromDatasets($countId, $input['_include_sets']);

        // Create or remove suppressions
        $this->createOrRemoveCountSuppressionsFromJobs($countId, $input['_suppress_jobs']);
        $this->createOrRemoveCountSuppressionsFromResponses($countId, $input['_suppress_responses']);
        $this->createOrRemoveCountSuppressionsFromCounts($countId, $input['_suppress_counts']);

        if ($input['_suppress_file_operation'] == 'remove') {
            $this->removeCountSuppressionsFromFiles($countId);
        } elseif ($input['_suppress_file']) {
            $this->createCountSuppressionsFromFile($countId, $input['_suppress_file']);
        }

        // Save filters to database
        $this->updateCountPartsFilters($countId, $input['_part_filter']);

        $partsAfter        = $this->getCountParts($countId);
        $partsAfterIds     = array_column($partsAfter, 'Id');
        $suppressionsAfter = $this->getCountSuppressionsSummary($countId);

        // If parts have been added
        if (array_diff($partsAfterIds, $partsBeforeIds)) {
            $this->debug('Info: Parts have been added');

            $this->excludeCountProspectsCompanies($count['Id']);
            $this->excludeCountProspectsDoNotMail($count['Id']);
            $this->updateCountProspectsDistance($count['Id']);
        }

        // If parts have been added or removed
        if ($partsBeforeIds !== $partsAfterIds) {
            $this->debug('Info: Parts have been added or removed');

            $this->excludeCountProspectsDuplicates($count['Id']);
        }

        // If parts have been added or removed or if suppressions have been added or removed
        if ($partsBeforeIds !== $partsAfterIds || $suppressionsBefore !== $suppressionsAfter) {
            $this->debug('Info: Parts or suppressions have been added or removed');

            $this->excludeCountProspectsSuppressed($count['Id']);
        }

        // Update selections
        $this->updateCountProspectsSelected($count['Id']);

        // Update count totals
        $this->updateCountTotals($count['Id']);

        return true;
    }

    /**
     * @param int    $countId
     * @param string $mode
     * @return bool|mixed|resource
     */
    public function updateCountMode($countId, $mode)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $sql = "UPDATE application_counts SET NcoaMode = '" . $mode . "' WHERE Id = '" . $countId . "'";

        return $this->common_model->execute_query($sql);
    }

    /**
     * @param int $countId
     * @return true
     */
    public function updateCountProspectsDistance($countId)
    {
        // Fetch the count from the database
        $count = $this->getCount($countId);

        $this->updateCountStatusMessage($countId, 'Calculating record distance from client');

        // Check the database for existing zip code distance measurements for the target zip code
        $sql   = "SELECT COUNT(*) AS count FROM application_counts_zips WHERE source = '" . $count['Zip'] . "'";
        $found = $this->common_model->single_result_field($sql, 'count');

        // If none were found, use this remote service to collect measurements and store them in our database
        if ($found == 0) {
            $url = 'https://redline-redline-zipcode.p.rapidapi.com/rest/radius.json/' . $count['Zip'] . '/100/mile';

            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'x-rapidapi-key: srS9xGvqyQmshe1Huw53qXrxwccXp1N890VjsnEnf10OOeBUJb',
                ],
            ];

            $measured_zips = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);

            foreach ($measured_zips['zip_codes'] as $zip_codes) {
                $data             = [];
                $data['source']   = $count['Zip'];
                $data['target']   = $zip_codes['zip_code'];
                $data['distance'] = $zip_codes['distance'];
                $data['city']     = $zip_codes['city'];
                $data['state']    = $zip_codes['state'];

                $this->common_model->mysql_insert('application_counts_zips', $data);
            }
        }

        // Reset distance on prospects
        $sql = 'UPDATE application_counts_prospects SET Distance = NULL WHERE CountId = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        // Update distance on prospects
        $sql = "UPDATE application_counts_prospects
            INNER JOIN application_counts_zips
                    ON LEFT(application_counts_prospects.Zip, 5) = application_counts_zips.target
                   SET application_counts_prospects.Distance = application_counts_zips.distance
                 WHERE application_counts_prospects.CountId = '" . $countId . "'
                   AND application_counts_zips.source = '" . $count['Zip'] . "'
            ";

        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return bool|mixed|resource
     */
    public function updateCountSelectionsToDefault($countId)
    {
        $options = [];

        $numberOfParts = count($this->getCountParts($countId));

        for ($i = 0; $i < $numberOfParts; $i++) {
            $options[] = [
                'method' => 'all',
            ];
        }

        $sql = 'UPDATE application_counts SET ExportedOptions = "' . mysql_escape_string(json_encode($options)) . '" WHERE Id = "' . $countId . '"';

        return $this->common_model->execute_query($sql);
    }

    /**
     * @param int    $countId
     * @param string $instructions
     * @return void
     */
    public function updateCountSpecialInstructions($countId, $instructions)
    {
        $data = [
            'SpecialInstructions' => $instructions,
        ];

        $this->common_model->mysql_update('application_counts', $data, $countId);
    }

    /**
     * @param int    $countId
     * @param string $status
     * @return bool|mixed|resource
     */
    public function updateCountStatus($countId, $status)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        if ($status == self::COUNT_STATUS_PENDING) {
            $sql = "UPDATE application_counts SET Status = '" . $status . "', NcoaStatus = NULL, NcoaState = NULL WHERE Id = '" . $countId . "'";
        } else {
            $sql = "UPDATE application_counts SET Status = '" . $status . "' WHERE Id = '" . $countId . "'";
        }

        return $this->common_model->execute_query($sql);
    }

    /**
     * @param string $message
     * @param int    $countId
     * @param bool   $includeInDebug
     * @return true
     */
    public function updateCountStatusMessage($countId, $message, $includeInDebug = true)
    {
        if ($includeInDebug) {
            $this->debug('Operation: ' . $message);
        }

        $key = 'counts:status:' . $countId;

        $sentence = preg_replace('/([a-z])([A-Z])/', '$1 $2', $message);
        $sentence = ucfirst(strtolower($sentence));

        cacheSet($key, $sentence, 300);

        return true;
    }

    /**
     * @param int   $countId
     * @param array $userIds
     * @return void
     */
    public function updateCountUserIdsCopied($countId, $userIds)
    {
        if (empty($userIds)) {
            return;
        }

        $userIdsCsv = implode(',', $userIds);

        $data = [
            'UserIdsCopied' => $userIdsCsv,
        ];

        $this->common_model->mysql_update('application_counts', $data, $countId);
    }

    /**
     * Flush a cache key for a count part
     *
     * @param string   $key
     * @param int      $countId
     * @param int|null $sourceId
     * @return bool
     */
    private function cacheDelete($key, $countId, $sourceId = null)
    {
        // Generate the cache key
        $redisKey = $key . ':' . $countId . ':' . ($sourceId === null ? 'COUNT' : $sourceId);

        // Debug
        $this->debug('Cache delete: ' . $redisKey);

        return cacheDelete($redisKey);
    }

    /**
     * Flush all cache keys for a count part
     *
     * @param int      $countId
     * @param int|null $sourceId
     * @return true
     */
    private function cacheFlush($countId, $sourceId = null)
    {
        $this->debug('Cache flush count ' . $countId . ' part ' . ($sourceId === null ? 'NULL' : $sourceId));

        foreach (self::CACHE_KEY as $key) {
            $this->cacheDelete($key, $countId, $sourceId);
        }

        return true;
    }

    /**
     * Get a cache key for a count part
     *
     * @param string   $key
     * @param int      $countId
     * @param int|null $sourceId
     * @return array|false
     */
    private function cacheGet($key, $countId, $sourceId = null)
    {
        // Return false if the cache is disabled
        if (!$this->cacheEnabled) {
            return false;
        }

        // Return false if teh count is locked
        if ($this->isCountLocked($countId)) {
            return false;
        }

        // Generate the cache key
        $redisKey = $key . ':' . $countId . ':' . ($sourceId === null ? 'COUNT' : $sourceId);

        // Get the value from the cache
        $value = cacheGetJson($redisKey);

        // Debug
        if ($value === false) {
            $this->debug('Cache miss: ' . $redisKey);
        } else {
            $this->debug('Cache hit: ' . $redisKey);
        }

        return $value;
    }

    /**
     * Store a cache key for a count part
     *
     * @param string   $key
     * @param array    $data
     * @param int      $countId
     * @param int|null $sourceId
     * @return bool
     */
    private function cacheSet($key, $data, $countId, $sourceId = null)
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        if ($this->isCountLocked($countId)) {
            return false;
        }

        // Generate the cache key
        $redisKey = $key . ':' . $countId . ':' . ($sourceId === null ? 'COUNT' : $sourceId);

        // Debug
        $this->debug('Cache set: ' . $redisKey);

        return cacheSetJson($redisKey, $data, 60 * 60 * 24 * 14);
    }

    /**
     * @param int    $countId
     * @param string $sourceType
     * @param int    $sourceId
     * @return void
     * @throws UnexpectedResultException
     */
    private function createCountPart($countId, $sourceType, $sourceId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $data = [
            'CountId'    => $countId,
            'SourceType' => $sourceType,
            'SourceId'   => $sourceId,
        ];

        $this->common_model->mysql_insert('application_counts_parts', $data);

        if (($sourceType == self::SOURCE_TYPE_DMS) && !$this->createCountProspectsFromDms($countId)) {
            throw new UnexpectedResultException('Could not create prospects from DMS Data');
        }

        if (($sourceType == self::SOURCE_TYPE_SET) && !$this->createCountProspectsFromDataset($countId, $sourceId)) {
            throw new UnexpectedResultException('Could not create prospects from SET Data');
        }

        $this->cacheFlush($countId);
        $this->cacheFlush($countId, $sourceId);
    }

    /**
     * @param int $countId
     * @param int $sourceId
     * @return true
     */
    private function createCountProspectsFromDataset($countId, $sourceId)
    {
        $dataset = $this->getDataset($sourceId);

        $this->updateCountStatusMessage($countId, 'Including records from dataset ' . $dataset['Name']);

        $sql = 'INSERT INTO application_counts_prospects (
                    CountId,
                    SourceType,
                    SourceId,
                    CustomerNumber,
                    DealNumber,
                    First,
                    Last,
                    Address,
                    City,
                    State,
                    Zip,
                    Email,
                    Home,
                    Cell,
                    Year,
                    Make,
                    Model,
                    Vin,
                    VehicleType,
                    DealType,
                    Term,
                    LeaseExpiring,
                    SoldDate,
                    LastServiceDate,
                    DataType,
                    AddressValid,
                    LastNCOA
                 )
                SELECT "' . $countId . '" AS CountId,
                       "' . self::SOURCE_TYPE_SET . '" AS SourceType,
                       "' . $sourceId . '" AS SourceId,
                       TRIM(CustomerNumber),
                       TRIM(DealNumber),
                       TRIM(First),
                       TRIM(Last),
                       TRIM(Address),
                       TRIM(City),
                       TRIM(State),
                       LEFT(LPAD(TRIM(Zip), 5, "0"), 5) AS Zip,
                       TRIM(Email),
                       TRIM(Home),
                       TRIM(Cell),
                       TRIM(Year),
                       TRIM(REPLACE(REPLACE(Make, "\\"", ""), "\'", "")) AS Make,
                       TRIM(REPLACE(REPLACE(Model, "\\"", ""), "\'", "")) AS Model,
                       TRIM(Vin),
                       TRIM(VehicleType),
                       TRIM(DealType),
                       TRIM(Term),
                       TRIM(LeaseExpiring),
                       TRIM(SoldDate),
                       TRIM(LastServiceDate),
                       "' . $dataset['Type'] . '" AS DataType,
                       AddressValid,
                       LastNCOA
                  FROM application_counts_datasets_prospects
                 WHERE DatasetId = "' . $sourceId . '"
                   AND First != ""
                   AND Last != ""
        ';

        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return true
     * @noinspection InArrayMissUseInspection
     * @noinspection PhpInArrayCanBeReplacedWithComparisonInspection
     */
    private function createCountProspectsFromDms($countId)
    {
        $this->updateCountStatusMessage($countId, 'Searching DMS for records');

        $count = $this->getCount($countId);

        $clientId = $count['ClientId'];

        // @TODO: There are some anomoalies in the DMS feed data tables application_client_sales and application_client_service.
        //        These generally relate to grouped clients (dealerships) that share feed data in one sense, and not in another.
        //        I (Aaron) don't fully understand it, JD understands it better.
        //        As a quick fix, we are rewriting the requested client_id values to the target client_id for the DMS feed data.
        //        In the future, this could be better done with an array of aliases, or this could be expressed in the database somehow.
        //        This is a temporary fix, and should be revisited.
        //        Note well that the $client_id variable is being changed here.

        // Braman BMW of Miami  (2108) -> Braman Motors (2056)
        // Braman Mini of Miami (2092) -> Braman Motors (2056)
        if (in_array($clientId, [2108, 2092])) {
            $clientId = 2056;
        }

        // Braman Genesis (2675) -> Braman Miami Hyundai (272)
        if (in_array($clientId, [2675])) {
            $clientId = 272;
        }

        // Abeloff Kia (2488) -> Abeloff GMC (2124)
        if (in_array($clientId, [2488])) {
            $clientId = 2124;
        }

        // Bentley Washington D.C. (4021) -> Aston Martin Washington D.C. (4239)
        if (in_array($clientId, [4021])) {
            $clientId = 4239;
        }

        $selectSql = "
                SELECT '" . $countId . "' AS CountId,
                       '" . self::SOURCE_TYPE_DMS . "' AS SourceType,
                       '0' AS SourceId,
                       CustomerNumber,
                       DealNumber,
                       First,
                       Last,
                       Address,
                       City,
                       State,
                       LEFT(Zip, 5) AS Zip,
                       Email,
                       IF(CHAR_LENGTH(Home) > 9, Home, NULL) AS Home,
                       IF(CHAR_LENGTH(Cell) > 9, Cell, NULL) AS Cell,
                       Year,
                       REPLACE(REPLACE(Make, '\'', ''), '\"', '') AS Make,
                       REPLACE(REPLACE(Model, '\'', ''), '\"', '') AS Model,
                       VIN,
                       VehicleType,
                       DealType,
                       Term,
                       LeaseExpiring,
                       SoldDate,
                       LastServiceDate,
                       'DMS' AS DataType,
                       AddressValid,
                       LastNCOA
                  FROM (
                       SELECT *
                         FROM (
                              SELECT CustomerNumber,
                                     RecID,
                                     DealNumber,
                                     First,
                                     Last,
                                     Address,
                                     City,
                                     State,
                                     LEFT(Zip, 5) AS Zip,
                                     Email,
                                     Home,
                                     Cell,
                                     Year,
                                     Make,
                                     Model,
                                     VIN,
                                     VehicleType,
                                     DealType,
                                     Term,
                                     MAX(LeaseExpiring) AS LeaseExpiring,
                                     MAX(SoldDate) AS SoldDate,
                                     MAX(LastServiceDate) AS LastServiceDate,
                                     AddressValid,
                                     LastNCOA
                                FROM (
                                     SELECT CASE WHEN CustomerNumber REGEXP '^[0-9]+$' THEN CAST(CustomerNumber AS UNSIGNED) ELSE CustomerNumber END AS CustomerNumber,
                                            CONCAT(TRIM(UPPER(VehicleVIN)), '_', TRIM(UPPER(CASE WHEN CustomerNumber REGEXP '^[0-9]+$' THEN CAST(CustomerNumber AS UNSIGNED) ELSE CustomerNumber END))) AS RecID,
                                            TRIM(DealNumber) AS DealNumber,
                                            TRIM(UPPER(CustomerFirstName)) AS First,
                                            TRIM(UPPER(CustomerLastName)) AS Last,
                                            TRIM(UPPER(CustomerAddress)) AS Address,
                                            TRIM(UPPER(CustomerCity)) AS City,
                                            TRIM(UPPER(CustomerState)) AS State,
                                            LEFT(TRIM(CustomerZip), 5) AS Zip,
                                            TRIM(UPPER(CustomerEmail)) AS Email,
                                            TRIM(CustomerHomePhone) AS Home,
                                            TRIM(CustomerCellPhone) AS Cell,
                                            TRIM(DealBookDate) AS SoldDate,
                                            NULL AS LastServiceDate,
                                            TRIM(VehicleYear) AS Year,
                                            TRIM(UPPER(VehicleMake)) AS Make,
                                            TRIM(UPPER(VehicleModel)) AS Model,
                                            TRIM(UPPER(VehicleVIN)) AS VIN,
                                            TRIM(UPPER(VehicleType)) AS VehicleType,
                                            TRIM(UPPER(
                                                CASE 
                                                    WHEN (SaleType LIKE '%cash%' OR SaleType = 'cash' OR SaleType = 'c')
                                                         OR (Term = '1' AND DealType NOT IN ('L', 'Lease'))
                                                         OR (DealType LIKE '%cash%' OR DealType = 'C') THEN 'CASH'
                                                    ELSE DealType
                                                END
                                            )) AS DealType,
                                            TRIM(Term) AS Term,
                                            CASE
                                                WHEN UPPER(DealType) IN " . $this->sqlInArray(sales_service_model::DMS_DEAL_TYPE_LEASE) . ' THEN DATE_ADD(DealBookDate, INTERVAL Term MONTH)
                                            END AS LeaseExpiring,
                                            ' . ($this->dmsTableSuffix == '_processed' ? 'AddressValid' : 'NULL') . ' AS AddressValid,
                                            ' . ($this->dmsTableSuffix == '_processed' ? 'LastNCOA' : 'NULL') . ' AS LastNCOA
                                       FROM application_client_sales' . $this->dmsTableSuffix . "
                                      WHERE cid = '" . $clientId . "'
                                        AND CustomerFirstName != ''
                                        AND CustomerLastName != ''
                                        AND VehicleType NOT IN " . $this->sqlInArray(sales_service_model::DMS_VEHICLE_TYPE_WHOLESALE) . "
                                        AND DealBookDate IS NOT NULL
                                        AND VehicleYear > 1900
                                        AND VehicleYear < '" . (date('Y') + 1) . "'
                                        " . ($this->dmsTableSuffix == '_processed' ? 'AND AddressValid = 1' : '') . "
                                      UNION ALL
                                     SELECT CASE WHEN CustomerNumber REGEXP '^[0-9]+$' THEN CAST(CustomerNumber AS UNSIGNED) ELSE CustomerNumber END AS CustomerNumber,
                                            CONCAT(TRIM(UPPER(VehicleVIN)), '_', TRIM(UPPER(CASE WHEN CustomerNumber REGEXP '^[0-9]+$' THEN CAST(CustomerNumber AS UNSIGNED) ELSE CustomerNumber END))) AS RecID,
                                            TRIM(RONumber) AS DealNumber,
                                            TRIM(UPPER(CustomerFirstName)) AS First,
                                            TRIM(UPPER(CustomerLastName)) AS Last,
                                            TRIM(UPPER(CustomerAddress)) AS Address,
                                            TRIM(UPPER(CustomerCity)) AS City,
                                            TRIM(UPPER(CustomerState)) AS State,
                                            LEFT(TRIM(CustomerZip), 5) AS Zip,
                                            TRIM(UPPER(CustomerEmail)) AS Email,
                                            TRIM(CustomerHomePhone) AS Home,
                                            TRIM(CustomerCellPhone) AS Cell,
                                            NULL AS SoldDate,
                                            TRIM(ClosedDate) AS LastServiceDate,
                                            TRIM(VehicleYear) AS Year,
                                            TRIM(UPPER(VehicleMake)) AS Make,
                                            TRIM(UPPER(VehicleModel)) AS Model,
                                            TRIM(UPPER(VehicleVIN)) AS VIN,
                                            NULL AS VehicleType,
                                            NULL AS DealType,
                                            NULL AS Term,
                                            NULL AS LeaseExpiring,
                                            " . ($this->dmsTableSuffix == '_processed' ? 'AddressValid' : 'NULL') . ' AS AddressValid,
                                            ' . ($this->dmsTableSuffix == '_processed' ? 'LastNCOA' : 'NULL') . ' AS LastNCOA
                                       FROM application_client_service' . $this->dmsTableSuffix . "
                                      WHERE cid = '" . $clientId . "'
                                        AND CustomerFirstName != ''
                                        AND CustomerLastName != ''
                                        AND ClosedDate IS NOT NULL
                                        AND VehicleYear > 1900
                                        AND VehicleYear <= '" . (date('Y') + 1) . "'
                                        
                                        " . ($this->dmsTableSuffix == '_processed' ? 'AND AddressValid = 1' : '') . '
                                      ORDER BY LastServiceDate
                                     ) AS combined
                               GROUP BY RecID
                               ORDER BY MAX(SoldDate) DESC,
                                        MAX(LastServiceDate) DESC
                              ) grouped_by_recid
                        GROUP BY grouped_by_recid.VIN
                        ORDER BY grouped_by_recid.SoldDate DESC,
                                 grouped_by_recid.LastServiceDate DESC
                       ) grouped_by_vin
                 GROUP BY CustomerNumber
                 ORDER BY MAX(SoldDate) DESC,
                          MAX(LastServiceDate) DESC
                ';

        $res   = $this->common_model->execute_query($selectSql);
        $count = $this->common_model->num_rows_from_resource($res);

        $i = 0;

        while ($row = mysql_fetch_assoc($res)) {
            $i++;

            $this->updateCountStatusMessage($countId, 'Including ' . number_format($count) . ' DMS records ' . number_format($i / $count * 100) . '%', false);

            $this->common_model->mysql_insert_null_safe('application_counts_prospects', $row);
        }

        return true;
    }

    /**
     * @param int   $countId
     * @param array $input
     * @return void
     * @throws UnexpectedResultException
     */
    private function createOrRemoveCountProspectsFromDatasets($countId, $input)
    {
        $this->updateCountStatusMessage($countId, 'Apply prospects from datasets');

        // Get the count from the database
        $count = $this->getCount($countId);

        // Import Set records as necessary
        foreach ($input as $source_id) {
            if (!$this->getCountPartForDataset($count['Id'], $source_id)) {
                $this->createCountPart($count['Id'], self::SOURCE_TYPE_SET, $source_id);
            }
        }

        // Remove Set records as necessary
        foreach ($this->getCountParts($count['Id']) as $part) {
            // Ignore any parts that aren't SET
            if ($part['SourceType'] != self::SOURCE_TYPE_SET) {
                continue;
            }

            if (!in_array($part['SourceId'], $input)) {
                $this->removeCountPart($count['Id'], self::SOURCE_TYPE_SET, $part['SourceId']);
            }
        }
    }

    /**
     * @param int $countId
     * @return true
     * @throws UnexpectedResultException
     */
    private function createOrRemoveCountProspectsFromDms($countId)
    {
        $this->updateCountStatusMessage($countId, 'Apply prospects from DMS');

        // Get the count from the database
        $count = $this->getCount($countId);

        // Import DMS records if necessary
        if ($count['UseDMS'] == 1 && !$this->getCountPartForDms($count['Id'])) {
            $this->createCountPart($countId, self::SOURCE_TYPE_DMS, 0);
            $this->updateCountStatus($countId, self::COUNT_STATUS_NEW);
        }

        // Remove DMS records if necessary
        if ($count['UseDMS'] == 0 && $this->getCountPartForDms($count['Id'])) {
            $this->removeCountPart($countId, self::SOURCE_TYPE_DMS, 0);
        }

        return true;
    }

    /**
     * @param int   $countId
     * @param array $input
     * @return true
     */
    private function createOrRemoveCountSuppressionsFromCounts($countId, $input)
    {
        $this->updateCountStatusMessage($countId, 'Apply suppressions from counts');

        $count = $this->getCount($countId);

        $sql          = 'SELECT DISTINCT SourceId FROM application_counts_suppressions WHERE CountId = "' . $count['Id'] . '" AND SourceType = "' . self::SOURCE_TYPE_COUNT . '"';
        $existing_ids = $this->common_model->array_result_field($sql, 'SourceId');

        // Import count suppression as necessary
        foreach ($input as $source_id) {
            if (in_array($source_id, $existing_ids)) {
                continue;
            }

            $sql = 'INSERT INTO application_counts_suppressions (CountId, SourceType, SourceId, First, Last, Address, City, State, Zip)
                    SELECT "' . $count['Id'] . '",
                           "' . self::SOURCE_TYPE_COUNT . '",
                           "' . $source_id . '",
                           First,
                           Last,
                           Address,
                           City,
                           State,
                           LEFT(Zip, 5) AS Zip
                      FROM application_counts_prospects
                     WHERE CountId = "' . $source_id . '"
                       AND flag_selected = 1
                    ';

            $this->cacheFlush($countId);

            $this->common_model->execute_query($sql);
        }

        // Remove count suppressions as necessary
        foreach ($existing_ids as $source_id) {
            if (in_array($source_id, $input)) {
                continue;
            }

            $sql = 'DELETE FROM application_counts_suppressions WHERE CountId = "' . $count['Id'] . '" AND SourceType = "' . self::SOURCE_TYPE_COUNT . '" AND SourceId = "' . $source_id . '"';
            $this->common_model->execute_query($sql);

            $this->cacheFlush($countId);
        }

        return true;
    }

    /**
     * @param int   $countId
     * @param array $input
     * @return true
     */
    private function createOrRemoveCountSuppressionsFromJobs($countId, $input)
    {
        $this->updateCountStatusMessage($countId, 'Apply suppressions from jobs');

        $count = $this->getCount($countId);

        $sql          = 'SELECT DISTINCT SourceId FROM application_counts_suppressions WHERE CountId = "' . $count['Id'] . '" AND SourceType = "' . self::SOURCE_TYPE_JOB . '"';
        $existing_ids = $this->common_model->array_result_field($sql, 'SourceId');

        // Import job suppression as necessary
        foreach ($input as $source_id) {
            if (in_array($source_id, $existing_ids)) {
                continue;
            }

            $sql   = "SELECT Tablename FROM application_barcode_branch WHERE CampaignId = '" . $source_id . "'";
            $table = $this->common_model->single_result_field($sql, 'Tablename');

            $sql = 'INSERT INTO application_counts_suppressions (CountId, SourceType, SourceId, First, Last, Address, City, State, Zip)
                    SELECT "' . $count['Id'] . '",
                           "' . self::SOURCE_TYPE_JOB . '",
                           "' . $source_id . '",
                           Fname AS First,
                           Lname AS Last,
                           Address,
                           City,
                           State,
                           LEFT(Zip, 5) AS Zip
                      FROM ' . $table . '
                     WHERE CampaignId = "' . $source_id . '"
                    ';

            $this->common_model->execute_query($sql);

            $this->cacheFlush($countId);
        }

        // Remove job suppressions as necessary
        foreach ($existing_ids as $source_id) {
            if (in_array($source_id, $input)) {
                continue;
            }

            $sql = 'DELETE FROM application_counts_suppressions WHERE CountId = "' . $count['Id'] . '" AND SourceType = "' . self::SOURCE_TYPE_JOB . '" AND SourceId = "' . $source_id . '"';
            $this->common_model->execute_query($sql);

            $this->cacheFlush($countId);
        }

        return true;
    }

    /**
     * @param int   $countId
     * @param array $input
     * @return true
     */
    private function createOrRemoveCountSuppressionsFromResponses($countId, $input)
    {
        $this->updateCountStatusMessage($countId, 'Apply suppressions from responses');

        $count = $this->getCount($countId);

        $sql          = 'SELECT DISTINCT SourceId FROM application_counts_suppressions WHERE CountId = "' . $count['Id'] . '" AND SourceType = "' . self::SOURCE_TYPE_RESPONSE . '"';
        $existing_ids = $this->common_model->array_result_field($sql, 'SourceId');

        // Import job suppression as necessary
        foreach ($input as $source_id) {
            if (in_array($source_id, $existing_ids)) {
                continue;
            }

            $sql = 'INSERT INTO application_counts_suppressions (CountId, SourceType, SourceId, First, Last, Address, City, State, Zip)
                    SELECT "' . $count['Id'] . '",
                           "' . self::SOURCE_TYPE_RESPONSE . '",
                           "' . $source_id . '",
                           person.fname   AS First,
                           person.lname   AS Last,
                           person.address AS Address,
                           person.city    AS City,
                           person.state   AS State,
                           person.zip     AS Zip
                      FROM application_campaign AS campaign
                      JOIN application_response AS response
                           ON campaign.id = response.CampaignId
                      JOIN application_person   AS person
                           ON response.AddressId = person.id
                     WHERE campaign.Id = "' . $source_id . '"
                       AND person.address != ""
                       AND person.city != ""
                       AND person.state != ""
                       AND person.zip != ""
                     GROUP BY person.address
                    ';

            $this->common_model->execute_query($sql);

            $this->cacheFlush($countId);
        }

        // Remove job suppressions as necessary
        foreach ($existing_ids as $source_id) {
            if (in_array($source_id, $input)) {
                continue;
            }

            $sql = 'DELETE FROM application_counts_suppressions WHERE CountId = "' . $count['Id'] . '" AND SourceType = "' . self::SOURCE_TYPE_RESPONSE . '" AND SourceId = "' . $source_id . '"';
            $this->common_model->execute_query($sql);

            $this->cacheFlush($countId);
        }

        return true;
    }

    /**
     * @param int $countId
     * @return true
     */
    private function excludeCountProspectsCompanies($countId)
    {
        $this->updateCountStatusMessage($countId, 'Excluding business records');

        // Reset flag_company on prospects
        $sql = 'UPDATE application_counts_prospects SET flag_company = 0 WHERE CountId = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        // Update flag_company on prospects
        $sql = "UPDATE application_counts_prospects 
                   SET application_counts_prospects.flag_company = 1 
                 WHERE application_counts_prospects.CountId = '" . $countId . "' 
                   AND ( 
                         (
                          CONCAT_WS(' ', First, Last) 
                          REGEXP '(LLC| INC|INC[.]|INC | CORP|CORP[.]|CORP | COMP|COMP[.]|COMP |CO[.]|CARS|AUTO|MOTOR|AUCTION|MANHEIM|DBA|TRUST|,|;|\\\\(|\\\\)|[.]|\\\\?|\\\\#|[0-9]|ASSOC|CONTRA|CONSTRU|CUSTOMER|VALUE)' 
                          OR First LIKE CONCAT('%', Last, '%') 
                         ) 
                         OR
                         ( 
                          TRIM(First) = '' 
                          OR TRIM(Last) = '' 
                          OR LENGTH(TRIM(First)) < 2
                          OR LENGTH(TRIM(Last))  < 2  
                          OR First IS NULL 
                          OR Last  IS NULL 
                          OR TRIM(First) NOT RLIKE '^[A-Za-z \\-''-]*$' 
                          OR TRIM(Last)  NOT RLIKE '^[A-Za-z \\-''-]*$' 
                          OR LENGTH(TRIM(First)) > 35 
                          OR LENGTH(TRIM(Last))  > 35 
                         ) 
                       ) 
        ";

        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return true
     */
    private function excludeCountProspectsDoNotMail($countId)
    {
        $this->updateCountStatusMessage($countId, 'Excluding DNM records');

        // Reset flag_do_not_mail on prospects
        $sql = 'UPDATE application_counts_prospects SET flag_do_not_mail = 0 WHERE CountId = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        // Update flag_do_not_mail on prospects
        $sql = "UPDATE application_counts_prospects
            INNER JOIN application_do_not_mail
                    ON application_counts_prospects.Address = application_do_not_mail.Address
                   AND application_counts_prospects.Zip     = LEFT(application_do_not_mail.Zip, 5)
                   SET application_counts_prospects.flag_do_not_mail = 1
                 WHERE application_counts_prospects.CountId = '" . $countId . "'
            ";

        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return true
     */
    private function excludeCountProspectsSuppressed($countId)
    {
        $this->updateCountStatusMessage($countId, 'Excluding suppressed records');

        // Reset flag_suppressed on prospects
        $sql = 'UPDATE application_counts_prospects SET flag_suppressed = 0 WHERE CountId = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        // Update flag_suppressed on prospects
        $sql = "UPDATE application_counts_prospects
            INNER JOIN application_counts_suppressions
                    ON application_counts_prospects.CountId      = application_counts_suppressions.CountId
                   AND application_counts_prospects.Address      = application_counts_suppressions.Address
                   AND LEFT(application_counts_prospects.Zip, 5) = LEFT(application_counts_suppressions.Zip, 5)
                   SET application_counts_prospects.flag_suppressed = 1
                 WHERE application_counts_prospects.CountId = '" . $countId . "'
            ";

        $this->common_model->execute_query($sql);

        return true;
    }

    /**
     * @param int $countId
     * @return string
     */
    private function filepathCountNcoaInput($countId)
    {
        return path_to_resources('counts/ncoa/' . $countId . '-input.csv');
    }

    /**
     * @param int $countId
     * @return string
     */
    private function filepathCountNcoaOutput($countId)
    {
        return path_to_resources('counts/ncoa/' . $countId . '-output.csv');
    }

    /**
     * @param int $countId
     * @param int $sourceId
     * @return array|false
     */
    private function getCountPartForDataset($countId, $sourceId)
    {
        $sql = "SELECT * FROM application_counts_parts WHERE CountId = '" . $countId . "' AND SourceType = '" . self::SOURCE_TYPE_SET . "' AND SourceId = '" . $sourceId . "'";

        return $this->common_model->single_result_assoc($sql);
    }

    /**
     * @param int $countId
     * @return array|false
     */
    private function getCountPartForDms($countId)
    {
        $sql = "SELECT * FROM application_counts_parts WHERE CountId = '" . $countId . "' AND SourceType = '" . self::SOURCE_TYPE_DMS . "' AND SourceId = 0";

        return $this->common_model->single_result_assoc($sql);
    }

    /**
     * @param int    $countId
     * @param string $status
     * @param string $state
     * @param array  $additional_data
     * @return void
     */
    private function ncoaStatus($countId, $status, $state, array $additional_data = [])
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $data = [
                'NcoaUpdated' => date('Y-m-d H:i:s'),
                'NcoaStatus'  => $status,
                'NcoaState'   => $state,
            ] + $additional_data;

        $this->common_model->mysql_update('application_counts', $data, $countId);
    }

    /**
     * @param int    $countId
     * @param string $sourceType
     * @param int    $sourceId
     * @return void
     */
    private function removeCountPart($countId, $sourceType, $sourceId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $sql = "DELETE
                  FROM application_counts_parts
                 WHERE CountId = '" . $countId . "'
                   AND SourceType = '" . $sourceType . "'
                   AND SourceId = '" . $sourceId . "'";

        $this->common_model->execute_query($sql);

        if ($sourceType == self::SOURCE_TYPE_DMS) {
            $this->removeCountProspects($countId, self::SOURCE_TYPE_DMS, 0);
        }

        if ($sourceType == self::SOURCE_TYPE_SET) {
            $this->removeCountProspects($countId, self::SOURCE_TYPE_SET, $sourceId);
        }

        $this->cacheFlush($countId);
        $this->cacheFlush($countId, $sourceId);
    }

    /**
     * @param int    $countId
     * @param string $sourceType
     * @param int    $sourceId
     * @return void
     */
    private function removeCountProspects($countId, $sourceType, $sourceId)
    {
        $this->updateCountStatusMessage($countId, __FUNCTION__);

        $sql = "DELETE
                  FROM application_counts_prospects
                 WHERE CountId = '" . $countId . "'
                   AND SourceType = '" . $sourceType . "'
                   AND SourceId = '" . $sourceId . "'";

        $this->common_model->execute_query($sql);
    }

    /**
     * @param array $filters
     * @return array
     */
    private function sanitizeFilters(array $filters)
    {
        foreach (self::COUNT_FILTERS as $filter) {
            if (!array_key_exists($filter, $filters)) {
                $filters[$filter] = '';
            }

            if ($filter == 'GlobalRadius' && $filters[$filter] < 1) {
                $filters[$filter] = 100;
            }

            if (!is_array($filters[$filter]) && in_array($filter, ['GlobalMakes', 'GlobalModels', 'GlobalZips'])) {
                $filters[$filter] = array_filter(explode(',', $filters[$filter]));
            }
        }

        return $filters;
    }

    /**
     * @param int        $countId
     * @param int|null   $sourceId
     * @param array|null $part
     * @param bool       $excludeFlaggedProspects
     * @return string
     */
    private function selectionSql($countId, $sourceId = null, $part = null, $excludeFlaggedProspects = true)
    {
        $exclude_flagged_prospects_sql = '
            AND application_counts_prospects.flag_company        = 0
            AND application_counts_prospects.flag_do_not_mail    = 0
            AND application_counts_prospects.flag_duplicate      = 0
            AND application_counts_prospects.flag_invalid        = 0
            AND application_counts_prospects.flag_nonresidential = 0
            AND application_counts_prospects.flag_suppressed     = 0
        ';

        if ($part == null && $sourceId == null) {
            $sql = 'SELECT * FROM application_counts_prospects WHERE CountId = "' . $countId . '" AND flag_selected = 1';

            if ($excludeFlaggedProspects) {
                $sql .= $exclude_flagged_prospects_sql;
            }

            return $sql;
        }

        if ($part == null) {
            $sql = 'SELECT * FROM application_counts_prospects WHERE CountId = "' . $countId . '" AND SourceId = "' . $sourceId . '" AND flag_selected = 1';

            if ($excludeFlaggedProspects) {
                $sql .= $exclude_flagged_prospects_sql;
            }

            return $sql;
        }

        $part = $this->sanitizeFilters($part);

        /**
         * This string should remain double quoted for the sake of the SQL query
         *
         * @noinspection UnNecessaryDoubleQuotesInspection
         * @noinspection PhpUnnecessaryDoubleQuotesInspection
         */
        $sql = "
                SELECT *
                  FROM application_counts_prospects 
                 WHERE CountId  = " . $countId . "
                   AND SourceId = " . $sourceId . "
               ";

        if ($excludeFlaggedProspects) {
            $sql .= $exclude_flagged_prospects_sql;
        }

        $sql .= "
                   AND
                   
                   CASE WHEN '" . $part['GlobalRadius'] . "' > 0
                             THEN Distance IS NOT NULL AND Distance <= '" . $part['GlobalRadius'] . "'
                        ELSE
                             TRUE
                        END

                   AND
                   
                   CASE WHEN '" . count($part['GlobalZips']) . "' > 0
                             THEN Zip IN " . (count($part['GlobalZips']) ? $this->sqlInArray($part['GlobalZips']) : "('EMPTY_SET_PLACEHOLDER')") . "
                        ELSE
                             TRUE
                        END                   

                   AND
                   
                   CASE WHEN CAST('" . $part['GlobalVehicleYearMin'] . "' AS UNSIGNED INTEGER) > 0
                             THEN Year >= CAST('" . $part['GlobalVehicleYearMin'] . "' AS UNSIGNED INTEGER)
                        ELSE
                             TRUE
                        END
                   
                   AND
                   
                   CASE WHEN CAST('" . $part['GlobalVehicleYearMax'] . "' AS UNSIGNED INTEGER) > 0
                             THEN Year <= CAST('" . $part['GlobalVehicleYearMax'] . "' AS UNSIGNED INTEGER)
                        ELSE
                             TRUE
                        END
                   
                   AND
                   
                   CASE WHEN '" . count($part['GlobalMakes']) . "' > 0
                             THEN Make IN " . (count($part['GlobalMakes']) ? $this->sqlInArray($part['GlobalMakes']) : "('EMPTY_SET_PLACEHOLDER')") . "
                        ELSE
                             TRUE
                        END
                   
                   AND
                   
                   CASE WHEN '" . count($part['GlobalModels']) . "' > 0
                             THEN Model IN " . (count($part['GlobalModels']) ? $this->sqlInArray($part['GlobalModels']) : "('EMPTY_SET_PLACEHOLDER')") . "
                        ELSE
                             TRUE
                        END
                   
                   AND
                   
                   (
                           CASE WHEN '" . $part['SalesInclude'] . "'
                                     THEN DealType IN " . $this->sqlInArray(sales_service_model::DMS_DEAL_TYPE_SALE) . " 
                                          
                                          AND
                                          
                                          CASE WHEN '" . $part['SalesVehicleType'] . "' = 'new' -- SalesVehicleType
                                                    THEN VehicleType IN " . $this->sqlInArray(sales_service_model::DMS_VEHICLE_TYPE_NEW) . "
                                               WHEN '" . $part['SalesVehicleType'] . "' = 'used' -- SalesVehicleType
                                                    THEN VehicleType IN " . $this->sqlInArray(sales_service_model::DMS_VEHICLE_TYPE_USED) . "
                                               ELSE
                                                    TRUE
                                               END
                                               
                                          AND
                                          
                                          CASE WHEN '" . $part['SalesMonthsMin'] . "' > 0 -- SalesMonthsMin
                                                    THEN DATE(SoldDate) <= DATE_SUB(CURDATE(), INTERVAL '" . $part['SalesMonthsMin'] . "' MONTH)
                                               ELSE
                                                    TRUE
                                               END
                                               
                                          AND
                                          
                                          CASE WHEN '" . $part['SalesMonthsMax'] . "' > 0 -- SalesMonthsMax
                                                    THEN DATE(SoldDate) >= DATE_SUB(CURDATE(), INTERVAL '" . $part['SalesMonthsMax'] . "' MONTH)
                                               ELSE
                                                    TRUE
                                               END
                                               
                                          AND
                                          
                                          CASE WHEN '" . $part['SalesServiceInclude'] . "' = 0 -- SalesServiceInclude
                                                    THEN LastServiceDate IS NULL
                                               ELSE
                                                    (
                                                        CASE WHEN '" . $part['SalesServiceMonthsMin'] . "' > 0 -- SalesServiceMonthsMin
                                                                  THEN DATE(LastServiceDate) <= DATE_SUB(CURDATE(), INTERVAL '" . $part['SalesServiceMonthsMin'] . "' MONTH)
                                                             ELSE
                                                                  TRUE
                                                             END
                                                             
                                                        AND
                                                        
                                                        CASE WHEN '" . $part['SalesServiceMonthsMax'] . "' > 0 -- SalesServiceMonthsMax
                                                                  THEN DATE(LastServiceDate) >= DATE_SUB(CURDATE(), INTERVAL '" . $part['SalesServiceMonthsMax'] . "' MONTH)
                                                             ELSE
                                                                  TRUE
                                                             END
                                                    )
                                                         
                                                    OR
                                                    
                                                    LastServiceDate IS NULL
                                               END
                                               
                                          OR
                                          
                                          CASE WHEN '" . $part['SalesOptionCashPurchases'] . "' -- SalesOptionCashPurchases
                                                    THEN DealType IN ('CASH')
                                                     AND DATE(SoldDate) <= DATE_SUB(CURDATE(), INTERVAL '18' MONTH)
                                                     AND DATE(SoldDate) >= DATE_SUB(CURDATE(), INTERVAL '30' MONTH)
                                                     
                                                     AND
                                                     
                                                     CASE WHEN '" . $part['SalesServiceInclude'] . "' = 0 -- SalesServiceInclude
                                                               THEN LastServiceDate IS NULL
                                                          ELSE
                                                               (
                                                                   CASE WHEN '" . $part['SalesServiceMonthsMin'] . "' > 0 -- SalesServiceMonthsMin
                                                                             THEN DATE(LastServiceDate) <= DATE_SUB(CURDATE(), INTERVAL '" . $part['SalesServiceMonthsMin'] . "' MONTH)
                                                                        ELSE
                                                                             TRUE
                                                                        END
                                                                        
                                                                   AND
                                                                   
                                                                   CASE WHEN '" . $part['SalesServiceMonthsMax'] . "' > 0 -- SalesServiceMonthsMax
                                                                             THEN DATE(LastServiceDate) >= DATE_SUB(CURDATE(), INTERVAL '" . $part['SalesServiceMonthsMax'] . "' MONTH)
                                                                        ELSE
                                                                             TRUE
                                                                        END
                                                               )
                                                                    
                                                               OR
                                                               
                                                               LastServiceDate IS NULL
                                                          END
                                               ELSE
                                                    FALSE
                                               END    
                                ELSE
                                     FALSE
                                END
                           
                           OR
                           
                           CASE WHEN '" . $part['LeasesInclude'] . "'
                                     THEN DealType IN " . $this->sqlInArray(sales_service_model::DMS_DEAL_TYPE_LEASE) . " AND DATE(LeaseExpiring) >= CURDATE()
                                          
                                          AND

                                          CASE WHEN '" . $part['LeasesMonthsMin'] . "' > 0
                                                    THEN DATE(SoldDate) <= DATE_SUB(CURDATE(), INTERVAL '" . $part['LeasesMonthsMin'] . "' MONTH) AND DATE(LeaseExpiring) >= CURDATE()
                                               ELSE
                                                    TRUE
                                               END
        
                                          AND
                                          
                                          CASE WHEN '" . $part['LeasesMonthsMax'] . "' > 0
                                                    THEN DATE(SoldDate) >= DATE_SUB(CURDATE(), INTERVAL '" . $part['LeasesMonthsMax'] . "' MONTH) AND DATE(LeaseExpiring) >= CURDATE()
                                               ELSE
                                                    TRUE
                                               END

                                          AND
                                          
                                          CASE WHEN '" . $part['LeasesMonthsExpiring'] . "' > 0
                                                    THEN DATE(LeaseExpiring) <= DATE_ADD(CURDATE(), INTERVAL '" . $part['LeasesMonthsExpiring'] . "' MONTH) AND DATE(LeaseExpiring) >= CURDATE()
                                               ELSE
                                                    TRUE
                                               END
                                          
                                          AND
                                          
                                          CASE WHEN '" . $part['LeasesServiceInclude'] . "' = 0
                                                    THEN LastServiceDate IS NULL
                                               ELSE
                                                    (
                                                        CASE WHEN '" . $part['LeasesServiceMonthsMin'] . "' > 0
                                                                  THEN DATE(LastServiceDate) <= DATE_SUB(CURDATE(), INTERVAL '" . $part['LeasesServiceMonthsMin'] . "' MONTH)
                                                             ELSE
                                                                  TRUE
                                                             END
                                                             
                                                        AND
                                                        
                                                        CASE WHEN '" . $part['LeasesServiceMonthsMax'] . "' > 0
                                                                  THEN DATE(LastServiceDate) >= DATE_SUB(CURDATE(), INTERVAL '" . $part['LeasesServiceMonthsMax'] . "' MONTH)
                                                             ELSE
                                                                  TRUE
                                                             END
                                                    )
                                                         
                                                    OR
                                                    
                                                    LastServiceDate IS NULL
                                               END
                                ELSE
                                     FALSE
                                END
                           
                           OR
                           
                           CASE WHEN '" . $part['ServicesInclude'] . "'
                                     THEN LastServiceDate IS NOT NULL AND (SoldDate IS NULL OR SoldDate = '0000-00-00')
                                          
                                          AND
                                          
                                          CASE WHEN '" . $part['ServicesMonthsMin'] . "' > 0
                                                    THEN DATE(LastServiceDate) <= DATE_SUB(CURDATE(), INTERVAL '" . $part['ServicesMonthsMin'] . "' MONTH)
                                               ELSE
                                                    TRUE
                                               END
                                               
                                          AND
                                          
                                          CASE WHEN '" . $part['ServicesMonthsMax'] . "' > 0
                                                    THEN DATE(LastServiceDate) >= DATE_SUB(CURDATE(), INTERVAL '" . $part['ServicesMonthsMax'] . "' MONTH)
                                               ELSE
                                                    TRUE
                                               END
                                ELSE
                                     FALSE
                                END
                   )
        ";

        return $sql;
    }

    /**
     * @param array $array
     * @return string
     */
    private function sqlInArray($array)
    {
        $mappedArray = array_map(static function ($value) {
            return " '" . $value . "'";
        }, $array);

        $string = '(' . implode(',', $mappedArray) . ')';

        return $string;
    }

    /**
     * @param int   $countId
     * @param array $input
     * @return true
     */
    private function updateCountPartsFilters($countId, $input)
    {
        $this->updateCountStatusMessage($countId, 'Update part filters');

        // Initialize part filter values
        foreach ($input as $source_type => $parts) {
            foreach ($parts as $source_id => $part) {
                $input[$source_type][$source_id] = $this->sanitizeFilters($input[$source_type][$source_id]);
            }
        }

        // Update filter values
        foreach ($input as $source_type => $parts) {
            foreach ($parts as $source_id => $part) {
                // Initialize the filters changed flag
                $flagFiltersChanged = false;

                foreach ($part as $key => $value) {
                    if (is_array($value)) {
                        $this->common_model->execute_query("UPDATE application_counts_parts SET $key = '" . implode(',', $value) . "' WHERE CountId = " . $countId . " AND SourceType = '" . $source_type . "' AND SourceId = '" . $source_id . "'");
                    } elseif ($value === '') {
                        $this->common_model->execute_query("UPDATE application_counts_parts SET $key = NULL WHERE CountId = " . $countId . " AND SourceType = '" . $source_type . "' AND SourceId = '" . $source_id . "'");
                    } else {
                        $this->common_model->execute_query("UPDATE application_counts_parts SET $key = '" . $value . "' WHERE CountId = '" . $countId . "' AND SourceType = '" . $source_type . "' AND SourceId = '" . $source_id . "'");
                    }

                    if ($this->common_model->mysql_affected_rows() > 0) {
                        $this->debug('Filter changed: ' . $source_type . ' ' . $source_id . ' ' . $key);
                        $flagFiltersChanged = true;
                    }
                }

                if ($flagFiltersChanged) {
                    $this->cacheFlush($countId);
                    $this->cacheFlush($countId, $source_id);
                }
            }
        }

        return true;
    }

    /**
     * @param int $countId
     * @return void
     */
    private function updateCountProspectsSelected($countId)
    {
        $this->updateCountStatusMessage($countId, 'Selecting records');

        // Reset flag_selected on prospects
        $sql = "UPDATE application_counts_prospects SET flag_selected = 0 WHERE CountId = '" . $countId . "'";
        $this->common_model->execute_query($sql);

        foreach ($this->getCountParts($countId) as $part) {
            /** @noinspection SqlWithoutWhere */
            $sql = 'UPDATE application_counts_prospects RIGHT JOIN (' . $this->selectionSql($part['CountId'], $part['SourceId'], $part) . ') AS subquery ON application_counts_prospects.Id = subquery.Id SET application_counts_prospects.flag_selected = 1';
            $this->common_model->execute_query($sql);
        }
    }

    /**
     * @param int $countId
     * @return void
     */
    private function updateCountTotals($countId)
    {
        $this->updateCountStatusMessage($countId, 'Counting selected records');

        $sql = 'UPDATE application_counts
                   SET CalculatedProspects = (SELECT COUNT(*) FROM application_counts_prospects WHERE CountId = "' . $countId . '"),
                       CalculatedSelected  = (SELECT COUNT(*) FROM application_counts_prospects WHERE CountId = "' . $countId . '" AND flag_selected = 1)
                 WHERE Id = "' . $countId . '"';

        $this->common_model->execute_query($sql);
    }
}

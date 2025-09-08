<?php

use application\apis\Accutrace;
use application\services\Profiler;

class counts extends base_controller
{
    /**
     * System statistics queries
     *
     * @noinspection SqlResolve
     */
    const SYSTEM_STATS_QUERIES = [
        'application_client_sales Total'                    => 'SELECT COUNT(id) AS count FROM application_client_sales',
        'application_client_sales_processed Total'          => 'SELECT COUNT(id) AS count FROM application_client_sales_processed',
        'application_client_sales_processed NCOA good'      => 'SELECT COUNT(id) AS count FROM application_client_sales_processed WHERE Cid IN (__active_cids__) AND (LastNCOA >= NOW() - INTERVAL 1 MONTH)',
        'application_client_sales_processed NCOA needed'    => 'SELECT COUNT(id) AS count FROM application_client_sales_processed WHERE Cid IN (__active_cids__) AND (LastNCOA IS NULL || LastNCOA <= NOW() - INTERVAL 1 MONTH)',
        'application_client_service Total'                  => 'SELECT COUNT(id) AS count FROM application_client_service',
        'application_client_service_processed Total'        => 'SELECT COUNT(id) AS count FROM application_client_service_processed',
        'application_client_service_processed NCOA good'    => 'SELECT COUNT(id) AS count FROM application_client_service_processed WHERE Cid IN (__active_cids__) AND (LastNCOA >= NOW() - INTERVAL 1 MONTH)',
        'application_client_service_processed NCOA needed'  => 'SELECT COUNT(id) AS count FROM application_client_service_processed WHERE Cid IN (__active_cids__) AND (LastNCOA IS NULL || LastNCOA <= NOW() - INTERVAL 1 MONTH)',
        'application_counts_datasets_prospects Total'       => 'SELECT COUNT(id) AS count FROM application_counts_datasets_prospects',
        'application_counts_datasets_prospects NCOA good'   => 'SELECT COUNT(id) AS count FROM application_counts_datasets_prospects WHERE (LastNCOA >= NOW() - INTERVAL 1 MONTH)',
        'application_counts_datasets_prospects NCOA needed' => 'SELECT COUNT(id) AS count FROM application_counts_datasets_prospects WHERE (LastNCOA IS NULL || LastNCOA <= NOW() - INTERVAL 1 MONTH)',
    ];

    public $configAuthorizeAdmins = true;

    public $configBodyCssClass = 'page-counts';

    public $configSessionStayOpen = false;

    public function index()
    {
        $this->authorizeGetRequests();

        $data = [
            'counts' => $this->counts_model->getCounts(),
        ];

        return $this->renderLayout('counts/index', $data);
    }

    public function ajax_flush_cache($countId)
    {
        $this->counts_model->cacheFlushPublic($countId);

        return $this->returnAjaxSuccess();
    }

    public function ajax_get_count_status($count_id = null)
    {
        Profiler::shouldSaveFrame(false);

        $this->authorizeGetRequests();

        if (!$count_id) {
            $count_id = $this->common_model->single_result_field('SELECT MAX(Id) AS Id FROM application_counts', 'Id');
        }

        $data = [
            'count_id' => $count_id,
            'message'  => $this->counts_model->getCountSystemStatus($count_id),
            'locked'   => $this->counts_model->isCountLocked($count_id),
        ];

        return $this->renderJson($data);
    }

    public function ajax_get_ncoa_status($countId)
    {
        Profiler::shouldSaveFrame(false);

        $this->authorizeGetRequests();

        $data = [
            'count'      => $this->counts_model->getCount($countId),
            'ncoa'       => $this->counts_model->getCountAccuZipStatus($countId),
            'ncoa_files' => $this->counts_model->getCountAccuZipFiles($countId),
        ];

        return $this->renderView('counts/_ncoa_status', $data);
    }

    public function ajax_part_stats($count_id, $source_id)
    {
        $part = [
            'CountId'  => $count_id,
            'SourceId' => $source_id,
        ];

        foreach (counts_model::COUNT_FILTERS as $field) {
            $part[$field] = $this->input->get($field);
        }

        $data = [
            'statistics' => $this->counts_model->getCountStatistics($count_id, $source_id, $part),
            'breakdowns' => $this->counts_model->getCountBreakdowns($count_id, $source_id, $part),
        ];

        return $this->renderView('counts/_count_statistics', $data);
    }

    public function ajax_set_status_pending($countId)
    {
        $this->authorizePostRequests();

        $mode = $this->input->post('mode');

        $this->counts_model->updateCountMode($countId, $mode);

        $this->counts_model->updateCountStatus($countId, counts_model::COUNT_STATUS_PENDING);

        return $this->returnAjaxSuccess();
    }

    public function create()
    {
        $this->authorizeGetRequests();

        $data = [
            'clients' => $this->counts_model->getClients(),
            'users'   => $this->counts_model->getUsers(),
        ];

        return $this->renderLayout('counts/create', $data);
    }

    public function datasets()
    {
        $this->authorizeGetRequests();
        $this->authorizeSuperAdmins();

        $data = [
            'datasets'   => $this->counts_model->getDatasets(),
            'clients'    => $this->counts_model->getClients(),
            'statistics' => $this->counts_model->getDatasetStatistics(),
        ];

        return $this->renderLayout('counts/datasets', $data);
    }

    public function datasets_create()
    {
        $this->authorizeGetRequests();
        $this->authorizeSuperAdmins();

        $data = [
            'clients' => $this->counts_model->getClients(),
        ];

        return $this->renderLayout('counts/datasets_create', $data);
    }

    public function datasets_delete($datasetId)
    {
        $this->authorizePostRequests();
        $this->authorizeSuperAdmins();

        $this->counts_model->removeDataset($datasetId);

        return $this->returnRedirect(__CLASS__, 'datasets');
    }

    public function datasets_store()
    {
        $this->authorizePostRequests();
        $this->authorizeSuperAdmins();

        if (!array_key_exists('prospects', $_FILES) || $_FILES['prospects']['size'] == 0) {
            return $this->renderError('No file uploaded');
        }

        $clientId = $this->input->post('ClientId');
        $name     = $this->input->post('Name');
        $type     = $this->input->post('Type');

        $filepath = $_FILES['prospects']['tmp_name'];
        $filename = $_FILES['prospects']['name'];

        try {
            $this->counts_model->createDataset($name, $filepath, $filename, $clientId, $type);
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        return $this->returnRedirect(__CLASS__, 'datasets');
    }

    public function datasets_view($datasetId)
    {
        $this->authorizeGetRequests();
        $this->authorizeSuperAdmins();

        if (!$this->counts_model->existsDataset($datasetId)) {
            return $this->renderNotFound();
        }

        $data = [
            'dataset' => $this->counts_model->getDataset($datasetId),
            'columns' => array_combine(array_keys(counts_model::COLUMNS_DATASET_PROSPECTS_IMPORT), array_keys(counts_model::COLUMNS_DATASET_PROSPECTS_IMPORT)),
            'records' => $this->counts_model->getDatasetProspects($datasetId),
        ];

        return $this->renderLayout('counts/datasets_view', $data);
    }

    public function deduplicate($countId)
    {
        try {
            $this->counts_model->excludeCountProspectsDuplicates($countId);
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        return $this->returnRedirect(__CLASS__, 'edit', $countId);
    }

    public function delete($countId)
    {
        $this->authorizePostRequests();

        $this->counts_model->removeCount($countId);

        return $this->returnRedirect(__CLASS__);
    }

    public function edit($countId)
    {
        $this->authorizeGetRequests();

        if (!$this->counts_model->existsCount($countId)) {
            return $this->renderNotFound();
        }

        // Start debugger
        $this->counts_model->debug('Start');

        $count = $this->counts_model->getCount($countId);

        $count['ExportedOptions']   = $this->jsonDecodeOrEmptyArray($count['ExportedOptions']);
        $count['SuppressJobs']      = $this->jsonDecodeOrEmptyArray($count['SuppressJobs']);
        $count['SuppressResponses'] = $this->jsonDecodeOrEmptyArray($count['SuppressResponses']);
        $count['SuppressCounts']    = $this->jsonDecodeOrEmptyArray($count['SuppressCounts']);

        $data = [
            'presets'      => counts_model::COUNT_PRESETS,
            'count'        => $count,
            'parts'        => $this->counts_model->getCountParts($countId),
            'options'      => $this->counts_model->getCountPartsOptions($countId),
            'statistics'   => $this->counts_model->getCountStatistics($countId),
            'breakdowns'   => $this->counts_model->getCountBreakdowns($countId),
            'all_by_zip'   => $this->counts_model->getCountSelectionZipcodeStatistics($countId),
            'suppressions' => $this->counts_model->getCountSuppressionStatistics($countId),
            'counts'       => $this->counts_model->getCounts(),
            'ncoa_summary' => $this->counts_model->getCountNcoaSummary($countId),
        ];

        foreach ($data['parts'] as $part) {
            $data['statistics_parts'][$part['SourceId']] = $this->counts_model->getCountStatistics($count['Id'], $part['SourceId']);
            $data['breakdowns_parts'][$part['SourceId']] = $this->counts_model->getCountBreakdowns($count['Id'], $part['SourceId']);
        }

        $this->counts_model->debug('Getting client feeds');
        $data['feeds'] = $count['ClientId'] ? $this->counts_model->getClientFeeds($count['ClientId']) : null;

        $this->counts_model->debug('Getting client datasets');
        $data['datasets'] = $count['ClientId'] ? $this->counts_model->getClientDatasets($count['ClientId']) : null;

        $this->counts_model->debug('Getting client jobs');
        $data['jobs'] = $count['ClientId'] ? $this->counts_model->getClientJobs($count['ClientId']) : null;

        // End debugger
        $this->counts_model->debug('End');

        $data['debug_last']    = json_decode($count['Debug'], true);
        $data['debug_current'] = $this->counts_model->debugGetCurrent();

        $this->counts_model->updateCountStatusMessage($countId, 'Ready');

        return $this->renderLayout('counts/edit', $data);
    }

    public function export($countId)
    {
        $this->authorizePostRequests();

        if (!$count = $this->counts_model->getCount($countId)) {
            return $this->renderNotFound();
        }

        $columns  = counts_model::COLUMNS_EXPORT;
        $filename = 'Count ' . $countId . ' - ' . ($count['client__Name'] ?: $count['Zip']) . ' - ' . date('Y-m-d') . '.csv';
        $options  = json_decode($count['ExportedOptions'], true);

        if (!is_array($options) || empty($options)) {
            return $this->renderText('No export options selected');
        }

        $parts = $this->counts_model->getCountParts($countId);

        $sql = 'UPDATE application_counts SET Exported = NOW(), ExportedFilename = "' . $filename . '" WHERE Id = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        $fp = fopen('php://output', 'wb');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        fputcsv($fp, $columns);

        foreach ($parts as $part) {
            if (!array_key_exists($part['SourceId'], $options)) {
                continue;
            }

            $sql   = [];
            $sql[] = 'SELECT ' . formatArrayToCsv(array_keys($columns)) . ' FROM application_counts_prospects WHERE CountId = ' . $part['CountId'] . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected= 1';

            if ($options[$part['SourceId']]['method'] == 'all') {
                $sql[] = 'ORDER BY Id';

                $sql = implode(' ', $sql);

                $res = $this->common_model->execute_query($sql);

                while ($row = mysql_fetch_assoc($res)) {
                    fputcsv($fp, $row);
                }
            } elseif ($options[$part['SourceId']]['method'] == 'closest') {
                $sql[] = 'ORDER BY Distance LIMIT ' . (empty($options[$part['SourceId']]['closest']) ? 0 : $options[$part['SourceId']]['closest']);

                $sql = implode(' ', $sql);

                $res = $this->common_model->execute_query($sql);

                while ($row = mysql_fetch_assoc($res)) {
                    fputcsv($fp, $row);
                }
            } elseif ($options[$part['SourceId']]['method'] == 'feathered') {
                $featheredRange      = $options[$part['SourceId']]['feathered_range'];
                $featheredSampleSize = $options[$part['SourceId']]['feathered_step'];

                $this->common_model->execute_query('SET @row_num = 0');
                $this->common_model->execute_query('SET @max_distance = ' . $featheredRange);
                $this->common_model->execute_query('SET @total_rows = (SELECT COUNT(*) FROM application_counts_prospects WHERE Distance <= @max_distance AND CountId = ' . $part['CountId'] . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected = 1)');
                $this->common_model->execute_query('SET @step_size = @total_rows / ' . $featheredSampleSize);

                $feathered_sql   = [];
                $feathered_sql[] = 'SELECT ' . implode(',', array_keys($columns)) . ' FROM (';
                $feathered_sql[] = '    SELECT (@row_num := @row_num + 1) AS row_num, ' . implode(',', array_keys($columns));
                $feathered_sql[] = '    FROM application_counts_prospects';
                $feathered_sql[] = '    WHERE Distance <= @max_distance AND CountId = ' . $part['CountId'] . ' AND SourceId = ' . $part['SourceId'] . ' AND flag_selected = 1';
                $feathered_sql[] = '    ORDER BY Distance';
                $feathered_sql[] = ') AS numbered_rows';
                $feathered_sql[] = 'WHERE MOD(row_num, @step_size) < 1';
                $feathered_sql[] = 'LIMIT ' . $featheredSampleSize;

                $feathered_sql = implode(' ', $feathered_sql);

                $res = $this->common_model->execute_query($feathered_sql);

                while ($row = mysql_fetch_assoc($res)) {
                    fputcsv($fp, $row);
                }
            } elseif ($options[$part['SourceId']]['method'] == 'zipcode') {
                foreach ($options[$part['SourceId']]['zipcode'] as $zip => $limit) {
                    $sql_zipcode   = $sql;
                    $sql_zipcode[] = 'AND Zip = ' . $zip;
                    $sql_zipcode[] = 'ORDER BY Id';
                    $sql_zipcode[] = 'LIMIT ' . $limit;

                    $sql_zipcode = implode(' ', $sql_zipcode);

                    $res = $this->common_model->execute_query($sql_zipcode);

                    while ($row = mysql_fetch_assoc($res)) {
                        fputcsv($fp, $row);
                    }
                }
            }
        }

        fclose($fp);

        return true;
    }

    public function export_phone_report($countId)
    {
        $this->authorizePostRequests();

        if (!$count = $this->counts_model->getCount($countId)) {
            return $this->renderNotFound();
        }

        $range = $this->input->post('range');

        $records = $this->counts_model->getCountPhoneReport($countId, $range);

        $fp = fopen('php://output', 'wb');

        $filename = 'Count (Phones) ' . $countId . ' - ' . ($count['client__Name'] ?: $count['Zip']) . ' - ' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($fp, array_keys($records[0]));

        foreach ($records as $record) {
            fputcsv($fp, $record);
        }

        fclose($fp);

        return true;
    }

    public function export_report($countId)
    {
        $this->authorizeGetRequests();

        if (!$this->counts_model->existsCount($countId)) {
            return $this->renderNotFound();
        }

        $count = $this->counts_model->getCount($countId);

        try {
            $file     = $this->counts_model->generateCountExcelReport($count['Id']);
            $filename = $this->counts_model->filenameCountReport($count['Id']);
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        // Download the spreadsheet
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Read the file and output its content
        readfile($file);

        return true;
    }

    public function export_suppression_all($countId)
    {
        $this->authorizeGetRequests();

        if (!$this->counts_model->existsCount($countId)) {
            return $this->renderNotFound();
        }

        $count = $this->counts_model->getCount($countId);

        $filename = $this->counts_model->filenameCountSuppressionAll($count['Id']);
        $file     = $this->counts_model->generateCountSuppressionsAllCsv($count['Id']);

        // Download the spreadsheet
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Read the file and output its content
        readfile($file);

        return true;
    }

    public function export_suppression_selected($countId)
    {
        $this->authorizeGetRequests();

        if (!$this->counts_model->existsCount($countId)) {
            return $this->renderNotFound();
        }

        $count = $this->counts_model->getCount($countId);

        $filename = $this->counts_model->filenameCountSuppressionSelected($count['Id']);
        $file     = $this->counts_model->generateCountSuppressionsSelectedCsv($count['Id']);

        // Download the spreadsheet
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Read the file and output its content
        readfile($file);

        return true;
    }

    public function feeds()
    {
        $this->authorizeGetRequests();
        $this->authorizeAdmins();

        $data = [
            'feeds' => $this->counts_model->getFeeds(),
        ];

        return $this->renderLayout('counts/feeds', $data);
    }

    public function mark_for_export($countId)
    {
        $this->authorizePostRequests();

        $options = $this->input->post('options');

        $sql = 'UPDATE application_counts SET ExportedOptions = "' . mysql_escape_string(json_encode($options)) . '" WHERE Id = "' . $countId . '"';
        $this->common_model->execute_query($sql);

        if ($this->input->post('navigation') === 'request') {
            $this->counts_model->notifyExportRequested($countId);
        }

        // Prepare the response data
        $data = [
            'count_id' => $countId,
            'message'  => $this->counts_model->getCountSystemStatus($countId),
            'locked'   => $this->counts_model->isCountLocked($countId),
        ];

        return $this->renderJson($data);
    }

    public function notify($countId)
    {
        try {
            $this->counts_model->notifyReady($countId);
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        return $this->returnRedirectBack();
    }

    public function preview($countId, $sourceId)
    {
        $part = [
            'CountId'  => $countId,
            'SourceId' => $sourceId,
        ];

        foreach (counts_model::COUNT_FILTERS as $field) {
            $part[$field] = $this->input->get($field);
        }

        $data = [
            'part'       => $part,
            'statistics' => $this->counts_model->getCountStatistics($countId, $sourceId, $part),
            'breakdowns' => $this->counts_model->getCountBreakdowns($countId, $sourceId, $part),
            'columns'    => counts_model::COLUMNS_EXPORT,
            'records'    => $this->counts_model->getCountProspects($countId, $sourceId, $part),
            'sql'        => $this->counts_model->getCountProspectsSql($countId, $sourceId, $part),
        ];

        return $this->renderLayout('counts/preview', $data);
    }

    public function preview_email($countId)
    {
        $this->authorizeGetRequests();

        if (!$this->counts_model->existsCount($countId)) {
            return $this->renderNotFound();
        }

        $body = $this->counts_model->generateEmailBody($countId);

        return $this->renderText($body);
    }

    public function redistance($countId)
    {
        $this->authorizePostRequests();

        $this->counts_model->updateCountProspectsDistance($countId);

        return $this->returnRedirect(__CLASS__, 'edit', $countId);
    }

    public function set_status_requested($countId)
    {
        $this->authorizePostRequests();

        $this->counts_model->updateCountUserIdsCopied($countId, $this->input->post('UserIdsCopied'));
        $this->counts_model->updateCountSpecialInstructions($countId, $this->input->post('SpecialInstructions'));
        $this->counts_model->updateCountStatus($countId, counts_model::COUNT_STATUS_REQUESTED);

        $this->counts_model->notifyNcoaRequested($countId);

        return $this->returnRedirect(__CLASS__, 'view', $countId);
    }

    public function store()
    {
        $this->authorizePostRequests();

        if (!$this->validatePost([
            'ClientId'       => 'required|integer',
            'Zip'            => 'required|min:5|max:5',
            'UseDms'         => 'required|integer|in:0,1',
            'UseDatasets'    => 'required|integer|in:0,1',
            'SuppressRecent' => 'required|integer|in:0,1',
            'UserId'         => 'required|integer',
        ])) {
            return $this->returnAjaxLastValidationError();
        }

        $client_id       = $this->input->post('ClientId');
        $zip             = $this->input->post('Zip');
        $use_dms         = $this->input->post('UseDms');
        $use_datasets    = $this->input->post('UseDatasets');
        $suppress_recent = $this->input->post('SuppressRecent');
        $user_id         = $this->input->post('UserId');
        $dms_table       = $this->input->post('DmsTable');

        // Create the count
        try {
            $count_id = $this->counts_model->createCount($zip, $client_id, $user_id);
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        // Lock the count
        $this->counts_model->lockCount($count_id);

        // Prepare the response data
        $data = [
            'count_id' => $count_id,
            'message'  => $this->counts_model->getCountSystemStatus($count_id),
            'locked'   => $this->counts_model->isCountLocked($count_id),
        ];

        // Respond early to the browser and continue execution
        $this->sendResponseEarly($data);

        // Suppress recents if necessary
        if ($suppress_recent) {
            $this->counts_model->createCountSuppressionsFromRecentlyContactedProspects($count_id);
        }

        // Initialize inputs
        $inputs = [
            'UseDms'   => $use_dms,
            'DmsTable' => $dms_table,
        ];

        // Determine if datasets will be used
        if ($use_datasets) {
            $inputs['_include_sets'] = $this->counts_model->getClientDatasetIds($client_id);
        }

        // Update the count
        try {
            $this->counts_model->updateCount($count_id, $inputs);
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        // Set initial selections
        $this->counts_model->updateCountSelectionsToDefault($count_id);

        // Set count status message
        $this->counts_model->updateCountStatusMessage($count_id, 'Ready');

        // Unlock the count
        $this->counts_model->unlockCount($count_id);

        return $this->returnAjaxSuccess();
    }

    public function system()
    {
        $this->authorizeGetRequests();

        $Accutrace = new Accutrace();

        try {
            $Accutrace->useSubscriptionKey();
            $ncoaResponse['Subscription'] = $Accutrace->account();

            $Accutrace->useTransactionKey();
            $ncoaResponse['Transaction'] = $Accutrace->account();
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        $data = [
            'ncoa'  => $ncoaResponse,
            'stats' => array_keys(self::SYSTEM_STATS_QUERIES),
        ];

        return $this->renderLayout('counts/system', $data);
    }

    public function system_stats_part($part)
    {
        $systsem_stats_queries = array_values(self::SYSTEM_STATS_QUERIES);

        $sql = $systsem_stats_queries[$part];

        $sql = str_replace('__active_cids__', formatArrayToCsv($this->sales_service_model->getClientIdsWithActiveDmsFeeds()), $sql);

        $count = $this->common_model->single_result_field($sql, 'count');

        return $this->renderText($count);
    }

    public function update($countId)
    {
        $this->authorizePostRequests();

        // Lock the count
        $this->counts_model->lockCount($countId);

        // Prepare the response data
        $data = [
            'count_id' => $countId,
            'message'  => $this->counts_model->getCountSystemStatus($countId),
            'locked'   => $this->counts_model->isCountLocked($countId),
        ];

        // Respond early to the browser and continue execution
        $this->sendResponseEarly($data);

        // Start debugger
        $this->counts_model->debug('Start');

        // Prepare data for update
        $data = [
            'UseDms'                   => $this->input->post('UseDms'),
            '_include_sets'            => $this->input->post('_include_sets'),
            '_suppress_jobs'           => $this->input->post('_suppress_jobs'),
            '_suppress_responses'      => $this->input->post('_suppress_responses'),
            '_suppress_counts'         => $this->input->post('_suppress_counts'),
            '_suppress_file'           => isset($_FILES['_suppress_file']['tmp_name']) ? $_FILES['_suppress_file']['tmp_name'] : null,
            '_suppress_file_operation' => $this->input->post('_suppress_file_operation'),
            '_part_filter'             => $this->input->post('_part_filter'),
        ];

        // Update the count
        try {
            $this->counts_model->updateCount($countId, $data);
        } catch (Exception $e) {
            return $this->renderException($e);
        }

        // Save the last debug frame
        $this->counts_model->debugSave($countId);

        // Unlock the count
        $this->counts_model->unlockCount($countId);

        // End debugger
        $this->counts_model->debug('End');

        // Set message
        $this->counts_model->updateCountStatusMessage($countId, 'Ready');

        if ($this->input->post('navigation') == 'continue') {
            return $this->returnRedirect(__CLASS__, 'view', $countId);
        }

        return $this->returnRedirect(__CLASS__, 'edit', $countId);
    }

    public function view($countId)
    {
        $this->authorizeGetRequests();

        if (!$this->counts_model->existsCount($countId)) {
            return $this->renderNotFound();
        }

        // Start debugger
        $this->counts_model->debug('Start');

        $count = $this->counts_model->getCount($countId);

        $count['ExportedOptions'] = $this->jsonDecodeOrEmptyArray($count['ExportedOptions']);

        $data = [
            'count'        => $count,
            'parts'        => $this->counts_model->getCountParts($countId),
            'statistics'   => $this->counts_model->getCountStatistics($countId),
            'breakdowns'   => $this->counts_model->getCountBreakdowns($countId),
            'all_by_zip'   => $this->counts_model->getCountSelectionZipcodeStatistics($countId),
            'users'        => $this->counts_model->getUsers(),
            'ncoa_summary' => $this->counts_model->getCountNcoaSummary($countId),
        ];

        foreach ($data['parts'] as $part) {
            $data['statistics_parts'][$part['SourceId']] = $this->counts_model->getCountStatistics($count['Id'], $part['SourceId']);
            $data['breakdowns_parts'][$part['SourceId']] = $this->counts_model->getCountBreakdowns($count['Id'], $part['SourceId']);
        }

        // End debugger
        $this->counts_model->debug('End');

        $data['debug_last']    = json_decode($count['Debug'], true);
        $data['debug_current'] = $this->counts_model->debugGetCurrent();

        return $this->renderLayout('counts/view', $data);
    }

    private function jsonDecodeOrEmptyArray($value)
    {
        try {
            $decoded = json_decode($value, true);

            if (!is_array($decoded)) {
                $decoded = [];
            }
        } catch (Exception $e) {
            $decoded = [];
        }

        return $decoded;
    }

    private function sendResponseEarly($data)
    {
        ob_start();

        /** @noinspection CustomRegExpInspection */
        echo json_encode($data);

        header('Connection: close');
        header('Content-Length: ' . ob_get_length());

        ob_end_flush();
        @ob_flush();
        flush();

        fastcgi_finish_request();
    }
}

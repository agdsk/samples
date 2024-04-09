<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenerateExcelExport extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public function __construct($file_path)
    {
        $this->file_path = $file_path;
    }

    public function handle()
    {

        $objPHPExcel = new \PHPExcel();

        \PHPExcel_Settings::setCacheStorageMethod(\PHPExcel_CachedObjectStorageFactory::cache_to_sqlite);

        $objPHPExcel->removeSheetByIndex(0);

        $tables = ['locations', 'reservations', 'brands'];

        set_time_limit(0);

        ini_set('memory_limit', '2G');

        foreach ($tables as $index => $table) {
            // Create Worksheet
            $objPHPExcel->createSheet($index);

            // Select the sheet
            $objPHPExcel->setActiveSheetIndex($index);

            // Name the sheet
            $objPHPExcel->getActiveSheet()->setTitle($table);

            // Get columns as an array
            $columns = Schema::getColumnListing($table);

            // Add column names to Worksheet
            $objPHPExcel->getActiveSheet()->fromArray($columns, null, 'A1');

            $records = DB::table($table)->select('*')->get();

            foreach ($records as $k => $record) {
                $records[$k] = get_object_vars($record);
            }

            $objPHPExcel->getActiveSheet()->fromArray($records, null, 'A2');
        }

        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        $objWriter->save($this->file_path);
    }
}

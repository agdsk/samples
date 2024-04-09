<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Jobs\GenerateExcelExport;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        list($dates, $statistics) = $this->getData();

        $data = [
            'dates'      => $dates,
            'statistics' => $statistics,
            'Locations'  => Location::with('Brand')->get(),
        ];

        return view('stats/index', $data);
    }

    public function download2()
    {
        $file_path = storage_path('app/Export.xls');
        dispatch(new GenerateExcelExport($file_path));

        $filename = "Acme Live Statistics as of " . date('Y-m-d') . '.xlsx';

        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo file_get_contents($file_path);

        exit;
    }

    public function download()
    {
        list($dates, $statistics) = $this->getData();

        foreach ($statistics as $location_id => $location_dates) {
            foreach ($location_dates as $date => $tuples) {
                $statistics[$location_id][$date] = $tuples['total'];
            }
        }

        $friendly_dates = $dates;

        foreach (array_keys($friendly_dates) as $k) {
            $friendly_dates[$k] = date('n/j', strtotime($friendly_dates[$k]));
        }

        $columns = array_merge(['ID', 'Brand', 'Name', 'Store #', 'City', 'Region', 'Country'], $friendly_dates);

        $fp = fopen('/tmp/file.csv', 'w');
        fputcsv($fp, $columns);

        $Locations = Location::with('Brand')->get();

        foreach ($Locations as $Location) {
            $fields = [
                $Location->id,
                $Location->Brand->name,
                $Location->name,
                $Location->vendor_id,
                $Location->city,
                $Location->region,
                $Location->country,
            ];

            $fields = array_merge($fields, $statistics[$Location->id]);

            fputcsv($fp, $fields);
        }

        fclose($fp);

        $filename = "Acme Live Statistics as of " . date('Y-m-d') . '.csv';

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo file_get_contents('/tmp/file.csv');

        exit;
    }

    private function getData()
    {
        $date_first   = new \DateTime(array_first(DB::table('reservations')->select('date')->orderBy('date', 'ASC')->limit(1)->pluck('date')));
        $date_last    = new \DateTime(array_first(DB::table('reservations')->select('date')->orderBy('date', 'DESC')->limit(1)->pluck('date')));
        $location_ids = DB::table('locations')->select('id')->pluck('id');

        // Fetch statistical data
        $stats_query = DB::table('reservations')->select(
            'location_id',
            'source',
            'date',
            DB::Raw('COUNT(*) as count')
        )->groupBy('location_id', 'date', 'source')->get();

        // Clone start date as a cursor
        $date_cursor = clone $date_first;
        $dates       = [];

        while ($date_cursor <= $date_last) {
            $dates[] = $date_cursor->format('Y-m-d');
            $date_cursor->add(new \DateInterval('P1D'));
        }

        // Build output
        $statistics = array_fill_keys($location_ids, []);

        foreach (array_keys($statistics) as $k) {
            $statistics[$k] = array_fill_keys($dates, ['walkup' => 0, 'website' => 0, 'total' => 0]);
        }

        foreach ($stats_query as $dp) {
            $statistics[$dp->location_id][$dp->date][$dp->source] = $dp->count;
            $statistics[$dp->location_id][$dp->date]['total'] += $dp->count;
        }

        return [$dates, $statistics];
    }
}

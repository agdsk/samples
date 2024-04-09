<?php

namespace AppBundle\Command;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

use Carbon\Carbon;

use AppBundle\Model\User;
use AppBundle\Model\Session;
use AppBundle\Model\Course;
use AppBundle\Model\Location;
use AppBundle\Model\Enrollment;

class PrplImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('prpl:import')->setDescription('Import data from AHA drupal database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->root_dir = $this->getApplication()->getKernel()->getRootDir();
        $this->output   = $output;

        $output->writeln('<comment>Checking for CSV files</comment>');
        $filesAreMissing = $this->checkDbFiles();

        if (!$filesAreMissing) {
            $output->writeln('<comment>Importing Users</comment>');
            $users = $this->importUsers();

            $output->writeln('<comment>Importing Sessions</comment>');
            $sessions = $this->importSessions();

            $output->writeln('<comment>Associating Users with Sessions</comment>');
            $this->associate($users, $sessions);
        }
    }

    private function checkDbFiles()
    {
        $fileIsMissing = false;

        $files = [
            'users',
            'uc_order_products',
            'content_field_event_date',
            'uc_orders',
            'uc_order_admin_comments',
            'uc_extra_fields_values',
            'uc_zones',
        ];

        foreach ($files as $file) {
            if (!file_exists($this->root_dir . '/../db/' . $file . '.csv')) {
                $fileIsMissing = true;
                $this->output->writeln('<error>"' . $file . '.csv" is missing</error>');
            }
        }

        if (!$fileIsMissing) {
            $this->output->writeln('<info>All CSV files exist</info>' . PHP_EOL);
        }

        return $fileIsMissing;
    }

    private function importUsers()
    {
        $users      = array_map('str_getcsv', file($this->root_dir . '/../db/users.csv'));
        $user_array = [];

        $email_array = [];

        $progress = new ProgressBar($this->output, count($users));
        $progress->start();

        foreach ($users as $key => $user) {
            if ($key > 0 && $user[1] != '') {
                if (!in_array(strtolower($user[3]), $email_array)) {
                    $User = User::firstOrNew(['email' => $user[3]]);

                    $User->email = $user[3];
                    $User->opid  = $user[1];
                    $User->Save();

                    $user_array[$user[0]] = [
                        'opid'  => $user[1],
                        'email' => $user[3],
                        'user'  => $User,
                    ];

                    $email_array[] = strtolower($user[3]);
                }
            }

            $progress->setProgress($key);
        }

        $progress->finish();
        $this->output->writeln(PHP_EOL);

        return $user_array;
    }

    private function importSessions()
    {
        $dates = array_map('str_getcsv', file($this->root_dir . '/../db/content_field_event_date.csv'));

        $sessions      = array_map('str_getcsv', file($this->root_dir . '/../db/uc_order_products.csv'));
        $session_array = [];

        $progress = new ProgressBar($this->output, count($sessions));
        $progress->start();

        foreach ($sessions as $key => $session) {
            $progress->setProgress($key);

            if ($key > 0) {
                if ($session[2] != 0 && $this->getCourseId($session[3]) != 0) {
                    $session_dates = $this->getDates($session[2], $dates);

                    if (count($session_dates) < 1) {
                        $date1     = Carbon::parse('now');
                        $date1_end = Carbon::parse('now');
                    } else {
                        $date1     = Carbon::parse($session_dates[0][0]);
                        $date1_end = Carbon::parse($session_dates[0][1]);
                    }

                    $Session = Session::firstOrNew(['course_id' => $this->getCourseId($session[3]), 'date1' => $date1->toDateString(), 'start_time1' => $date1->toTimeString()]);

                    $Session->date1       = $date1->toDateString();
                    $Session->start_time1 = $date1->toTimeString();
                    $Session->end_time1   = $date1_end->toTimeString();

                    if (count($session_dates) > 1) {
                        if (count($session_dates) < 1) {
                            $date2     = Carbon::parse('0000-00-00 00:00:00');
                            $date2_end = Carbon::parse('0000-00-00 00:00:00');
                        } else {
                            $date2     = Carbon::parse($session_dates[1][0]);
                            $date2_end = Carbon::parse($session_dates[1][1]);
                        }

                        $Session->date2       = $date2;
                        $Session->start_time2 = $date2->toTimeString();
                        $Session->end_time2   = $date2_end->toTimeString();
                    }

                    $Course = Course::find($this->getCourseId($session[3]));
                    $Session->Course()->associate($Course);
                    $Session->Location()->associate(Location::find(1));

                    $Session->student_max         = $Session->Course->default_student_max;
                    $Session->instructor_max      = $Session->Course->default_instructor_max;
                    $Session->online_registration = false;
                    $Session->public              = false;

                    $Session->save();

                    $session_array[$session[1]] = $Session;
                }
            }
        }

        $progress->finish();
        $this->output->writeln(PHP_EOL);

        return $session_array;
    }

    private function associate($users, $sessions)
    {
        // the orders
        $orders      = array_map('str_getcsv', file($this->root_dir . '/../db/uc_orders.csv'));
        $order_array = [];

        // the cart items
        $order_products       = array_map('str_getcsv', file($this->root_dir . '/../db/uc_order_products.csv'));
        $order_products_array = [];

        // the order notes
        $order_comments      = array_map('str_getcsv', file($this->root_dir . '/../db/uc_order_admin_comments.csv'));
        $order_comment_array = [];

        // the cost centers and employee ids
        $extra_fields       = array_map('str_getcsv', file($this->root_dir . '/../db/uc_extra_fields_values.csv'));
        $cost_centers_array = [];
        $employee_id_array  = [];

        // the states
        $states       = array_map('str_getcsv', file($this->root_dir . '/../db/uc_zones.csv'));
        $states_array = [];

        // get all the states from the zones table
        foreach ($states as $key => $state) {
            if ($key > 0) {
                $states_array[$state[0]] = $state[3];
            }
        }
        unset($states);

        // get an array of orders
        foreach ($orders as $key => $order) {
            if ($key > 0) {
                $order_array[$order[0]] = [
                    'uid'        => $order[1],
                    'cost'       => $order[3],
                    'status'     => $order[2],
                    'first_name' => $order[6],
                    'last_name'  => $order[7],
                    'address1'   => $order[10],
                    'address2'   => $order[11],
                    'city'       => $order[12],
                    'state'      => null,
                    'zip'        => $order[14],
                    'country'    => $order[15],
                ];

                if (array_key_exists($order[13], $states_array)) {
                    $order_array[$order[0]]['state'] = $states_array[$order[13]];
                }
            }
        }
        unset($orders); // for freeing memory

        // get an array of cart items with the order_id as the key
        // and the cost as the value
        foreach ($order_products as $key => $order_product) {
            if ($key > 0) {
                $order_products_array[$order_product[1]] = $order_product[8];
            }
        }
        unset($order_products);

        // get an array of cost centers
        foreach ($extra_fields as $key => $extra_field) {
            if ($key > 0) {
                if ($extra_field[2] == '9') {
                    $cost_centers_array[$key] = $extra_field[3];
                }

                if ($extra_field[2] == '1') {
                    $employee_id_array[$key] = $extra_field[3];
                }
            }
        }
        unset($cost_centers);

        // get all the notes and concat them
        foreach ($order_comments as $key => $order_comment) {
            if ($key > 0) {
                if (count($order_comment) > 4 && $order_comment[3] != 'Order created through website.') {
                    if (!array_key_exists($order_comment[1], $order_comment_array)) {
                        $order_comment_array[$order_comment[1]] = [];
                    }
                    $order_comment_array[$order_comment[1]][] = strip_tags($order_comment[3]);
                }
            }
        }

        // put the progress bar on the screen
        $progress = new ProgressBar($this->output, count($order_products_array));
        $progress->start();

        foreach ($order_products_array as $key => $session_cost) {
            $order_id = $key;
            $order    = $order_array[$order_id];
            $uid      = $order['uid'];

            $User = null;
            if (array_key_exists($uid, $users)) {
                $User = $users[$uid]['user'];
            }

            $Session = null;
            if (array_key_exists($order_id, $sessions)) {
                $Session = $sessions[$order_id];
            }

            if ($Session != null && $User != null) {
                $Enrollment = Enrollment::firstOrNew(['user_id' => $User->id, 'session_id' => $Session->id]);

                $User->setAttribute('first_name', $order['first_name']);
                $User->setAttribute('last_name', $order['last_name']);
                $User->setAttribute('address1', $order['address1']);
                $User->setAttribute('address2', $order['address2']);
                $User->setAttribute('city', $order['city']);
                $User->setAttribute('state', $order['state']);
                $User->setAttribute('zip', $order['zip']);

                if ($order['country'] == '124') {
                    $User->setAttribute('country', 'CA');
                } else {
                    $User->setAttribute('country', 'US');
                }

                if (array_key_exists($order_id, $employee_id_array)) {
                    $User->setAttribute('employee_id', $employee_id_array[$order_id]);
                }

                $User->save();

                $Enrollment->User()->associate($User);
                $Enrollment->Session()->associate($Session);
                $Enrollment->setAttribute('cost', $session_cost);
                $Enrollment->setAttribute('tag', $order['status']);

                switch ($order['status']) {
                    case 'canceled':
                        $Enrollment->setAttribute('status', 'registered');
                        break;
                    case 'cancelled_no_show':
                        $Enrollment->setAttribute('status', 'cancelled');
                        $Enrollment->setAttribute('attendance', 'no show');
                        break;
                    case 'completed':
                        $Enrollment->setAttribute('status', 'registered');
                        $Enrollment->setAttribute('attendance', 'show');
                        $Enrollment->setAttribute('grade', 'pass');
                        break;
                    case 'completed_cc':
                        $Enrollment->setAttribute('status', 'registered');
                        $Enrollment->setAttribute('attendance', 'show');
                        $Enrollment->setAttribute('grade', 'pass');
                        $Enrollment->setAttribute('payment_method', 'card');
                        break;
                    case 'completed_idt':
                        $Enrollment->setAttribute('status', 'registered');
                        $Enrollment->setAttribute('attendance', 'show');
                        $Enrollment->setAttribute('grade', 'pass');
                        $Enrollment->setAttribute('payment_method', 'unit');
                        break;
                    case 'completed_noshow':
                        $Enrollment->setAttribute('status', 'registered');
                        $Enrollment->setAttribute('attendance', 'no show');
                        break;
                    case 'completed_ppd':
                        $Enrollment->setAttribute('status', 'registered');
                        $Enrollment->setAttribute('attendance', 'show');
                        $Enrollment->setAttribute('grade', 'pass');
                        break;
                    case 'completed_refund':
                        $Enrollment->setAttribute('status', 'cancelled');
                        $Enrollment->setAttribute('payment_method', 'card');
                        break;
                    case 'partial_payment_due':
                        $Enrollment->setAttribute('status', 'pending');
                        break;
                    case 'payment_received':
                        $Enrollment->setAttribute('status', 'registered');
                        break;
                    case 'payment_received_but_not_rcvd':
                        $Enrollment->setAttribute('status', 'pending');
                        $Enrollment->setAttribute('payment_method', 'unit');
                        break;
                    case 'pending':
                        $Enrollment->setAttribute('status', 'pending');
                        break;
                    case 'removed':
                        $Enrollment->setAttribute('status', 'cancelled');
                        break;
                    default:
                        $Enrollment->setAttribute('status', 'registered');
                        break;
                }

                if (array_key_exists($order_id, $cost_centers_array)) {
                    $Enrollment->setAttribute('cost_center', $cost_centers_array[$order_id]);
                }

                $notes = '';
                if (array_key_exists($order_id, $order_comment_array)) {
                    foreach ($order_comment_array[$order_id] as $message) {
                        $notes .= $message . ' ';
                    }
                }
                $notes = trim($notes);

                $Enrollment->setAttribute('note_admin', $notes);

                $Enrollment->save();
            }

            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln(PHP_EOL);
    }

    private function getDates($nid, &$dates)
    {
        $date_arr = [];

        foreach ($dates as $date) {
            if ($nid == $date[1]) {
                $date_arr[] = [$date[2], $date[3]];
            }
        }

        return $date_arr;
    }

    private function getCourseId($title)
    {
        $title = trim($title);

        $dictionary = [
            'ACLS Exp Provider Course'                                 => 5,
            'ACLS Exp Provider Instructor Training Course'             => 7,
            'ACLS Fast Track'                                          => 4,
            'ACLS Instructor Course'                                   => 6,
            'ACLS Prep Course'                                         => 1,
            'ACLS Provider Training Course'                            => 2,
            'ACLS Retrain Course'                                      => 3,
            'BLS Instructor Training Course'                           => 9,
            'BLS Provider Training Course'                             => 8,
            'ENPC Instructor Course'                                   => 28,
            'Emergency Nurse Pediatric Course (ENPC)'                  => 19,
            'Heart Saver CPR & AED'                                    => 11,
            'Heart Saver CPR AED & First Aid Course'                   => 13,
            'Heart Saver Instructor Course'                            => 15,
            'Heart Saver/First Aid'                                    => 12,
            'NPR_Online_Checkoff_Session 1'                            => 17,
            'NRP CHECK OFF SESSION'                                    => 17,
            'NRP Instructor Course'                                    => 18,
            'NRP Provider Online Course with Skills Check-off Session' => 16,
            'PALS Fast Track Course'                                   => 25,
            'PALS Instructor Training Course'                          => 26,
            'PALS Provider Training Course'                            => 23,
            'PALS Retrain Course'                                      => 24,
            'PEARS Course'                                             => 22,
            'STABLE'                                                   => 27,
            'TNCC Instructor Course'                                   => 21,
            'Trauma Nursing Core Course TNCC'                          => 20,
        ];

        return $dictionary[$title];
    }
}

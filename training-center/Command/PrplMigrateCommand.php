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
use AppBundle\Model\UserCertification;

use \PDO;

class PrplMigrateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('prpl:migrate')->setDescription('Migrate data from AHA drupal database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->root_dir = $this->getApplication()->getKernel()->getRootDir();
        $this->output   = $output;

        $output->writeln('<comment>Establishing database connection</comment>');
        $this->db = new PDO('mysql:host=localhost;dbname=aha_prod;charset=utf8mb4', 'root', '');

        $output->writeln('<comment>Importing users</comment>');
        $this->importUsers();

        $output->writeln('<comment>Importing sessions</comment>');
        $this->importSessions();

        $output->writeln('<comment>Importing enrollments</comment>');
        $this->importEnrollments();
    }

    private function importUsers()
    {
        $sql = '
            SELECT
                users.uid                      AS old_id,
                users.name                     AS opid,
                users.mail                     AS email,
                uc_orders.delivery_first_name  AS first_name,
                uc_orders.delivery_last_name   AS last_name,
                uc_orders.delivery_phone       AS phone,
                uc_orders.delivery_street1     AS address1,
                uc_orders.delivery_street2     AS address2,
                uc_orders.delivery_city        AS city,
                uc_orders.delivery_country     AS country,
                uc_zones.zone_code             AS state,
                uc_orders.delivery_postal_code AS zip,
                uc_extra_fields_values.value   AS employee_id
                FROM users
                LEFT OUTER JOIN uc_orders              ON users.uid                         = uc_orders.uid
                LEFT OUTER JOIN uc_extra_fields_values ON uc_extra_fields_values.element_id = uc_orders.order_id AND field_id = 1
                LEFT OUTER JOIN uc_zones               ON uc_orders.delivery_zone           = uc_zones.zone_id   
                GROUP BY users.uid
        ';

        $sth = $this->db->query($sql);

        $progress = new ProgressBar($this->output, $sth->rowCount());
        $progress->start();

        while ($record = $sth->fetch(PDO::FETCH_ASSOC)) {
            $progress->advance();

            $record               = array_map('trim', $record);
            $record['email']      = strtolower($record['email']);
            $record['first_name'] = ctype_lower($record['first_name']) ? ucfirst($record['first_name']) : $record['first_name'];
            $record['last_name']  = ctype_lower($record['last_name']) ? ucfirst($record['last_name']) : $record['last_name'];
            $record['opid']       = strtoupper($record['opid']);

            $User             = User::firstOrNew(['email' => $record['email']]);
            $User->first_name = $record['first_name'];
            $User->last_name  = $record['last_name'];
            $User->opid       = $record['opid'];
            $User->email      = $record['email'];
            $User->phone      = $record['phone'];
            $User->address1   = $record['address1'];
            $User->address2   = $record['address2'];
            $User->city       = $record['city'];
            $User->state      = $record['state'];
            $User->zip        = $record['zip'];

            if ($record['country'] == '') {
                $User->country = '';
            } elseif ($record['country'] == '0') {
                $User->country = '';
            } elseif ($record['country'] == '124') {
                $User->country = 'CA';
            } elseif ($record['country'] == '840') {
                $User->country = 'US';
            } else {
                throw new \Exception('Unknown country code ' . $record['country']);
            }

            $User->Save();
        }

        $progress->finish();
        $this->output->writeln(PHP_EOL);
    }

    private function importSessions()
    {
        $sql = '
            SELECT
                uc_products.vid AS old_id,
                uc_products.nid,
                node.title AS course
                FROM uc_products
                LEFT JOIN node ON uc_products.nid = node.nid
        ';

        $sth = $this->db->query($sql);

        $progress = new ProgressBar($this->output, $sth->rowCount());
        $progress->start();

        while ($record = $sth->fetch(PDO::FETCH_ASSOC)) {
            $progress->advance();

            $record    = array_map('trim', $record);
            $course_id = $this->getCourseId($record['course']);

            if ($course_id == null) {
                continue;
            }

            $sql = '
                SELECT
                    field_event_date_value  AS start,
                    field_event_date_value2 AS end
                    FROM content_field_event_date
                    WHERE nid=:nid
            ';

            $sth2 = $this->db->prepare($sql);
            $sth2->bindValue(':nid', $record['nid']);
            $sth2->execute();
            $dates = $sth2->fetchAll();

            $first_imported_date = Carbon::parse($dates[0]['start'], new \DateTimeZone('UTC'));
            $first_imported_date->setTimezone(new \DateTimeZone('America/New_York'));

            $second_imported_date = Carbon::parse($dates[0]['end'], new \DateTimeZone('UTC'));
            $second_imported_date->setTimezone(new \DateTimeZone('America/New_York'));

            $Session  = Session::firstOrNew(['course_id' => $course_id, 'date1' => $first_imported_date->toDateString(), 'start_time1' => $first_imported_date->toTimeString()]);
            $Course   = Course::find($course_id);
            $Location = Location::find(1);

            $Session->Course()->associate($Course);
            $Session->Location()->associate($Location);
            $Session->student_max    = $Course->default_student_max;
            $Session->instructor_max = $Course->default_instructor_max;
            $Session->old_id         = $record['old_id'];

            if ($first_imported_date->toDateString() == $second_imported_date->toDateString()) {
                $Session->date1       = $first_imported_date->toDateString();
                $Session->start_time1 = $first_imported_date->toTimeString();
                $Session->end_time1   = $second_imported_date->toTimeString();
            } else {
                $Session->date1       = $first_imported_date->toDateString();
                $Session->start_time1 = $first_imported_date->toTimeString();
                $Session->end_time1   = $Course->default_end_time1;

                $Session->date2       = $second_imported_date->toDateString();
                $Session->start_time2 = $Course->default_start_time2;
                $Session->end_time2   = $Course->default_end_time2;
            }

            $Session->save();
        }

        $progress->finish();
        $this->output->writeln(PHP_EOL);
    }

    private function importEnrollments()
    {

        Capsule::statement('SET FOREIGN_KEY_CHECKS=0;');
        Capsule::table('enrollments')->truncate();
        Capsule::statement('SET FOREIGN_KEY_CHECKS=1;');

        $sql = "
            SELECT
            users.mail                         AS email,
            uc_order_products.order_product_id AS old_enrollment_id,
            uc_order_products.model            AS model,
            uc_products.vid                    AS old_session_id,
            uc_orders.order_status             AS status,
            uc_payment_po.po_number            AS cost_center_preferred,
            efv_cost_center.value              AS cost_center_fallback,
            efv_employee_id.value              AS employee_id,
            efv_license_number.value           AS license_number
            FROM uc_order_products
            LEFT JOIN uc_orders           ON uc_order_products.order_id = uc_orders.order_id
            LEFT JOIN uc_products         ON uc_order_products.nid      = uc_products.nid
            LEFT JOIN uc_payment_po       ON uc_order_products.order_id = uc_payment_po.order_id
            LEFT JOIN uc_payment_receipts ON uc_order_products.order_id = uc_payment_receipts.order_id
            LEFT JOIN users               ON uc_orders.uid              = users.uid
            LEFT JOIN uc_extra_fields_values AS efv_employee_id    ON uc_orders.order_id = efv_employee_id.element_id    AND efv_employee_id.field_id     = '1'
            LEFT JOIN uc_extra_fields_values AS efv_license_number ON uc_orders.order_id = efv_license_number.element_id AND efv_license_number.field_id  = '5'
            LEFT JOIN uc_extra_fields_values AS efv_cost_center    ON uc_orders.order_id = efv_cost_center.element_id    AND efv_cost_center.field_id     = '9'
            GROUP BY uc_order_products.order_product_id
        ";

        $sth = $this->db->query($sql);

        $progress = new ProgressBar($this->output, $sth->rowCount());
        $progress->start();

        $skipped_records = 0;

        while ($record = $sth->fetch(PDO::FETCH_ASSOC)) {
            $progress->advance();

            $record          = array_map('trim', $record);
            $record['email'] = strtolower($record['email']);

            $User    = User::where('email', $record['email'])->get()->first();
            $Session = Session::where('old_id', $record['old_session_id'])->get()->first();

            if ($Session == null) {
                $this->output->writeln(' ' . 'Error with record ' . $record['old_enrollment_id'] . ' (' . $record['email'] . ', ' . $record['model'] . ') could not find associated session');
                $skipped_records++;
                continue;
            }

            $Enrollment = new Enrollment();
            $Enrollment->User()->associate($User);
            $Enrollment->Session()->associate($Session);
            $Enrollment->setAttribute('old_id', $record['old_enrollment_id']);
            $Enrollment->setAttribute('tag', $record['status']);
            $Enrollment->setAttribute('imported', 1);

            if ($record['cost_center_preferred'] != '') {
                $Enrollment->setAttribute('cost_center', $record['cost_center_preferred']);
            } else {
                $Enrollment->setAttribute('cost_center', $record['cost_center_fallback']);
            }

            switch ($record['status']) {
                case 'canceled':
                    $Enrollment->setAttribute('status', 'cancelled');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'cancelled_no_show':
                    $Enrollment->setAttribute('status', 'cancelled');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', 'noshow');
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'completed':
                    $Enrollment->setAttribute('status', 'registered');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', 'show');
                    $Enrollment->setAttribute('grade', 'pass');
                    break;
                case 'completed_cc':
                    $Enrollment->setAttribute('status', 'registered');
                    $Enrollment->setAttribute('payment_method', 'card');
                    $Enrollment->setAttribute('attendance', 'show');
                    $Enrollment->setAttribute('grade', 'pass');
                    break;
                case 'completed_idt':
                    $Enrollment->setAttribute('status', 'registered');
                    $Enrollment->setAttribute('payment_method', 'unit');
                    $Enrollment->setAttribute('attendance', 'show');
                    $Enrollment->setAttribute('grade', 'pass');
                    break;
                case 'completed_noshow':
                    $Enrollment->setAttribute('status', 'registered');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', 'noshow');
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'completed_ppd':
                    $Enrollment->setAttribute('status', 'registered');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', 'show');
                    $Enrollment->setAttribute('grade', 'pass');
                    break;
                case 'completed_refund':
                    $Enrollment->setAttribute('status', 'cancelled');
                    $Enrollment->setAttribute('payment_method', 'card');
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'partial_payment_due':
                    $Enrollment->setAttribute('status', 'pending');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'payment_received':
                    $Enrollment->setAttribute('status', 'registered');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'payment_received_but_not_rcvd':
                    $Enrollment->setAttribute('status', 'pending');
                    $Enrollment->setAttribute('payment_method', 'unit');
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'pending':
                    $Enrollment->setAttribute('status', 'pending');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'removed':
                    $Enrollment->setAttribute('status', 'cancelled');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'processing' :
                    $Enrollment->setAttribute('status', 'pending');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                case 'in_checkout' :
                    $Enrollment->setAttribute('status', 'pending');
                    $Enrollment->setAttribute('payment_method', null);
                    $Enrollment->setAttribute('attendance', null);
                    $Enrollment->setAttribute('grade', null);
                    break;
                default:
                    throw new \Exception('Unknown status ' . $record['status']);
                    break;
            }

            $Enrollment->save();

            $this->generateUserCertification($Enrollment);

            $User->employee_id    = $record['employee_id'];
            $User->license_number = $record['license_number'];
            $User->save();
        }

        $progress->finish();
        $this->output->writeln(PHP_EOL);
        $this->output->writeln($skipped_records . ' records not imported due to error');
    }

    private function generateUserCertification($Enrollment)
    {
        if ($Enrollment->grade == 'pass' && $Enrollment->Session->Course->CertificationReceived) {
            $DateIssued  = new Carbon($Enrollment->Session->date2 ? $Enrollment->Session->date2 : $Enrollment->Session->date1);
            $DateExpires = clone $DateIssued;
            $DateExpires->addMonths($Enrollment->Session->Course->CertificationReceived->length * 12);
            $DateExpires = $DateExpires->day($DateExpires->daysInMonth);

            $UserCertification = UserCertification::firstOrNew(['user_id' => $Enrollment->User->id, 'certification_id' => $Enrollment->Session->Course->CertificationReceived->id]);
            $UserCertification->User()->associate($Enrollment->User);
            $UserCertification->Certification()->associate($Enrollment->Session->Course->CertificationReceived);
            $UserCertification->Session()->associate($Enrollment->Session);
            $UserCertification->issued_at  = $DateIssued;
            $UserCertification->expires_at = $DateExpires;

            $UserCertification->Save();
        }
    }

    private function getCourseId($title)
    {
        $dictionary = [
            'ACLS Exp Provider Course'                                          => 5,
            'ACLS Exp Provider Instructor Training Course'                      => 7,
            'ACLS Fast Track'                                                   => 4,
            'ACLS Instructor Course'                                            => 6,
            'ACLS Prep Course'                                                  => 1,
            'ACLS Provider Training Course'                                     => 2,
            'ACLS Retrain Course'                                               => 3,
            'BLS Instructor Training Course'                                    => 9,
            'BLS Provider Training Course'                                      => 8,
            'ENPC Instructor Course'                                            => 28,
            'Emergency Nurse Pediatric Course (ENPC)'                           => 19,
            'Heart Saver CPR & AED'                                             => 11,
            'Heart Saver CPR AED & First Aid Course'                            => 13,
            'Heart Saver Instructor Course'                                     => 15,
            'Heart Saver/First Aid'                                             => 12,
            'NPR_Online_Checkoff_Session 1'                                     => 17,
            'NRP CHECK OFF SESSION'                                             => 17,
            'NRP Instructor Course'                                             => 18,
            'NRP Provider Online Course with Skills Check-off Session'          => 16,
            'PALS Fast Track Course'                                            => 25,
            'PALS Instructor Training Course'                                   => 26,
            'PALS Provider Training Course'                                     => 23,
            'PALS Retrain Course'                                               => 24,
            'PEARS Course'                                                      => 22,
            'STABLE'                                                            => 27,
            'TNCC Instructor Course'                                            => 21,
            'Trauma Nursing Core Course TNCC'                                   => 20,
            'NRP Provider LIVE Training Course'                                 => null,
            'Clone of NRP Provider Online Course with Skills Check-off Session' => null,
        ];

        return $dictionary[$title];
    }
}

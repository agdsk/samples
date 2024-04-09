<?php

namespace AppBundle\Command;

use AppBundle\Model\Course;
use AppBundle\Model\Enrollment;
use AppBundle\Model\InstructorAssignment;
use AppBundle\Model\Invoice;
use AppBundle\Model\Location;
use AppBundle\Model\Session;
use AppBundle\Model\User;
use AppBundle\Model\UserCertification;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Faker;

class PrplSeedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('prpl:seed')->setDescription('Seed tables with fake data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Users</comment>');
        $this->simulateUsers();

        $output->writeln('<comment>Invoices</comment>');
        $this->simulateInvoices();

        $output->writeln('<comment>Sessions</comment>');
        $this->simulateSessions();

        $output->writeln('<comment>Enrollments</comment>');
        $this->simulateEnrollments();

        $output->writeln('<comment>UserCertifications</comment>');
        $this->simulateUserCertifications();

        $output->writeln('<comment>InstructorAssignments</comment>');
        $this->simulateInstructorAssignments();
    }

    private function simulateUsers()
    {
        $Faker = Faker\Factory::create();

        $departments = array_filter(file(__DIR__ . '/data_departments.txt'), function ($value) {
            return rand(1, 6) == 1;
        });

        $titles = array_filter(file(__DIR__ . '/data_titles.txt'), function ($value) {
            return rand(1, 6) == 1;
        });

        for ($i = 1; $i <= 200; $i++) {
            $User = new User();
            $User->setContainer($this->getContainer());

            $User->first_name = $Faker->firstName;
            $User->last_name  = $Faker->lastName;
            $User->email      = $Faker->email;
            $User->department = trim($departments[array_rand($departments)]);
            $User->title      = trim($titles[array_rand($titles)]);

            $User->Save();
        }
    }

    private function simulateSessions()
    {
        $Courses  = Course::all();
        $Location = Location::orderBy('id', 'ASC')->get()->first();

        foreach ($Courses as $Course) {
            $DateTime = new \DateTime('-2 years');

            for ($i = 1; $i <= 1100; $i++) {
                $DateTime->add(new \DateInterval('P1D'));

                if (rand(1, 60) != 1) {
                    continue;
                }

                $Session = new Session();

                $Session->date1       = $DateTime;
                $Session->start_time1 = $Course->default_start_time1;
                $Session->end_time1   = $Course->default_end_time1;

                if ($Course->days == 2) {
                    $DateTime2 = clone $DateTime;
                    $DateTime2->add(new \DateInterval('P1D'));

                    $Session->start_time2 = $Course->default_start_time2;
                    $Session->end_time2   = $Course->default_end_time2;
                    $Session->date2       = $DateTime2;
                }

                $Session->student_max    = $Course->default_student_max;
                $Session->instructor_max = $Course->default_instructor_max;

                $Session->Course()->associate($Course);
                $Session->Location()->associate($Location);

                $Session->Save();
            }
        }
    }

    private function simulateInvoices()
    {
        for ($i = 1; $i <= 5; $i++) {
            $Invoice         = new Invoice();
            $Invoice->number = rand(1000000, 6000000);
            $Invoice->save();
        }
    }

    private function simulateEnrollments()
    {
        $Sessions = Session::orderBy('date1')->get();
        $Users    = User::all();
        $Invoices = Invoice::all();

        foreach ($Sessions as $Session) {
            $Users  = $Users->shuffle();
            $target = rand(1, $Session->student_max);
            $i      = 0;

            foreach ($Users as $User) {
                $Enrollment = new Enrollment();
                $Enrollment->User()->associate($User);
                $Enrollment->Session()->associate($Session);
                $Enrollment->setAttribute('cost', $Enrollment->Session->Course->cost_employee);

                // It is either unit, card or invoice
                $Enrollment->setAttribute('payment_method', $this->randomElement([50 => 'unit', 20 => 'card', 10 => 'invoice']));

                // If it is unit
                if ($Enrollment->payment_method == 'unit') {
                    $Enrollment->setAttribute('cost_center', $this->randomElement(['123456789', '8888888888', '424242424242', '90909909090', '3262347437', '435734573457']));
                    $Enrollment->setAttribute('status', 'pending');
                }

                // If it is card
                if ($Enrollment->payment_method == 'card') {
                    $Enrollment->setAttribute('card_number', rand(1000, 9999));
                    $Enrollment->setAttribute('status', 'registered');
                }

                // If it is invoice
                if ($Enrollment->payment_method == 'invoice') {
                    $Enrollment->Invoice()->associate($Invoices->random());
                    $Enrollment->setAttribute('status', 'registered');
                }

                // If it is in the past
                if ($Session->isPast()) {
                    // It was either registered, cancelled or denied
                    $Enrollment->setAttribute('status', $this->randomElement([80 => 'registered', 10 => 'cancelled', 10 => 'denied']));

                    // If it was cancelled
                    if ($Enrollment->status == 'cancelled') {
                        $Enrollment->setAttribute('cancellation_reason', array_rand(Session::$cancellation_reasons));
                    }

                    // If it was registered
                    if ($Enrollment->status == 'registered') {
                        // It was either show or no show
                        $Enrollment->setAttribute('attendance', $this->randomElement([90 => 'show', 10 => 'noshow']));

                        // If it was show
                        if ($Enrollment->attendance == 'show') {

                            // It was either pass or fail
                            $Enrollment->setAttribute('grade', $this->randomElement([90 => 'pass', 10 => 'fail']));
                        }
                    }
                }

                // If it is in the future
                if (!$Session->isPast()) {
                    // It was either pending, registered, cancelled, cancel_pending or denied
                    $Enrollment->setAttribute('status', $this->randomElement([10 => 'pending', 11 => 'cancel_pending', 12 => 'denied', 120 => 'registered', 13 => 'cancelled']));

                    // If it was cancelled
                    if ($Enrollment->status == 'cancelled') {
                        $Enrollment->setAttribute('cancellation_reason', array_rand(Session::$cancellation_reasons));
                    }
                }

                $Enrollment->save();

                if ($i++ == $target) {
                    break;
                }
            }
        }
    }

    private function simulateUserCertifications()
    {
        $offset = 0;
        $step   = 1000;

        while (true) {
            $Enrollments = Enrollment::with('Session', 'User', 'Session.Course.CertificationReceived')->where('grade', 'pass')->limit($step)->offset($offset)->get();

            if ($Enrollments->count() == 0) {
                break;
            }

            foreach ($Enrollments as $Enrollment) {
                if ($Enrollment->Session->Course->CertificationReceived) {
                    $UserCertification = new UserCertification();
                    $UserCertification->User()->associate($Enrollment->User);
                    $UserCertification->Certification()->associate($Enrollment->Session->Course->CertificationReceived);
                    $UserCertification->Session()->associate($Enrollment->Session);

                    $DateTime  = new \DateTime($Enrollment->Session->date1);
                    $DateTime2 = clone $DateTime;

                    $DateTime2->add(new \DateInterval('P2Y'));

                    $UserCertification->issued_at  = $DateTime;
                    $UserCertification->expires_at = $DateTime2;

                    $UserCertification->save();
                }
            }

            $offset += $step;
        }
    }

    private function simulateInstructorAssignments()
    {
        $Sessions = Session::orderBy('date1')->get();
        $Users    = User::all();

        foreach ($Sessions as $Session) {
            $Users  = $Users->shuffle();
            $target = rand(1, $Session->instructor_max);
            $i      = 0;

            foreach ($Users as $User) {
                $InstructorAssignment = new InstructorAssignment();

                $InstructorAssignment->Session()->associate($Session);
                $InstructorAssignment->User()->associate($User);
                $InstructorAssignment->Save();

                if ($i++ == $target) {
                    break;
                }
            }
        }
    }

    private function randomElement($array)
    {
        $total         = array_sum(array_keys($array));
        $random_number = rand(1, $total);
        $cum_points    = 0;

        foreach ($array as $k => $v) {
            $cum_points += $k;

            if ($random_number <= $cum_points) {
                return $v;
            }
        }
    }
}

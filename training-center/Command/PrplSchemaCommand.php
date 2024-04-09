<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Illuminate\Database\Capsule\Manager as Capsule;

class PrplSchemaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('prpl:schema')->setDescription('Drop existing tables, and recreate all tables');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Regenerating schema</comment>');

        Capsule::statement('SET FOREIGN_KEY_CHECKS=0;');

        Capsule::schema()->dropIfExists('site_settings');

        Capsule::schema()->create('site_settings', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('notice');
        });

        Capsule::schema()->dropIfExists('certifications');

        Capsule::schema()->create('certifications', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('slug');
            $table->integer('length');
        });

        Capsule::schema()->dropIfExists('user_certifications');

        Capsule::schema()->create('user_certifications', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('certification_id')->unsigned();
            $table->foreign('certification_id')->references('id')->on('certifications')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('session_id')->unsigned()->nullable();
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('restrict')->onUpdate('cascade');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
        });

        Capsule::schema()->dropIfExists('programs');

        Capsule::schema()->create('programs', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->text('overview')->nullable();
            $table->string('slug')->unique();
        });

        Capsule::schema()->dropIfExists('courses');

        Capsule::schema()->create('courses', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('slug');
            $table->integer('program_id')->unsigned();
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('certification_received_id')->unsigned()->nullable();
            $table->foreign('certification_received_id')->references('id')->on('certifications')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('certification_required_id')->unsigned()->nullable();
            $table->foreign('certification_required_id')->references('id')->on('certifications')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('default_instructor_max')->unsigned();
            $table->integer('default_student_max')->unsigned();
            $table->enum('days', [1, 2]);
            $table->time('default_start_time1')->nullable();
            $table->time('default_end_time1')->nullable();
            $table->time('default_start_time2')->nullable();
            $table->time('default_end_time2')->nullable();
            $table->decimal('cost_public')->nullable()->unsigned();
            $table->decimal('cost_employee')->nullable()->unsigned();
            $table->boolean('public')->default(true);
            $table->text('overview')->nullable();
            $table->text('description')->nullable();
            $table->text('instruction_intended_audience')->nullable();
            $table->text('instruction_prerequisites')->nullable();
            $table->text('instruction_before_class')->nullable();
            $table->text('instruction_bring_to_class')->nullable();
            $table->enum('and_or_materials', ['and', 'or'])->nullable();
            $table->boolean('rentable')->default(1);
        });

        Capsule::schema()->dropIfExists('locations');

        Capsule::schema()->create('locations', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('slug');
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('zip');
            $table->text('specifics')->nullable();
        });

        Capsule::schema()->dropIfExists('sessions');

        Capsule::schema()->create('sessions', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('old_id')->nullable();
            $table->integer('course_id')->unsigned();
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('restrict')->onUpdate('cascade');
            $table->date('date1');
            $table->time('start_time1');
            $table->time('end_time1');
            $table->date('date2')->nullable();
            $table->time('start_time2')->nullable();
            $table->time('end_time2')->nullable();
            $table->integer('student_max');
            $table->integer('instructor_max');
            $table->boolean('online_registration')->default(true);
            $table->boolean('public')->default(true);
            $table->text('notice')->nullable();
        });

        Capsule::schema()->dropIfExists('users');

        Capsule::schema()->create('users', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('opid')->nullable();
            $table->string('department')->nullable();
            $table->string('license_number')->nullable();
            $table->string('title')->nullable();
            $table->string('email')->unique();
            $table->enum('role', ['User', 'Instructor', 'Admin']);
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('token')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->enum('country', ['US', 'CA'])->nullable();

            $table->index('email');
        });

        Capsule::schema()->dropIfExists('invoices');

        Capsule::schema()->create('invoices', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('number');
        });

        Capsule::schema()->dropIfExists('enrollments');

        Capsule::schema()->create('enrollments', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('old_id')->unsigned()->nullable();
            $table->integer('session_id')->unsigned();
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->string('hash');
            $table->decimal('cost');
            $table->decimal('cost2');
            $table->enum('payment_method', ['unit', 'card', 'invoice'])->nullable();
            $table->string('cost_center')->nullable();
            $table->string('manager_name')->nullable();
            $table->text('manager_contact')->nullable();
            $table->string('card_number')->nullable();
            $table->string('transid')->nullable();
            $table->integer('invoice_id')->unsigned()->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('book_id')->unsigned()->nullable();
            $table->enum('status', ['pending', 'registered', 'cancel_pending', 'cancelled', 'denied'])->default('pending');
            $table->enum('grade', ['pass', 'fail'])->nullable();
            $table->enum('attendance', ['show', 'noshow'])->nullable();
            $table->integer('cancellation_reason')->nullable();
            $table->text('note_admin')->nullable();
            $table->text('note_user')->nullable();
            $table->string('tag')->nullable();
            $table->boolean('imported')->default(false);
            $table->timestamps();
        });

        Capsule::schema()->dropIfExists('instructor_assignment');

        Capsule::schema()->create('instructor_assignment', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('session_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->enum('attendance', ['show', 'noshow'])->nullable();
        });

        Capsule::schema()->dropIfExists('materials');

        Capsule::schema()->create('materials', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('isbn10')->nullable();
            $table->string('isbn13')->nullable();
            $table->boolean('rentable')->default(0);
            $table->decimal('rent_cost')->unsigned()->nullable();
            $table->decimal('purchase_cost')->unsigned();
        });

        Capsule::schema()->dropIfExists('course_material');

        Capsule::schema()->create('course_material', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('course_id')->unsigned()->nullable();
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('material_id')->unsigned()->nullable();
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('restrict')->onUpdate('cascade');
        });

        Capsule::schema()->dropIfExists('email_log');

        Capsule::schema()->create('email_log', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('email');
            $table->string('key');
            $table->string('subject')->nullable();
            $table->boolean('success');
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->dropIfExists('login_log');

        Capsule::schema()->create('login_log', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('username');
            $table->string('ip_address');
            $table->string('user_agent');
            $table->boolean('success');
            $table->timestamps();
        });

        Capsule::schema()->dropIfExists('general_log');

        Capsule::schema()->create('general_log', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('ip_address');
            $table->string('user_agent');
            $table->string('event_type');
            $table->string('data');
            $table->timestamps();
        });

        Capsule::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

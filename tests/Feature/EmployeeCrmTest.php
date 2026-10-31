<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeCrmController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeCrmTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->string('password');
            $table->string('user_type'); $table->string('phone')->nullable(); $table->string('adresse')->nullable();
            $table->boolean('active')->default(true); $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable(); $table->timestamps();
        });
        $migration = require database_path('migrations/2026_10_31_000003_create_employee_profiles_table.php');
        $migration->up();
        Schema::create('finance_cash_movements', function (Blueprint $table): void {
            $table->id();
        });
        $payrollMigration = require database_path('migrations/2026_10_31_000004_create_employee_payroll_tracking.php');
        $payrollMigration->up();
        $trackingMigration = require database_path('migrations/2026_10_31_000005_add_employee_tracking_ids.php');
        $trackingMigration->up();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_employee_directory_excludes_partners_and_searches_work_profiles(): void
    {
        $seller = $this->employee('Afi Mensah', User::ROLE_SALES, 'Vente');
        $partner = $this->employee('Partenaire Test', User::ROLE_PARTNER);
        $request = \Illuminate\Http\Request::create('/personnel', 'GET', ['q' => 'Vente']);

        $view = app(EmployeeCrmController::class)->index($request);

        $this->assertSame('Afi Mensah', $view->getData()['employees']->first()->name);
        $this->assertSame(1, $view->getData()['employees']->total());
        $this->assertNotSame($partner->id, $view->getData()['employees']->first()->id);
    }

    public function test_profile_update_saves_contact_and_work_details_without_changing_account_access(): void
    {
        $employee = $this->employee('Kossi Agbo', User::ROLE_MANAGER);
        $request = \Illuminate\Http\Request::create('/personnel/'.$employee->id, 'PUT', [
            'name' => 'Kossi Agbo', 'phone' => '90000001', 'adresse' => 'Lomé',
            'emergency_contact_name' => 'Ama Agbo', 'emergency_contact_phone' => '90000002',
            'job_title' => 'Responsable boutique', 'department' => 'Vente', 'hired_on' => '2024-03-01',
            'work_site' => 'Boutique Djagblé', 'notes' => 'Dossier mis à jour en recette.',
        ]);

        app(EmployeeCrmController::class)->update($request, $employee);

        $employee->refresh()->load('employeeProfile');
        $this->assertSame('90000001', $employee->phone);
        $this->assertSame(User::ROLE_MANAGER, $employee->user_type);
        $this->assertSame('Responsable boutique', $employee->employeeProfile->job_title);
        $this->assertSame('Vente', $employee->employeeProfile->department);
        $this->assertSame('2024-03-01', $employee->employeeProfile->hired_on->toDateString());
    }

    private function employee(string $name, string $role, ?string $department = null): User
    {
        $employee = User::query()->create([
            'name' => $name, 'email' => str($name)->slug('-').'@test.local', 'password' => 'test-password',
            'user_type' => $role, 'phone' => '90000000', 'active' => true,
        ]);
        if ($department) $employee->employeeProfile()->create(['department' => $department]);
        return $employee;
    }
}

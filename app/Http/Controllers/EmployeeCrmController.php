<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\FinanceAuditEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeCrmController extends Controller
{
    private const EMPLOYEE_ROLES = [
        User::ROLE_ADMIN,
        User::ROLE_CALL_CENTER,
        User::ROLE_SALES,
        User::ROLE_MANAGER,
        User::ROLE_SECRETARY,
        User::ROLE_LIVREUR,
        User::ROLE_CASHIER,
        User::ROLE_ACCOUNTANT,
        User::ROLE_COMMERCIAL_MANAGER,
    ];

    public function index(Request $request): View
    {
        $query = User::query()->with(['employeeProfile', 'responsibleStockLocations:id,name,responsible_user_id'])->whereIn('user_type', self::EMPLOYEE_ROLES)
            ->when($request->filled('role'), fn ($users) => $users->where('user_type', (string) $request->query('role')))
            ->when($request->query('status') === 'active', fn ($users) => $users->where('active', true))
            ->when($request->query('status') === 'inactive', fn ($users) => $users->where('active', false))
            ->when($request->filled('q'), function ($users) use ($request): void {
                $search = trim((string) $request->query('q'));
                $users->where(function ($scope) use ($search): void {
                    $scope->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('employeeProfile', fn ($profile) => $profile
                            ->where('job_title', 'like', '%'.$search.'%')
                            ->orWhere('department', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('name');

        $employees = $query->paginate(15)->withQueryString();
        $employees->getCollection()->each(function (User $employee): void {
            if (! $employee->employeeProfile) {
                $employee->setRelation('employeeProfile', $employee->employeeProfile()->create());
            }
        });

        return view('employees.index', [
            'employees' => $employees,
            'roles' => $this->roleLabels(),
            'filters' => $request->only(['q', 'role', 'status']),
            'activeCount' => User::query()->whereIn('user_type', self::EMPLOYEE_ROLES)->where('active', true)->count(),
            'inactiveCount' => User::query()->whereIn('user_type', self::EMPLOYEE_ROLES)->where('active', false)->count(),
        ]);
    }

    public function show(User $employee): View
    {
        $this->ensureEmployee($employee);
        $employee->load(['employeeProfile', 'responsibleStockLocations:id,name,responsible_user_id']);

        return view('employees.show', ['employee' => $employee, 'roleLabel' => $this->roleLabels()[$employee->user_type]]);
    }

    public function edit(User $employee): View
    {
        $this->ensureEmployee($employee);
        $employee->load(['employeeProfile', 'responsibleStockLocations:id,name,responsible_user_id']);

        return view('employees.edit', ['employee' => $employee]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->ensureEmployee($employee);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($employee->id)],
            'adresse' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:160'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'hired_on' => ['nullable', 'date', 'before_or_equal:today'],
            'monthly_salary' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
            'salary_effective_from' => ['nullable', 'required_with:monthly_salary', 'date_format:Y-m'],
            'work_site' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $profile = EmployeeProfile::query()->firstOrNew(['user_id' => $employee->id]);
        $salaryBefore = [
            'monthly_salary' => $profile->monthly_salary,
            'salary_effective_from' => $profile->salary_effective_from?->toDateString(),
        ];

        $employee->update(collect($data)->only([
            'name', 'phone', 'adresse', 'emergency_contact_name', 'emergency_contact_phone',
        ])->all());

        $profile->fill(collect($data)->only([
            'job_title', 'department', 'hired_on', 'monthly_salary', 'salary_effective_from', 'work_site', 'notes',
        ])->all());
        $profile->save();

        $salaryAfter = [
            'monthly_salary' => $profile->monthly_salary,
            'salary_effective_from' => $profile->salary_effective_from?->toDateString(),
        ];
        if ($salaryBefore !== $salaryAfter) {
            FinanceAuditEvent::query()->create([
                'subject_type' => 'EmployeeProfile',
                'subject_id' => $profile->id,
                'action' => 'employee_salary_updated',
                'before_values' => $salaryBefore,
                'after_values' => $salaryAfter,
                'reason' => 'Mise à jour du salaire convenu de '.$employee->name,
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        }

        return redirect()->route('employees.show', $employee->employeeProfile->tracking_id)->with('success', 'Dossier employé enregistré.');
    }

    private function ensureEmployee(User $user): void
    {
        abort_unless(in_array($user->user_type, self::EMPLOYEE_ROLES, true), 404);
    }

    private function roleLabels(): array
    {
        return [
            User::ROLE_ADMIN => 'Administrateur',
            User::ROLE_CALL_CENTER => 'SAV',
            User::ROLE_SALES => 'Vendeur',
            User::ROLE_MANAGER => 'Manager',
            User::ROLE_SECRETARY => 'Secrétaire',
            User::ROLE_LIVREUR => 'Livreur',
            User::ROLE_CASHIER => 'Caissier',
            User::ROLE_ACCOUNTANT => 'Comptable',
            User::ROLE_COMMERCIAL_MANAGER => 'Responsable commercial',
        ];
    }
}

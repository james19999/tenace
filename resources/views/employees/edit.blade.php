@extends('layouts.admin')

@section('content')
<main class="container-fluid employee-crm">
    <a class="ec-back" href="{{ route('employees.show',$employee->employeeProfile->tracking_id) }}">← Retour au dossier</a>
    <header class="ec-edit-heading"><span class="ec-eyebrow">DOSSIER EMPLOYÉ</span><h1>Modifier les informations</h1><p>{{ $employee->name }} · Les changements ici ne modifient ni son rôle d’accès ni son mot de passe.</p></header>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="ec-edit-card"><form method="POST" action="{{ route('employees.update',$employee->employeeProfile->tracking_id) }}">@csrf @method('PUT')
        <h2>Coordonnées</h2><div class="ec-edit-grid">
            <label>Nom complet<input class="form-control" name="name" value="{{ old('name',$employee->name) }}" required maxlength="255"></label>
            <label>Téléphone<input class="form-control" name="phone" value="{{ old('phone',$employee->phone) }}" maxlength="32"></label>
            <label class="ec-wide">Adresse<input class="form-control" name="adresse" value="{{ old('adresse',$employee->adresse) }}" maxlength="500"></label>
            <label>Contact d’urgence<input class="form-control" name="emergency_contact_name" value="{{ old('emergency_contact_name',$employee->emergency_contact_name) }}" maxlength="160"></label>
            <label>Téléphone du contact d’urgence<input class="form-control" name="emergency_contact_phone" value="{{ old('emergency_contact_phone',$employee->emergency_contact_phone) }}" maxlength="32"></label>
        </div>
        <h2>Informations de travail</h2><div class="ec-edit-grid">
            <label>Poste<input class="form-control" name="job_title" value="{{ old('job_title',$employee->employeeProfile?->job_title) }}" placeholder="Ex. Responsable boutique" maxlength="120"></label>
            <label>Service<input class="form-control" name="department" value="{{ old('department',$employee->employeeProfile?->department) }}" placeholder="Ex. Vente" maxlength="120"></label>
            <label>Date d’arrivée<input class="form-control" type="date" name="hired_on" value="{{ old('hired_on',$employee->employeeProfile?->hired_on?->format('Y-m-d')) }}" max="{{ today()->toDateString() }}"></label>
            <label>Lieu de travail<input class="form-control" name="work_site" value="{{ old('work_site',$employee->employeeProfile?->work_site) }}" placeholder="Ex. Boutique Djagblé" maxlength="160"></label>
            @if(!$employee->isAdmin())<label>Boutique de rattachement<select class="form-control" name="primary_stock_location_id" required><option value="">Choisir une boutique</option>@foreach($shopLocations as $location)<option value="{{ $location->id }}" @selected((string)old('primary_stock_location_id',$employee->primary_stock_location_id)===(string)$location->id)>{{ $location->name }}</option>@endforeach</select><small>Les boutiques grossistes ne sont pas proposées ici.</small></label>@endif
            <label class="ec-wide">Note interne<textarea class="form-control" name="notes" rows="4" maxlength="3000" placeholder="Information utile pour le suivi de cet employé">{{ old('notes',$employee->employeeProfile?->notes) }}</textarea></label>
        </div>
        <h2>Salaire convenu</h2><p class="ec-payroll-help">Suivi interne en F CFA. Aucun calcul de retenues légales ni bulletin de paie.</p><div class="ec-edit-grid">
            <label>Salaire mensuel (F CFA)<input class="form-control" type="number" name="monthly_salary" min="1" step="1" value="{{ old('monthly_salary',$employee->employeeProfile?->monthly_salary) }}" placeholder="Ex. 150000"></label>
            <label>Salaire applicable à partir du mois<input class="form-control" type="month" name="salary_effective_from" value="{{ old('salary_effective_from',$employee->employeeProfile?->salary_effective_from?->format('Y-m')) }}"></label>
        </div>
        <div class="ec-edit-actions"><a class="btn btn-light" href="{{ route('employees.show',$employee->employeeProfile->tracking_id) }}">Annuler</a><button class="btn btn-primary">Enregistrer le dossier</button></div>
    </form></section>
    <p class="ec-no-payroll">Les mots de passe, les rôles et l’activation du compte se gèrent dans « Comptes & accès ».</p>
</main>
<style>
.employee-crm{max-width:1100px;padding:clamp(14px,2vw,28px);color:var(--app-text)}.ec-back{color:var(--app-primary);font-weight:700}.ec-edit-heading{margin:18px 0}.ec-eyebrow{font-size:11px;letter-spacing:.08em;font-weight:800;color:var(--app-primary)}.ec-edit-heading h1{margin:5px 0;font-size:clamp(25px,3vw,34px);font-weight:850}.ec-edit-heading p{margin:0;color:var(--app-muted)}.ec-edit-card{padding:clamp(16px,2vw,24px);background:var(--app-surface);border:1px solid color-mix(in srgb,var(--app-text) 14%,var(--app-surface));border-radius:var(--app-radius)}.ec-edit-card h2{margin:0 0 14px;font-size:18px;font-weight:800}.ec-edit-card h2:not(:first-child){margin-top:24px}.ec-edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.ec-edit-grid label{min-width:0;margin:0;font-size:13px;font-weight:700}.ec-edit-grid .form-control{display:block;width:100%;margin-top:5px;min-width:0}.ec-wide{grid-column:1/-1}.ec-edit-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:22px}.ec-no-payroll,.ec-payroll-help{margin:8px 0;color:var(--app-muted);font-size:12px}
@media(max-width:600px){.employee-crm{padding:12px 9px}.ec-edit-grid{grid-template-columns:minmax(0,1fr)}.ec-wide{grid-column:auto}.ec-edit-actions>*{flex:1}}
</style>
@endsection

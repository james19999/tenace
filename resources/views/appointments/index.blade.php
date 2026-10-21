@extends('layouts.admin')

@section('content')
<div class="container-fluid py-3" style="max-width:1200px">
    <div class="mb-4"><h1 class="h3 mb-1">Rendez-vous avec le DG</h1><p class="text-muted mb-0">Consulte les créneaux libres et planifie un rendez-vous. La réservation est confirmée uniquement si le créneau est encore libre.</p></div>
    @if(session('appointment_success'))<div class="alert alert-success">{{ session('appointment_success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <nav class="nav nav-pills mb-3" aria-label="Rendez-vous du DG">
        <button type="button" class="nav-link active" data-appointment-tab="book">Planifier un rendez-vous</button>
        <button type="button" class="nav-link" data-appointment-tab="upcoming">Rendez-vous planifiés <span class="badge badge-light ml-1" data-appointment-counter="upcoming">{{ $appointments->count() }}</span></button>
    </nav>
    <section data-appointment-panel="book">
    @if(!$calendar)
        <div class="alert alert-warning">Les disponibilités du DG ne sont pas encore configurées. Demande à l’administrateur de les définir dans Paramètres.</div>
    @else
        <div class="card shadow-sm mb-4"><div class="card-header bg-white"><strong>Planifier pour {{ $calendar->dg->name }}</strong></div><div class="card-body">
            <form method="POST" action="{{ route('dg-appointments.store') }}" id="dg-appointment-form">
                @csrf
                <div class="row">
                    <div class="form-group col-12 col-md-6"><label for="visitor-name">Nom de la personne à recevoir</label><input class="form-control" id="visitor-name" name="visitor_name" value="{{ old('visitor_name') }}" required maxlength="150"></div>
                    <div class="form-group col-12 col-md-6"><label for="visitor-phone">Téléphone</label><input class="form-control" id="visitor-phone" name="visitor_phone" value="{{ old('visitor_phone') }}" required maxlength="40"></div>
                    <div class="form-group col-12"><label for="appointment-subject">Objet du rendez-vous</label><textarea class="form-control" id="appointment-subject" name="subject" rows="2" maxlength="500" required>{{ old('subject') }}</textarea></div>
                    <div class="form-group col-12 col-md-6"><label for="appointment-date">Date</label><input class="form-control" type="date" id="appointment-date" name="appointment_date" value="{{ old('appointment_date') }}" min="{{ now()->toDateString() }}" required></div>
                    <div class="form-group col-12"><label for="available-range">Période encore libre</label><select class="form-control" id="available-range" disabled required><option value="">Choisis d’abord une date</option></select><small class="form-text text-muted" id="slot-message">Les plages libres du DG s’afficheront après le choix de la date.</small></div>
                    <div class="form-group col-6"><label for="appointment-start">Début du rendez-vous</label><input class="form-control" type="time" id="appointment-start" name="starts_at" value="{{ old('starts_at') }}" disabled required></div>
                    <div class="form-group col-6"><label for="appointment-end">Fin du rendez-vous</label><input class="form-control" type="time" id="appointment-end" name="ends_at" value="{{ old('ends_at') }}" disabled required></div>
                </div>
                <button type="submit" class="btn btn-primary" id="appointment-submit" disabled>Planifier le rendez-vous</button>
            </form>
        </div></div>
    @endif
    </section>
    <section data-appointment-panel="upcoming" hidden>
    <nav class="nav nav-pills mb-3 dg-appointment-state-tabs" aria-label="État des rendez-vous">
        <button type="button" class="nav-link active" data-dg-appointment-tab="upcoming">À venir <span class="badge badge-light ml-1" data-appointment-counter="upcoming">{{ $appointments->count() }}</span></button>
        <button type="button" class="nav-link" data-dg-appointment-tab="past">Passés <span class="badge badge-light ml-1" data-appointment-counter="past">{{ $pastAppointments->count() }}</span></button>
    </nav>
    <div data-dg-appointment-panel="upcoming">
    <div class="card shadow-sm"><div class="card-header bg-white"><strong>Rendez-vous à venir</strong></div><div class="table-responsive"><table class="table mb-0 dg-appointment-table"><thead><tr><th>Date</th><th>Heure</th><th>Personne</th><th>Téléphone</th><th>Objet</th><th>Planifié par</th><th>État</th></tr></thead><tbody>
        @forelse($appointments as $appointment)
            <tr data-appointment-expires-at="{{ \Carbon\Carbon::parse($appointment->appointment_date->format('Y-m-d').' '.substr($appointment->ends_at, 0, 8))->timestamp * 1000 }}"><td>{{ $appointment->appointment_date->format('d/m/Y') }}</td><td>{{ substr($appointment->starts_at, 0, 5) }}–{{ substr($appointment->ends_at, 0, 5) }}</td><td>{{ $appointment->visitor_name }}</td><td>{{ $appointment->visitor_phone }}</td><td>{{ $appointment->subject }}</td><td>{{ $appointment->requester?->name ?? '—' }}</td><td><span class="badge badge-primary" data-appointment-status>À venir</span></td></tr>
        @empty<tr data-appointment-empty><td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous à venir.</td></tr>@endforelse
    </tbody></table></div></div>
    </div>
    <div data-dg-appointment-panel="past" hidden>
        <div class="card shadow-sm"><div class="card-header bg-white"><strong>Rendez-vous terminés</strong></div><div class="table-responsive"><table class="table mb-0 dg-appointment-table"><thead><tr><th>Date</th><th>Heure</th><th>Personne</th><th>Téléphone</th><th>Objet</th><th>Planifié par</th><th>État</th></tr></thead><tbody data-appointment-history-body>
            @forelse($pastAppointments as $appointment)
                <tr><td>{{ $appointment->appointment_date->format('d/m/Y') }}</td><td>{{ substr($appointment->starts_at, 0, 5) }}–{{ substr($appointment->ends_at, 0, 5) }}</td><td>{{ $appointment->visitor_name }}</td><td>{{ $appointment->visitor_phone }}</td><td>{{ $appointment->subject }}</td><td>{{ $appointment->requester?->name ?? '—' }}</td><td><span class="badge badge-secondary">Passé</span></td></tr>
            @empty<tr data-appointment-empty><td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous terminé.</td></tr>@endforelse
        </tbody></table></div></div>
    </div>
    </section>
</div>
<style>
    .dg-appointment-table { min-width:760px; }
    .table-responsive { -webkit-overflow-scrolling:touch; }
    .dg-appointment-state-tabs { display:flex; flex-wrap:wrap; gap:8px; }
    .dg-appointment-state-tabs .nav-link { display:inline-flex; align-items:center; gap:5px; }
    @media(max-width:575.98px) {
        .container-fluid.py-3 { padding-left:10px; padding-right:10px; }
        .nav[aria-label="Rendez-vous du DG"] { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; }
        .nav[aria-label="Rendez-vous du DG"] .nav-link,
        .dg-appointment-state-tabs .nav-link { justify-content:center; min-width:0; padding:9px 7px; font-size:12px; line-height:1.25; text-align:center; white-space:normal; }
        .dg-appointment-state-tabs { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; }
        .dg-appointment-table { min-width:720px; }
        [data-appointment-panel="book"] .card-body { padding:13px; }
        [data-appointment-panel="book"] .form-group { min-width:0; }
        [data-appointment-panel="book"] input,
        [data-appointment-panel="book"] select,
        [data-appointment-panel="book"] textarea { max-width:100%; }
        #appointment-submit { width:100%; min-height:44px; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const expireAppointmentRows = () => {
        document.querySelectorAll('[data-appointment-expires-at]').forEach(row => {
            if (Number(row.dataset.appointmentExpiresAt) > Date.now()) return;
            const historyBody = document.querySelector('[data-appointment-history-body]');
            if (!historyBody) { row.remove(); return; }
            const upcomingBody = row.closest('tbody');
            upcomingBody.querySelector('[data-appointment-empty]')?.remove();
            historyBody.querySelector('[data-appointment-empty]')?.remove();
            const status = row.querySelector('[data-appointment-status]');
            if (status) { status.textContent = 'Passé'; status.classList.remove('badge-primary'); status.classList.add('badge-secondary'); }
            row.removeAttribute('data-appointment-expires-at');
            historyBody.prepend(row);
            if (!upcomingBody.querySelector('tr:not([data-appointment-empty])')) {
                const emptyRow = document.createElement('tr');
                emptyRow.dataset.appointmentEmpty = '';
                emptyRow.innerHTML = '<td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous à venir.</td>';
                upcomingBody.appendChild(emptyRow);
            }
            if (!historyBody.querySelector('tr:not([data-appointment-empty])')) {
                const emptyRow = document.createElement('tr');
                emptyRow.dataset.appointmentEmpty = '';
                emptyRow.innerHTML = '<td colspan="7" class="text-center text-muted py-4">Aucun rendez-vous terminé.</td>';
                historyBody.appendChild(emptyRow);
            }
            document.querySelectorAll('[data-appointment-counter="upcoming"]').forEach(counter => {
                const count = Math.max(0, parseInt(counter.textContent, 10) || 0) - 1;
                counter.textContent = count > 99 ? '99+' : count;
            });
            document.querySelectorAll('[data-appointment-counter="past"]').forEach(counter => {
                const count = (parseInt(counter.textContent, 10) || 0) + 1;
                counter.textContent = count > 99 ? '99+' : count;
            });
        });
    };
    expireAppointmentRows();
    window.setInterval(expireAppointmentRows, 15000);
    const appointmentTabs = [...document.querySelectorAll('[data-appointment-tab]')];
    function activateAppointmentTab(name) {
        const selected = appointmentTabs.find(tab => tab.dataset.appointmentTab === name) || appointmentTabs[0];
        if (!selected) return;
        appointmentTabs.forEach(item => item.classList.toggle('active', item === selected));
        document.querySelectorAll('[data-appointment-panel]').forEach(panel => {
            panel.hidden = panel.dataset.appointmentPanel !== selected.dataset.appointmentTab;
        });
    }
    appointmentTabs.forEach(tab => tab.addEventListener('click', () => {
        activateAppointmentTab(tab.dataset.appointmentTab);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab.dataset.appointmentTab);
        history.replaceState(null, '', url);
    }));
    const stateTabs = [...document.querySelectorAll('[data-dg-appointment-tab]')];
    stateTabs.forEach(tab => tab.addEventListener('click', () => {
        stateTabs.forEach(item => item.classList.toggle('active', item === tab));
        document.querySelectorAll('[data-dg-appointment-panel]').forEach(panel => {
            panel.hidden = panel.dataset.dgAppointmentPanel !== tab.dataset.dgAppointmentTab;
        });
    }));
    activateAppointmentTab(new URLSearchParams(window.location.search).get('tab') || 'book');
    @if($calendar)
    /* Availability lookup and booking fields are present after the DG configures a calendar. */
    (() => {
    /* initialize the date and time controls */
    const dateInput = document.getElementById('appointment-date');
    const rangeSelect = document.getElementById('available-range');
    const startInput = document.getElementById('appointment-start');
    const endInput = document.getElementById('appointment-end');
    const message = document.getElementById('slot-message');
    const submit = document.getElementById('appointment-submit');
    const oldDate = @json(old('appointment_date'));
    const oldStart = @json(old('starts_at'));
    const oldEnd = @json(old('ends_at'));
    async function loadRanges() {
        rangeSelect.innerHTML = '<option value="">Chargement des plages libres…</option>';
        rangeSelect.disabled = true;
        startInput.disabled = true;
        endInput.disabled = true;
        submit.disabled = true;
        if (!dateInput.value) { message.textContent = 'Les plages libres du DG s’afficheront après le choix de la date.'; return; }
        try {
            const response = await fetch(@json(route('dg-appointments.slots')) + '?date=' + encodeURIComponent(dateInput.value), {headers: {'Accept': 'application/json'}});
            const result = await response.json();
            rangeSelect.innerHTML = '<option value="">Choisir une plage libre</option>';
            (result.ranges || []).forEach(range => {
                const value = range.starts_at + '|' + range.ends_at;
                const option = new Option(range.starts_at + ' – ' + range.ends_at, value, false,
                    dateInput.value === oldDate && oldStart >= range.starts_at && oldEnd <= range.ends_at);
                rangeSelect.add(option);
            });
            rangeSelect.disabled = !result.ranges || result.ranges.length === 0;
            message.textContent = result.message || 'Choisis le début et la fin du rendez-vous dans cette période.';
            applyRange();
        } catch (error) {
            rangeSelect.innerHTML = '<option value="">Plages indisponibles</option>';
            message.textContent = 'Impossible de charger les horaires. Actualise la page et réessaie.';
        }
    }
    function applyRange() {
        const parts = rangeSelect.value.split('|');
        const hasRange = parts.length === 2;
        startInput.disabled = !hasRange;
        endInput.disabled = !hasRange;
        startInput.min = hasRange ? parts[0] : '';
        startInput.max = hasRange ? parts[1] : '';
        endInput.min = hasRange ? parts[0] : '';
        endInput.max = hasRange ? parts[1] : '';
        if (dateInput.value !== oldDate || !oldStart || oldStart < parts[0] || oldStart >= parts[1]) startInput.value = '';
        if (dateInput.value !== oldDate || !oldEnd || oldEnd > parts[1] || oldEnd <= parts[0]) endInput.value = '';
        updateSubmit();
    }
    function updateSubmit() {
        submit.disabled = rangeSelect.disabled || !rangeSelect.value || !startInput.value || !endInput.value || startInput.value >= endInput.value;
    }
    dateInput.addEventListener('change', loadRanges);
    rangeSelect.addEventListener('change', applyRange);
    startInput.addEventListener('input', updateSubmit);
    endInput.addEventListener('input', updateSubmit);
    if (dateInput.value) loadRanges();
    })();
    @endif
});
</script>
@endsection

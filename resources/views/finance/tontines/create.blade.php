@extends('layouts.admin')
@section('content')
@php($roleNames = ['MNG' => 'Manager', 'SCR' => 'Secrétaire', 'VDS' => 'Vendeur', 'CSA' => 'Caissier', 'LVS' => 'Livreur', 'CALLCENTER' => 'SAV', 'COMPTABLE' => 'Comptable', 'RCM' => 'Responsable commercial', 'GRO' => 'Grossiste', 'INFO' => 'Informaticien / ingénieur informatique'])
@php($isEditing = $isEditing ?? false)
@php($tontine = $tontine ?? null)
<main class="tontine-create-page">
    <header class="tontine-create-heading"><a href="{{ $tontine ? route('finance.tontines.show', $tontine) : route('finance.tontines.index') }}">← {{ $tontine ? 'Retour à la tontine' : 'Tontines' }}</a><h1>{{ $isEditing ? 'Modifier la tontine' : 'Créer une tontine' }}</h1><p>Les cotisations peuvent varier chaque semaine. Le montant récupérable dépendra de ce qui aura réellement été cotisé.</p></header>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="tontine-create-card"><form method="POST" action="{{ $isEditing ? route('finance.tontines.update', $tontine) : route('finance.tontines.store') }}">@csrf @if($isEditing)@method('PUT')@endif
        <div class="tontine-create-grid">
            <label class="tontine-wide">Nom de la tontine<input class="form-control" name="name" value="{{ old('name', $tontine?->name) }}" maxlength="160" required placeholder="Ex. Tontine hebdomadaire"></label>
            <div class="tontine-wide tontine-cash-info"><span class="material-icons">account_balance</span><span><strong>Caisse générale TENACOS</strong><small>Toutes les cotisations de tontine sortent de la caisse générale. Si la personne chargée n’est pas le caissier, sa demande lui sera envoyée directement, sans validation préalable.</small></span></div>
            <fieldset class="tontine-wide tontine-assignees"><legend>Personnes chargées des cotisations</legend><label class="tontine-assignee-search-label" for="tontine-assignee-search">Rechercher une personne</label><input class="form-control" type="search" id="tontine-assignee-search" autocomplete="off" placeholder="Saisis au moins 2 lettres de son nom"><div id="tontine-assignee-results" class="tontine-assignee-results" role="status" aria-live="polite">Saisis un nom pour afficher les personnes correspondantes.</div><div class="tontine-selected-heading">Personnes choisies <span id="tontine-selected-count">{{ $selectedAssignees->count() }}</span></div><div id="tontine-selected-assignees" class="tontine-selected-assignees">@foreach($selectedAssignees as $assignee)<div class="tontine-selected-person" data-person-id="{{ $assignee->id }}"><input type="hidden" name="assignee_ids[]" value="{{ $assignee->id }}"><span><strong>{{ $assignee->name }}</strong><small>{{ $roleNames[$assignee->user_type] ?? 'Employé' }}</small></span><button type="button" aria-label="Retirer {{ $assignee->name }}" data-remove-person="{{ $assignee->id }}">×</button></div>@endforeach</div>@error('assignee_ids')<small class="tontine-assignee-error">{{ $message }}</small>@enderror<small>Les résultats s’affichent au fur et à mesure de la recherche. Seules les personnes choisies verront cette tontine dans leur menu Comptabilité.</small></fieldset>
            <label class="tontine-wide">Note (facultatif)<textarea class="form-control" name="notes" rows="3" maxlength="2000" placeholder="Organisateur, accord ou précision utile">{{ old('notes', $tontine?->notes) }}</textarea></label>
        </div>
        <aside class="tontine-create-note"><span class="material-icons">info</span><p>Le montant est saisi à chaque cotisation selon le versement réel. Le caissier général confirme chaque remise et la sortie apparaît dans la caisse. L’admin pourra récupérer tout ou partie du solde effectivement cotisé. Ces mouvements restent séparés des dépenses.</p></aside>
        <button class="btn btn-primary" type="submit">{{ $isEditing ? 'Enregistrer les modifications' : 'Créer et notifier les personnes' }}</button>
    </form></section>
</main>
<style>
.tontine-create-page{max-width:980px;padding:22px 24px 44px;color:var(--app-text,#273142)}.tontine-create-heading{margin-bottom:16px}.tontine-create-heading>a{color:var(--app-primary,#187b2a);font-size:13px;font-weight:750;text-decoration:none}.tontine-create-heading h1{margin:8px 0;font-size:clamp(26px,2.5vw,34px);font-weight:850}.tontine-create-heading p{color:var(--app-muted,#778397)}.tontine-create-card{padding:22px;background:var(--app-surface,#fff);border:1px solid var(--app-border,#e2e7eb)}.tontine-create-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.tontine-create-grid>label{min-width:0;color:var(--app-text,#465164);font-weight:750}.tontine-create-grid small{display:block;margin-top:5px;color:var(--app-muted,#778397);font-size:12px;font-weight:450}.tontine-wide{grid-column:1/-1}.tontine-assignees{min-width:0;margin:0;padding:0;border:0}.tontine-assignees legend{margin-bottom:8px;color:var(--app-text,#465164);font-size:14px;font-weight:750}.tontine-assignee-search-label{display:block;margin-bottom:5px;color:var(--app-text,#465164);font-size:12px;font-weight:700}.tontine-assignee-results{display:grid;gap:6px;max-height:260px;overflow:auto;margin-top:8px}.tontine-assignee-results:empty{display:none}.tontine-assignee-results>p{margin:0;padding:10px 12px;border:1px dashed var(--app-border,#dce2e8);border-radius:6px;color:var(--app-muted,#778397);font-size:12px}.tontine-search-result{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:9px 12px;border:1px solid var(--app-border,#e2e7eb);border-radius:6px;background:var(--app-surface,#fff);color:var(--app-text,#273142);text-align:left;cursor:pointer}.tontine-search-result:hover{border-color:var(--app-primary,#187b2a);background:var(--app-primary-soft,#f3f8f3)}.tontine-search-result strong,.tontine-search-result small{display:block}.tontine-search-result strong{font-size:13px}.tontine-search-result small{margin-top:2px;color:var(--app-muted,#778397);font-size:11px}.tontine-search-result>span:last-child{flex:none;color:var(--app-primary,#187b2a);font-size:12px;font-weight:750}.tontine-search-result:disabled{cursor:default;opacity:.7}.tontine-selected-heading{display:flex;align-items:center;gap:8px;margin-top:14px;color:var(--app-text,#465164);font-size:12px;font-weight:800}.tontine-selected-heading span{display:grid;place-items:center;min-width:22px;height:22px;padding:0 5px;border-radius:20px;background:var(--app-primary-soft,#f1f8f1);color:var(--app-primary,#187b2a)}.tontine-selected-assignees{display:flex;flex-wrap:wrap;gap:7px;margin-top:7px}.tontine-selected-person{display:flex;align-items:center;gap:10px;max-width:100%;padding:8px 10px;border:1px solid var(--app-border,#e2e7eb);border-radius:7px;background:var(--app-primary-soft,#f6faf6)}.tontine-selected-person>span{min-width:0}.tontine-selected-person strong,.tontine-selected-person small{display:block;overflow-wrap:anywhere}.tontine-selected-person strong{font-size:12px}.tontine-selected-person small{margin-top:2px;color:var(--app-muted,#778397);font-size:10px}.tontine-selected-person button{flex:none;width:25px;height:25px;padding:0;border:0;border-radius:50%;background:transparent;color:var(--app-muted,#778397);font-size:20px;line-height:1;cursor:pointer}.tontine-selected-person button:hover{background:#fce8e6;color:#b42318}.tontine-assignees>small{display:block;margin-top:8px;color:var(--app-muted,#778397);font-size:12px;font-weight:450}.tontine-assignees>.tontine-assignee-error{color:#b42318}.tontine-create-note{display:flex;gap:10px;margin:18px 0;padding:13px;background:var(--app-primary-soft,#f3f8f3);color:var(--app-text,#465164)}.tontine-create-note .material-icons{flex:none;color:var(--app-primary,#187b2a)}.tontine-create-note p{margin:0;line-height:1.5}.tontine-create-card>.btn,.tontine-create-card form>.btn{min-height:44px;padding:10px 17px;font-weight:750}
@media(max-width:650px){.tontine-create-page{padding:12px}.tontine-create-card{padding:15px}.tontine-create-grid,.tontine-assignee-grid{grid-template-columns:1fr}.tontine-wide{grid-column:auto}.tontine-create-card form>.btn{width:100%}}
.tontine-cash-info{display:flex;align-items:flex-start;gap:11px;padding:13px 14px;border:1px solid var(--app-border,#e2e7eb);border-radius:7px;background:var(--app-primary-soft,#f6f8f6)}.tontine-cash-info>.material-icons{flex:none;color:var(--app-primary,#811515)}.tontine-cash-info strong,.tontine-cash-info small{display:block}.tontine-cash-info small{line-height:1.5}
</style>
<script>
(() => {
    const search = document.getElementById('tontine-assignee-search');
    const results = document.getElementById('tontine-assignee-results');
    const selectedList = document.getElementById('tontine-selected-assignees');
    const selectedCount = document.getElementById('tontine-selected-count');
    const form = search?.closest('form');
    if (!search || !results || !selectedList || !selectedCount || !form) return;

    const searchUrl = @json(route('finance.tontines.assignees.search'));
    const selected = new Map();
    selectedList.querySelectorAll('.tontine-selected-person').forEach((person) => {
        const id = person.dataset.personId;
        selected.set(id, {name: person.querySelector('strong')?.textContent || '', role: person.querySelector('small')?.textContent || ''});
    });
    let timer;
    let controller;
    let lastResults = [];

    const updateCount = () => { selectedCount.textContent = String(selected.size); };
    const addPerson = (person) => {
        const id = String(person.id);
        if (selected.has(id)) return;
        selected.set(id, person);
        const card = document.createElement('div');
        card.className = 'tontine-selected-person';
        card.dataset.personId = id;
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'assignee_ids[]';
        hidden.value = id;
        const details = document.createElement('span');
        const name = document.createElement('strong');
        name.textContent = person.name;
        const role = document.createElement('small');
        role.textContent = person.role;
        details.append(name, role);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.removePerson = id;
        remove.setAttribute('aria-label', `Retirer ${person.name}`);
        remove.textContent = '×';
        card.append(hidden, details, remove);
        selectedList.append(card);
        updateCount();
        renderResults(lastResults);
    };
    const renderResults = (people = null) => {
        if (people === null) return;
        lastResults = people;
        results.replaceChildren();
        if (people.length === 0) {
            const empty = document.createElement('p');
            empty.textContent = 'Aucune personne trouvée.';
            results.append(empty);
            return;
        }
        people.forEach((person) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'tontine-search-result';
            button.dataset.personId = String(person.id);
            const details = document.createElement('span');
            const name = document.createElement('strong');
            name.textContent = person.name;
            const role = document.createElement('small');
            role.textContent = person.role;
            details.append(name, role);
            const action = document.createElement('span');
            const alreadySelected = selected.has(String(person.id));
            action.textContent = alreadySelected ? 'Déjà choisie' : 'Ajouter';
            button.disabled = alreadySelected;
            button.append(details, action);
            button.addEventListener('click', () => addPerson(person));
            results.append(button);
        });
    };

    selectedList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-person]');
        if (!button) return;
        selected.delete(String(button.dataset.removePerson));
        button.closest('.tontine-selected-person')?.remove();
        updateCount();
        if (search.value.trim().length >= 2) search.dispatchEvent(new Event('input'));
    });
    search.addEventListener('input', () => {
        search.setCustomValidity('');
        clearTimeout(timer);
        controller?.abort();
        const term = search.value.trim();
        if (term.length < 2) {
            results.replaceChildren();
            const hint = document.createElement('p');
            hint.textContent = 'Saisis au moins 2 lettres pour rechercher.';
            results.append(hint);
            return;
        }
        timer = setTimeout(async () => {
            controller = new AbortController();
            results.replaceChildren();
            const loading = document.createElement('p');
            loading.textContent = 'Recherche en cours…';
            results.append(loading);
            try {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, {
                    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('search_failed');
                renderResults(await response.json());
            } catch (error) {
                if (error.name === 'AbortError') return;
                results.replaceChildren();
                const failure = document.createElement('p');
                failure.textContent = 'La recherche est indisponible. Réessaie.';
                results.append(failure);
            }
        }, 250);
    });
    form.addEventListener('submit', (event) => {
        if (selected.size > 0) return;
        event.preventDefault();
        search.setCustomValidity('Choisis au moins une personne chargée des cotisations.');
        search.reportValidity();
    });
    updateCount();
})();
</script>
@endsection

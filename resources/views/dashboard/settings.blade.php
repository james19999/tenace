@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <div  style="padding-top: 10px">
        <div class="card-body">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#defaultModal">
                Paramètre
            </button>

            <!-- Modal -->
            <div class="modal fade" id="defaultModal" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="defaultModalLabel">Information sur entreprise</h4>
                            <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true" class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('setting-info') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                          <input type="text" name="name" class="form-control col-12 mb-4" id="" placeholder="Nom de l'entreprise" required>
                          <input type="text" name="phone" class="form-control col-12 mb-4" id="" placeholder="Téléphone" required>
                          <input type="text" name="address" class="form-control col-12 mb-4" id="" placeholder="Adresse" >
                          <input type="text" name="email" class="form-control col-12 mb-4" id="" placeholder="E-mail" required>
                          <input type="file" name="img" class="form-control col-12 mb-4" id="" placeholder="" >
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">Valider</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>




   <div class="card shadow">
       <div class="card-body ">
            @if (Session::has('messages'))
            <div class="alert alert-success">
               <strong>{{ session('messages') }}</strong>
               </div>
            @endif
           <div class="table-responsive">
               <table id="example" class="table table-hover w-100">
                   <thead class="thead-light">
                       <tr>
                           <th style="width: 20%">Logo</th>
                           <th style="width: 20%">Nom</th>
                           <th style="width: 20%">Téléphone</th>
                           <th style="width: 20%">Adresse</th>
                           <th style="width: 20%">Email</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>

                         @foreach ($settings as $setting )
                      <tr>

                         <td style="color: black "><img src="{{ url('image/',$setting->img) }}" width="50" height="50" alt="" srcset="">  </td>
                         <td style="color: black ">{{ $setting->name }}</td>
                         <td style="color: black ">{{ $setting->phone }}</td>
                         <td style="color: black ">{{ $setting->address }}</td>
                         <td style="color: black ">{{ $setting->email }}</td>

                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">

                                 <a  href="" style="color: white" type="button" class="btn btn-warning"

                                 data-hover="tooltip" data-placement="top"
                                 data-target="#modal-edit-customers{{$setting->id }}" data-toggle="modal"
                                  id="modal-edit"
                                 >
                                     <i class="material-icons">edit</i>
                                     Modifier</a>


                                     {{--  <form   method="POST" action="{{ route('destroy-expensives',$setting) }}"
                                     onclick="return confirm('supprimer') "
                                    >
                                         @csrf
                                          @method("DELETE")
                                        <button  style="padding-bottom: 12%" class="btn btn-sm btn-danger"
                                         ><i class="material-icons">delete</i>Supprimer</button>
                                    </form>  --}}
                             </div>
                         </td>
                      </tr>
                      <div class="modal fade" id="modal-edit-customers{{$setting->id}}" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="defaultModalLabel">Modifier les informations</h4>
                                    <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" class="material-icons">close</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('setting-update',$setting) }}" method="POST" enctype="multipart/form-data">
                                        @method('PUT')
                                        @csrf
                                         <input type="text" name="name" value="{{ $setting->name }}" class="form-control col-12 mb-4" id="" placeholder="libelle" required>
                                         <input type="text" name="phone" value="{{ $setting->phone }}" class="form-control col-12 mb-4" id="" placeholder="Téléphone" required>
                                         <input type="text" name="address" value="{{ $setting->address }}"  class="form-control col-12 mb-4" id="" placeholder="Adresse" >
                                         <input type="text" name="email"   value="{{ $setting->email }}" class="form-control col-12 mb-4" id="" placeholder="E-mail" required>
                                         <input type="file" name="img"   value="{{ $setting->img }}" class="form-control col-12 mb-4" id="" placeholder="" >
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                    <button type="submit" class="btn btn-primary">Modifier</button>
                                </div>
                            </form>
                            </div>
                        </div>
                     </div>
                         @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                    <tr>
                        <th style="width: 20%">Logo</th>
                        <th style="width: 20%">Nom</th>
                        <th style="width: 20%">Téléphone</th>
                        <th style="width: 20%">Adresse</th>
                        <th style="width: 20%">Email</th>
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
{{-- ============================================================ --}}
{{-- Section : Gestion des rôles utilisateurs (Admin uniquement) --}}
{{-- ============================================================ --}}
<div class="card shadow mt-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 font-weight-bold text-dark">
            <span class="material-icons align-middle mr-2" style="color:#7e1615;">manage_accounts</span>
            Gestion des rôles utilisateurs
        </h5>
    </div>
    <div class="card-body">

        {{-- Alertes --}}
        @if(session('role_success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="material-icons align-middle mr-1">check_circle</span>
                {{ session('role_success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif
        @if(session('role_error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="material-icons align-middle mr-1">error</span>
                {{ session('role_error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        {{-- Barre de recherche instantanée (au fur et à mesure de la frappe) --}}
        <form method="GET" action="{{ route('setting') }}" id="user-search-form" class="mb-4" onsubmit="return false;">
            <div class="input-group" style="max-width:420px;">
                <input type="text"
                       id="user-search-input"
                       name="user_search"
                       value="{{ $search ?? '' }}"
                       class="form-control"
                       placeholder="Rechercher par nom ou e-mail…"
                       autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="button" id="user-search-btn" tabindex="-1">
                        <span class="material-icons align-middle" style="font-size:18px;">search</span>
                    </button>
                    <button type="button" id="user-search-clear" class="btn btn-outline-secondary" style="{{ $search ? 'display:inline-flex;' : 'display:none;' }}">
                        <span class="material-icons align-middle" style="font-size:18px;">close</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- Conteneur dynamique AJAX du Tableau et de la Pagination --}}
        <div id="users-table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Rôle actuel</th>
                            <th style="width:220px;">Changer le rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="text-muted small">{{ $u->id }}</td>
                                <td class="font-weight-bold">{{ $u->name }}</td>
                                <td class="text-muted small">{{ $u->email }}</td>
                                <td>
                                    @php
                                        $roleLabels = [
                                            'ADMINUSER'   => ['label' => 'Admin',          'class' => 'badge-danger'],
                                            'MNG'         => ['label' => 'Manager',        'class' => 'badge-warning text-dark'],
                                            'SCR'         => ['label' => 'Secrétaire',     'class' => 'badge-info'],
                                            'CALLCENTER'  => ['label' => 'SAV',            'class' => 'badge-primary'],
                                            'VDS'         => ['label' => 'Vendeur',        'class' => 'badge-success'],
                                            'LVS'         => ['label' => 'Livreur',        'class' => 'badge-info'],
                                            'PT'          => ['label' => 'Partenaire',     'class' => 'badge-secondary'],
                                            'CSA'         => ['label' => 'Caissier',       'class' => 'badge-dark'],
                                            'User'        => ['label' => 'Utilisateur',    'class' => 'badge-light border'],
                                        ];
                                        $badge = $roleLabels[$u->user_type] ?? ['label' => $u->user_type, 'class' => 'badge-secondary'];
                                    @endphp
                                    <span class="badge {{ $badge['class'] }} px-2 py-1">{{ $badge['label'] }}</span>
                                </td>
                                <td>
                                    @if($u->id !== Auth::id())
                                        <button type="button" class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#modal-role-{{ $u->id }}">
                                            <i class="material-icons mr-1" style="font-size:16px;vertical-align:middle;">manage_accounts</i>
                                            Changer
                                        </button>

                                        {{-- Modal Confirmation Changement de Rôle --}}
                                        <div class="modal fade" id="modal-role-{{ $u->id }}" tabindex="-1" aria-labelledby="modalRoleLabel{{ $u->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title" id="modalRoleLabel{{ $u->id }}">Modifier le rôle</h4>
                                                        <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true" class="material-icons">close</span>
                                                        </button>
                                                    </div>
                                                    <form action="{{ route('user.update-role', $u->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body text-left">
                                                            <div class="mb-3 p-3 bg-light rounded">
                                                                <div class="font-weight-bold text-dark">{{ $u->name }}</div>
                                                                <div class="text-muted small">{{ $u->email }}</div>
                                                                <div class="mt-2">
                                                                    <span class="text-muted small mr-1">Rôle actuel :</span>
                                                                    <span class="badge {{ $badge['class'] }} px-2 py-1">{{ $badge['label'] }}</span>
                                                                </div>
                                                            </div>

                                                            <div class="form-group mb-0">
                                                                <label for="select-role-{{ $u->id }}" class="font-weight-bold text-dark">Nouveau rôle :</label>
                                                                <select name="user_type" id="select-role-{{ $u->id }}" class="form-control" required>
                                                                    @foreach($roleLabels as $val => $info)
                                                                        <option value="{{ $val }}" {{ $u->user_type === $val ? 'selected' : '' }}>
                                                                            {{ $info['label'] }} ({{ $val }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-primary">Valider la modification</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small font-italic">Votre compte</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <span class="material-icons d-block mb-2" style="font-size:2rem;">person_search</span>
                                    Aucun utilisateur trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination propre avec style Bootstrap et taille contrôlée --}}
            <style>
                #users-table-container .pagination {
                    margin-bottom: 0;
                    flex-wrap: wrap;
                }
                #users-table-container .pagination .page-item .page-link {
                    color: #7e1615;
                    border-color: #dee2e6;
                    padding: 6px 12px;
                    font-size: 0.9rem;
                }
                #users-table-container .pagination .page-item.active .page-link {
                    background-color: #7e1615;
                    border-color: #7e1615;
                    color: #ffffff;
                }
                #users-table-container .pagination .page-item.disabled .page-link {
                    color: #6c757d;
                }
                #users-table-container .pagination svg {
                    width: 14px !important;
                    height: 14px !important;
                    max-width: 14px !important;
                    max-height: 14px !important;
                    vertical-align: middle;
                }
            </style>

            <div class="d-flex justify-content-between align-items-center flex-wrap mt-3 pt-2 border-top">
                <div class="text-muted small mb-2 mb-md-0">
                    Affichage de <span class="font-weight-bold">{{ $users->firstItem() ?? 0 }}</span> à <span class="font-weight-bold">{{ $users->lastItem() ?? 0 }}</span> sur <span class="font-weight-bold">{{ $users->total() }}</span> utilisateur(s)
                </div>
                @if($users->hasPages())
                    <div>
                        {{ $users->appends(['user_search' => $search])->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('user-search-input');
        const clearBtn = document.getElementById('user-search-clear');
        const container = document.getElementById('users-table-container');
        let searchTimer = null;

        if (searchInput && container) {
            function performSearch(query) {
                if (clearBtn) {
                    clearBtn.style.display = query.trim() ? 'inline-flex' : 'none';
                }

                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    const url = new URL('{{ route("setting") }}', window.location.origin);
                    if (query.trim()) {
                        url.searchParams.set('user_search', query.trim());
                    }

                    fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContainer = doc.getElementById('users-table-container');
                        if (newContainer) {
                            container.innerHTML = newContainer.innerHTML;
                        }
                    })
                    .catch(err => console.error('Erreur recherche utilisateurs:', err));
                }, 250);
            }

            // Recherche automatique instantanée lors de la frappe
            searchInput.addEventListener('input', function () {
                performSearch(this.value);
            });

            // Bouton vider la recherche
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    searchInput.value = '';
                    performSearch('');
                    searchInput.focus();
                });
            }

            // Navigation pagination via AJAX fluide sans rechargement de page
            container.addEventListener('click', function (e) {
                const link = e.target.closest('.pagination a');
                if (link && link.href) {
                    e.preventDefault();
                    fetch(link.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContainer = doc.getElementById('users-table-container');
                        if (newContainer) {
                            container.innerHTML = newContainer.innerHTML;
                        }
                    })
                    .catch(err => console.error('Erreur pagination:', err));
                }
            });
        }
    });
</script>
@endsection

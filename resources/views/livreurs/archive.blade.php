@extends('layouts.admin')


@section('content')


<div class="col-12 ">
    <div  style="padding-top: 10px">
        <h3>Archives</h4>
    </div>
   <div class="card shadow">
       <div class="card-body ">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item"><a class="nav-link {{ $archiveType === 'orders' ? 'active' : '' }}" href="{{ route('archivelist', ['type' => 'orders']) }}">Commandes archivées</a></li>
                <li class="nav-item"><a class="nav-link {{ $archiveType === 'cases' ? 'active' : '' }}" href="{{ route('archivelist', ['type' => 'cases']) }}">Dossiers SAV archivés</a></li>
                <li class="nav-item"><a class="nav-link {{ $archiveType === 'requests' ? 'active' : '' }}" href="{{ route('archivelist', ['type' => 'requests']) }}">Demandes d’accès</a></li>
            </ul>
            @if (Session::has('messages'))
             <div class="alert alert-success">
                <strong>{{ session('messages') }}</strong>
             </div>
            @endif
            @if (Session::has('error'))
             <div class="alert alert-warning"><strong>{{ session('error') }}</strong></div>
            @endif

           @if($archiveType === 'cases')
           <div class="table-responsive">
               <table class="table table-hover w-100">
                   <thead class="thead-light"><tr><th>N° dossier</th><th>Cliente</th><th>Type</th><th>Produit</th><th>Responsable</th><th>Clôturé le</th><th>Archivé le</th><th>Action</th></tr></thead>
                   <tbody>
                   @forelse($cases as $case)
                       <tr>
                           <td><strong>{{ $case->case_number }}</strong></td>
                           <td>{{ $case->customer?->name ?? '—' }}<br><small>{{ $case->customer?->phone ?? '' }}</small></td>
                           <td>{{ ['complaint' => 'Réclamation', 'dissatisfied' => 'Insatisfaction', 'personalized_support' => 'Accompagnement', 'information' => 'Demande d’information'][$case->case_type] ?? $case->case_type }}</td>
                           <td>{{ $case->product?->name ?? '—' }}</td>
                           <td>{{ $case->assignee?->name ?? '—' }}</td>
                           <td>{{ $case->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                           <td>{{ $case->archived_at?->format('d/m/Y H:i') ?? '—' }}</td>
                           <td><a class="btn btn-sm btn-primary" href="{{ route('service-cases.show', ['caseId' => $case->id, 'archiveView' => 1]) }}">Consulter</a></td>
                       </tr>
                   @empty
                       <tr><td colspan="8" class="text-center py-4">Aucun dossier SAV archivé.</td></tr>
                   @endforelse
                   </tbody>
               </table>
           </div>
           <div class="d-flex justify-content-end">{{ $cases->links() }}</div>
           @elseif($archiveType === 'requests')
           <div class="table-responsive">
               <table class="table table-hover w-100">
                   <thead class="thead-light"><tr><th>Dossier</th><th>Cliente</th><th>Demandeur</th><th>Date de demande</th><th>Actions</th></tr></thead>
                   <tbody>
                   @forelse($accessRequests as $accessRequest)
                       <tr>
                           <td><strong>{{ $accessRequest->customerServiceCase?->case_number ?? 'Dossier indisponible' }}</strong></td>
                           <td>{{ $accessRequest->customerServiceCase?->customer?->name ?? '—' }}</td>
                           <td>{{ $accessRequest->requester?->name ?? '—' }}</td>
                           <td>{{ $accessRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                           <td>
                               <div class="d-flex flex-wrap" style="gap: 6px">
                                   <form method="POST" action="{{ route('archive.case-access.approve', $accessRequest) }}">@csrf<button type="submit" class="btn btn-sm btn-success">Autoriser</button></form>
                                   <form method="POST" action="{{ route('archive.case-access.reject', $accessRequest) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">Refuser</button></form>
                               </div>
                           </td>
                       </tr>
                   @empty
                       <tr><td colspan="5" class="text-center py-4">Aucune demande d’accès en attente.</td></tr>
                   @endforelse
                   </tbody>
               </table>
           </div>
           <div class="d-flex justify-content-end">{{ $accessRequests->links() }}</div>
           @else
           <div class="table-responsive">
               <table id="example" class="table table-hover w-100">
                   <thead class="thead-light">
                       <tr>
                           <th style="width: 20%">N°</th>
                           <th style="width: 20%">Code</th>
                           <th style="width: 20%">Nom/Téléphone (Client)</th>
                           <th style="width: 20%">Nom/Téléphone (Livreur)</th>
                           <th style="width: 20%">Sous total</th>
                           <th style="width: 20%">Frais</th>
                           <th style="width: 20%">Total</th>
                           <th style="width: 20%">Etat</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ($orders as $order )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $order->code }}</td>
                         <td style="color: black ">{{ $order->costumer->name ?? '-' }}| {{ $order->costumer->phone ?? '-' }}</td>
                         <td style="color: black ">{{ $order->user->name ?? 'Non' }}| {{ $order->user->phone ?? '' }}</td>

                         <td style="color: black ">{{ $order->subtotal }} F</td>
                         <td style="color: black ">{{ $order->tax }} F </td>
                         <td style="color: black ">{{ $order->total }} F </td>
                         <td style="color: black ">
                                @if ($order->status=="ordered")
                                <span class="badge badge-warning"> En cours</span>

                                @elseif($order->status=="delivered")
                                <span class="badge badge-success"> Terminé</span>

                                {{-- @endif --}}

                                @elseif ($order->status=="canceled")

                                <span class="badge badge-danger">Annuler</span>

                                {{-- @endif --}}

                                @endif
                          </td>
                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">
                                 <a href="{{ route('unlock',$order) }}" style="color: white" type="button" class="btn btn-success"
                                 >
                                 <i class="material-icons f-16">unlock</i>Débloquer</a>
                             </div>
                         </td>
                      </tr>
                         @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                       <tr>
                        <th style="width: 20%">N°</th>
                        <th style="width: 20%">Code</th>
                        <th style="width: 20%">Nom/Téléphone (Client)</th>
                        <th style="width: 20%">Nom/Téléphone (Livreur)</th>
                        <th style="width: 20%">Sous total</th>
                        <th style="width: 20%">Frais</th>
                        <th style="width: 20%">Total</th>
                        <th style="width: 20%">Etat</th>
                        <th style="width: 20%">Actions</th>
                       </tr>
                   </tfoot>
               </table>
           </div>
           @endif
       </div>
   </div>
</div>


@endsection

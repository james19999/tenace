@extends('layouts.admin')


@section('content')


<div class="col-12 ">
    <div  style="padding-top: 10px">
        <h3>Liste des commandes toujours en cours </h4>
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
                           <th style="width: 20%">N°</th>
                           <th style="width: 20%">Code</th>
                           <th style="width: 20%">Nom/Téléphone (Client)</th>
                           <th style="width: 20%">Nom/Téléphone (Livreur)</th>
                           <th style="width: 20%">Sous total</th>
                           <th style="width: 20%">Remise</th>
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
                         <td style="color: black ">{{ $order->remis }} % </td>
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
                                <a  href="{{ route('refresh-specific-order',$order) }}" style="color: white" type="button" class="btn btn-info"

                                data-hover="tooltip" data-placement="top"
                                data-target="#modal-edit-customers{{$order->id }}" data-toggle="modal"
                                 id="modal-edit"
                                >
                                    <i class="material-icons">alarm</i>
                                    Relancer la livraison sur aujourd'hui </a>
                                 @if (Auth::user()->user_type=="ADMINUSER")

                                 <a href="{{ route('ordershow',$order) }}" style="color: white" type="button" class="btn btn-success"
                                 >
                                 <i class="material-icons f-16">visibility</i>Details</a>

                                 <a href="{{ route('check-type',$order) }}" style="color: white" type="button" class="btn b
                                   @if ($order->type=="PR")
                                      btn-dark
                                   @else

                                   btn-warning
                                   @endif

                                 "

                                 >
                                 <i class="material-icons f-16">edit</i>{{ $order->type }}</a>


                                 <form   method="POST" action="{{ route('deleteorder',$order) }}"
                                 onclick="return confirm('supprimer') "
                                >
                                     @csrf
                                      @method("DELETE")
                                    <button  style="padding-bottom: 12%" class="btn btn-sm btn-danger"
                                     ><i class="material-icons">delete</i>Supprimer</button>
                                </form>
                                  @elseif (Auth::user()->user_type=="MNG" || Auth::user()->user_type=="CSA" )


                                  <a href="{{ route('ordershow',$order) }}" style="color: white" type="button" class="btn btn-success"
                                  >
                                  <i class="material-icons f-16">visibility</i>Details</a>



                                  <a href="{{ route('check-type',$order) }}" style="color: white" type="button" class="btn b
                                  @if ($order->type=="PR")
                                     btn-dark
                                  @else

                                  btn-warning
                                  @endif

                                "

                                >
                                <i class="material-icons f-16">edit</i>{{ $order->type }}</a>
                                 @endif
                             </div>
                         </td>
                      </tr>

                      <div class="modal fade" id="modal-edit-customers{{$order->id}}" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="defaultModalLabel">Relancer la livraison sur aujourd'hui</h4>
                                    <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" class="material-icons">close</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('refresh-specific-order',$order) }}" method="POST">
                                        @method('PUT')
                                        @csrf


                                        <div class="form-group">
                                           <label>Heure</label>

                                            <input type="time" name="time" value="{{ old('time') }}" class="form-control col-12" id="" required>
                                        </div>
                                        {{--  <div class="form-group">
                                           <label>Date</label>

                                            <input type="date" name="date_report" value="{{ old('date_report') }}" class="form-control col-12" id="" required>
                                        </div>

                                        <div class="form-group">
                                             <label>Livreurs</label>
                                            <select class="form-control col-12 " name="user_report">
                                               @foreach ($livreurs as $livreur )

                                               <option value="{{ $livreur->id }}"
                                                {{ $livreur->id == $order->user_id ? 'selected' : '' }}

                                                 >{{ $livreur->name }} </option>

                                               @endforeach
                                            </select>
                                        </div>  --}}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                    <button type="submit" class="btn btn-primary">Relancer</button>
                                </div>
                            </form>
                            </div>
                        </div>
                     </div>
                         @endforeach

                   </tbody>
                   <tfoot class="thead-light">
                       <tr>
                        <th style="width: 20%">N°</th>
                        <th style="width: 20%">Code</th>
                        <th style="width: 20%">Nom/Téléphone (Client)</th>
                        <th style="width: 20%">Nom/Téléphone (Livreur)</th>
                        <th style="width: 20%">Sous total</th>
                        <th style="width: 20%">Remise</th>
                        <th style="width: 20%">Total</th>
                        <th style="width: 20%">Etat</th>
                        <th style="width: 20%">Actions</th>
                       </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>


@endsection

@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <div  style="padding-top: 10px">
        <div class="card-body">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#defaultModal">
                Créer une dépense
            </button>
            <a href="{{ route('repport-expensives') }}" type="button" class="btn btn-primary">
                Rapport sur les dépenses
            </a>
            <button type="button" class="btn btn-dark">
                Total dépense : {{ $expensives->sum('amount') }} XOF
            </button>
            {{--  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#defaultModal">
                Créer une dépense
            </button>  --}}

            <!-- Modal -->
            <div class="modal fade" id="defaultModal" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="defaultModalLabel">Dépense</h4>
                            <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true" class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('expensives-create') }}" method="POST">
                                @csrf
                                 <div class="form-group">

                                     <textarea name="description" required id="" cols="3" rows="3"
                                      placeholder="description" class="form-control col-12">{{ old('description') }}</textarea>
                                 </div>

                                 <div class="form-group">
                                    <label>Date</label>

                                     <input type="date" name="date_create" class="form-control col-12" id="" required>
                                 </div>

                                 <div class="form-group">
                                      <label>Type de dépense</label>
                                     <select class="form-control col-12 " name="type_expensive_id">
                                        @foreach ($typeexpensives as $typeexpensive )

                                        <option value="{{ $typeexpensive->id }}">{{ $typeexpensive->name }} </option>

                                        @endforeach
                                     </select>
                                 </div>
                                 <div class="form-group">
                                      <label for="">Montant</label>
                                     <input type="number" name="amount" class="form-control col-12" value="{{ old('amount',0) }}" id="">
                                 </div>
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
                        <th style="width: 20%">N°</th>
                        <th style="width: 20%">Type de dépense</th>
                        <th style="width: 20%">Description</th>
                        <th style="width: 20%">Montant</th>
                        <th style="width: 20%">Date</th>
                        <th style="width: 20%">Créer par</th>
                        <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ($expensives as $expensive )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $expensive->typeexpensive->name ?? '' }}</td>
                         <td style="color: black ">{{ $expensive->description }}</td>
                         <td style="color: black ">{{ $expensive->amount }} FCFA</td>
                         <td style="color: black ">{{ $expensive->date_create->format('Y-m-d') }}</td>
                         <td style="color: black ">{{ $expensive->user->name }}</td>

                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">

                                 <a  href="{{ route('expensives-update',$expensive) }}" style="color: white" type="button" class="btn btn-warning"

                                 data-hover="tooltip" data-placement="top"
                                 data-target="#modal-edit-customers{{$expensive->id }}" data-toggle="modal"
                                  id="modal-edit"
                                 >
                                     <i class="material-icons">edit</i>
                                     Modifier</a>


                                     <form   method="POST" action="{{ route('destroy-expensives',$expensive) }}"
                                     onclick="return confirm('supprimer') "
                                    >
                                         @csrf
                                          @method("DELETE")
                                        <button  style="padding-bottom: 12%" class="btn btn-sm btn-danger"
                                         ><i class="material-icons">delete</i>Supprimer</button>
                                    </form>
                             </div>
                         </td>
                      </tr>
                      <div class="modal fade" id="modal-edit-customers{{$expensive->id}}" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="defaultModalLabel">Modifier la dépense</h4>
                                    <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" class="material-icons">close</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('expensives-update',$expensive) }}" method="POST">
                                        @method('PUT')
                                        @csrf
                                        <div class="form-group">

                                            <textarea name="description" required id="" cols="3" rows="3"
                                             placeholder="description" class="form-control col-12">{{ old('description',$expensive->description) }}</textarea>
                                        </div>

                                        <div class="form-group">
                                           <label>Date</label>

                                            <input type="date" name="date_create" value="{{ old('date_create',$expensive->date_create) }}" class="form-control col-12" id="" required>
                                        </div>

                                        <div class="form-group">
                                             <label>Type de dépense</label>
                                            <select class="form-control col-12 " name="type_expensive_id">
                                               @foreach ($typeexpensives as $typeexpensive )

                                               <option value="{{ $typeexpensive->id }}"
                                                {{ $typeexpensive->id == $expensive->type_expensive_id ? 'selected' : '' }}

                                                 >{{ $typeexpensive->name }} </option>

                                               @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                             <label for="">Montant</label>
                                            <input type="number" name="amount" class="form-control col-12" value="{{ old('amount',$expensive->amount) }}" id="">
                                        </div>
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
                        <th style="width: 20%">N°</th>
                        <th style="width: 20%">Type de dépense</th>
                        <th style="width: 20%">Description</th>
                        <th style="width: 20%">Montant</th>
                        <th style="width: 20%">Date</th>
                        <th style="width: 20%">Créer par</th>
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>


@endsection

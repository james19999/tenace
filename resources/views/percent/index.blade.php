@extends('layouts.admin')

@section('content')

<div class="col-12 ">
    <div  style="padding-top: 10px">
        <div class="card-body">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#defaultModal">
               Pourcentage
            </button>

            <!-- Modal -->
            <div class="modal fade" id="defaultModal" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="defaultModalLabel">Pourcentage</h4>
                            <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true" class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('create-post') }}" method="POST">
                                @csrf
                                 <input type="number" name="amount" class="form-control col-12" id="" value="{{ old('amount',0) }}" placeholder="libelle" required>

                                 <select name="percent" id="" class="form-control mt-3">
                                    <option value="Pub">Publicité</option>
                                    <option value="Fond">Fond de roulement</option>
                                    <option value="Resp">Responsable</option>
                                    <option value="Big">Manager</option>
                                    <option value="Im">Imprévu</option>
                                    <option value="Epr">Epargne</option>
                                 </select>
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
                           <th style="width: 20%">Pourcentage</th>
                           <th style="width: 20%">Type</th>
                           <th style="width: 20%">Actions</th>
                       </tr>
                   </thead>
                   <tbody>
                        @php
                            $i=1;
                        @endphp
                         @foreach ($percents as $percent )
                      <tr>

                         <td style="color: black ">{{ $i++ }}</td>
                         <td style="color: black ">{{ $percent->amount }} %</td>
                         <td style="color: black ">{{ $percent->percent }} </td>

                         <td style="color: black " class=" pull-right">

                             <div class="btn-group btn-group-justified">

                                 <a  href="{{ route('percen-edit',$percent) }}" style="color: white" type="button" class="btn btn-warning"

                                 data-hover="tooltip" data-placement="top"
                                 data-target="#modal-edit-customers{{$percent->id }}" data-toggle="modal"
                                  id="modal-edit"
                                 >
                                     <i class="material-icons">edit</i>
                                     Modifier</a>


                                     {{--  <form   method="POST" action="{{ route('destroy-expensives',$percent) }}"
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
                      <div class="modal fade" id="modal-edit-customers{{$percent->id}}" tabindex="-1" aria-labelledby="defaultModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="defaultModalLabel">Modifier le  type de dépense</h4>
                                    <button type="button" class="btn btn-light btn-circle dismiss" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" class="material-icons">close</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ route('percen-edit',$percent) }}" method="POST">
                                        @method('PUT')
                                        @csrf
                                         <input type="number" name="amount" value="{{ $percent->amount }}" class="form-control col-12" id="" placeholder="libelle" required>

                                         <select name="percent" id="" class="form-control mt-3">
                                            <option value="Pub">Publicité</option>
                                            <option value="Fond">Fond de roulement</option>
                                            <option value="Resp">Responsable</option>
                                            <option value="Big">Manager</option>
                                            <option value="Im">Imprévu</option>
                                            <option value="Epr">Epargne</option>

                                         </select>
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
                        <th style="width: 20%">Pourcentage</th>
                        <th style="width: 20%">Type</th>
                        <th style="width: 20%">Actions</th>
                    </tr>
                   </tfoot>
               </table>
           </div>
       </div>
   </div>
</div>


@endsection

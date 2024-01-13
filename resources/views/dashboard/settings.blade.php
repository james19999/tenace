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
</div>


@endsection

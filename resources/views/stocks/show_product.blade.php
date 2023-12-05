@extends('layouts.admin')

@section('content')
<div class="container mt-5">
    <ul class="nav nav-tabs" id="myTabs">
      <li class="nav-item">
        <a class="nav-link active" id="tab1-tab" data-toggle="tab" href="#tab1">Entrer de stock</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab2-tab" data-toggle="tab" href="#tab2">Sortie de stock</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab3-tab" data-toggle="tab" href="#tab3">{{ $product->name }}</a>
      </li>
    </ul>

    <div class="tab-content mt-2">
      <div class="tab-pane fade show active" id="tab1">
             <div class="col-12">
                 <div class="row ">
                     <div class="col-md-12">
                         <form action="{{ route('enter_stock',$product) }}" method="POST">
                             @csrf
                             <div class="row mt-3">
                                 <div class="col-md-4">
                                     <div class="form-group">

                                         <input type="number" name="qt_stock"
                                         value="{{ old('qt_stock',0)}}" class="form-control" id="" required>
                                         <input type="hidden" name="product_id" value="{{ $product->id }}">
                                         <small>Entrer la quantité</small>
                                     </div>
                                 </div>

                                 <div class="col-md-6 ">
                                          <div class="form-group">

                                          <button type="submit" class="btn btn-outline-success" >Valider</button>
                                        </div>
                                  </div>
                             </div>
                         </form>
                     </div>
                 </div>
                 <div class="card shadow">
                    <div class="card-body ">
                        @if (Session::has('message'))
                        <div class="alert alert-success">
                        <strong>{{ session('message') }}</strong>
                        </div>
                        @endif

                    <div class="table-responsive">
                        <table id="example" class="table table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 20%">N°</th>
                                    <th style="width: 20%">Quantité</th>
                                    <th style="width: 20%">Date de création</th>
                                    {{--  <th style="width: 20%">Actions</th>  --}}
                                </tr>
                            </thead>
                            <tbody>
                                    @php
                                        $i=1;
                                    @endphp
                                    @foreach ($product->enterstocks as $items )
                                <tr>

                                    <td style="color: black ">{{ $i++}}</td>
                                    <td style="color: black ">{{ $items->qt_stock}}</td>
                                    <td  style="color: black ; ">{{ $items->created_at }}</td>


                                </tr>
                                    @endforeach

                            </tbody>
                            <tfoot class="thead-light">
                                <tr>
                                    <th style="width: 20%">N°</th>
                                    <th style="width: 20%">Quantité</th>
                                    <th style="width: 20%">Date de création</th>
                                    {{--  <th style="width: 20%">Actions</th>  --}}
                                </tr>

                            </tfoot>
                        </table>
                    </div>
                </div>
                </div>
             </div>
      </div>
      <div class="tab-pane fade" id="tab2">
        <div class="col-12">
            <div class="row ">
                <div class="col-md-12">
                    <form action="{{ route('out_stock',$product) }}" method="POST">
                        @csrf
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="form-group">

                                    <input type="number" name="qt_stock"
                                    value="{{ old('qt_stock',0)}}" class="form-control" id="" required>
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <small>Entrer la quantité</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                     <textarea name="raison" id="" required placeholder="Saisissez la raison" class="form-control" cols="8" rows="1">{{ old('raison') }}</textarea>

                                    <small>Saisissez la raison</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                     <div class="form-group">

                                     <button type="submit" class="btn btn-outline-success" >Valider</button>
                                   </div>
                             </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card shadow">
               <div class="card-body ">
                   @if (Session::has('message'))
                   <div class="alert alert-success">
                   <strong>{{ session('message') }}</strong>
                   </div>
                   @endif

               <div class="table-responsive">
                   <table id="example" class="table table-hover w-100">
                       <thead class="thead-light">
                           <tr>
                               <th style="width: 20%">N°</th>
                               <th style="width: 20%">Raison</th>
                               <th style="width: 20%">Quantité</th>
                               <th style="width: 20%">Date de création</th>
                               {{--  <th style="width: 20%">Actions</th>  --}}
                           </tr>
                       </thead>
                       <tbody>
                               @php
                                   $i=1;
                               @endphp
                               @foreach ($product->outstocks as $item )
                           <tr>

                               <td style="color: black ">{{ $i++}}</td>
                               <td style="color: black ">{{ $item->raison}}</td>
                               <td style="color: black ">{{ $item->qt_stock}}</td>
                               <td  style="color: black ; ">{{ $item->created_at }}</td>


                           </tr>
                               @endforeach

                       </tbody>
                       <tfoot class="thead-light">
                           <tr>
                               <th style="width: 20%">N°</th>
                               <th style="width: 20%">Raison</th>
                               <th style="width: 20%">Quantité</th>
                               <th style="width: 20%">Date de création</th>
                               {{--  <th style="width: 20%">Actions</th>  --}}
                           </tr>

                       </tfoot>
                   </table>
               </div>
           </div>
           </div>
        </div>
      </div>
      <div class="tab-pane fade" id="tab3">
        <h3>Content for Tab 3</h3>
        <p>This is the content for the third tab.</p>
      </div>
    </div>
  </div>
@endsection

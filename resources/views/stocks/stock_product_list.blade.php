{{--  @extends('layouts.admin')

@section('content')
<div class="container mt-5">
    <ul class="nav nav-tabs" id="myTabs">
      <li class="nav-item">
        <a class="nav-link active" id="tab1-tab" data-toggle="tab" href="#tab1">

        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab2-tab" data-toggle="tab" href="#tab2"><span style="color: green" >
             Total :  {{ number_format($totales, 2, '.', '')   }} F
         </span>  </a>
      </li>

    </ul>

    <div class="tab-content mt-2">
      <div class="tab-pane fade show active" id="tab1">
    <div class="col-12 ">

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
                            <th style="width: 20%">Nom</th>
                            <th style="width: 20%">Prix d'achat </th>
                            <th style="width: 20%">Prix d'achat unitaire</th>
                            <th style="width: 20%">Quantité restant</th>
                            <th style="width: 20%">Prix de vente</th>
                            <th style="width: 20%">Quantité vendue </th>
                            <th style="width: 20%">Bénéfice</th>
                            <th style="width: 20%">Status</th>
                            <th style="width: 20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                            @php
                                $i=1;
                            @endphp
                            @foreach ($Products as $product )
                        <tr>

                            <td style="color: black ">{{ $product->name }}</td>
                            <td style="color: black ">{{number_format($product->price_by, 2, '.', '') }} F</td>
                            <td  style="color: white ; background-color: black">{{number_format($product->price_market, 2, '.', '') }} F</td>
                                @if ($product->qt_initial==null)
                                <td>0</td>

                                @else

                                <td style="color: red">{{$product->qt_initial }} </td>
                                @endif
                            <td  style="color: white ; background-color: green">{{number_format($product->price , 2, '.', '') }} F</td>
                            <td  style="color: white ; background-color: black">{{ $product->qts_sell }} </td>
                            <td  style="color: white ; background-color: green">{{$product->benefice }} </td>
                            <td style="color: black ">
                                @if ($product->qts_seuil>=$product->qt_initial)
                                <span class="badge rounded-pill bg-danger" style="color: white">En cours de rupture  </span>
                                @else
                                <span class="badge rounded-pill bg-success"  style="color: white" >Disponible en stock </span>

                                @endif
                            </td>

                            <td style="color: black " class=" pull-right">

                                <div class="btn-group btn-group-justified">
                                    <button style="color: white" type="button" class="btn btn-success"

                                    wire:click.prevent="AddToCart({{$product->id }},'{{  $product->name }}',{{  $product->price }})"
                                    >
                                        <i class="material-icons">shopping_cart</i>Ajouter</button>
                                        @if (Auth::user()->user_type=="ADMINUSER")
                                        <a href="{{ route('editproduct',$product) }}" style="color: white" type="button" class="btn btn-warning">
                                            <i class="material-icons">visibility</i>
                                            Modifier</a>

                                            <form   method="POST" action="{{ route('delteproduct',$product) }}"
                                            onclick="return confirm('supprimer') "
                                        >
                                                @csrf
                                                @method("DELETE")
                                            <button  style="padding-bottom: 12%" class="btn btn-sm btn-danger"
                                                ><i class="material-icons">delete</i>Supprimer</button>
                                    </form>
                                        @endif
                                </div>
                            </td>
                        </tr>
                            @endforeach

                    </tbody>
                    <tfoot class="thead-light">
                        <tr>
                            <th style="width: 20%">Nom</th>
                            <th style="width: 20%">Prix d'achat </th>
                            <th style="width: 20%">Prix d'achat unitaire</th>
                            <th style="width: 20%">Quantité restant</th>
                            <th style="width: 20%">Prix de vente</th>
                            <th style="width: 20%">Quantité vendue</th>
                            <th style="width: 20%">Bénéfice</th>
                            <th style="width: 20%">Status</th>
                            <th style="width: 20%">Actions</th>
                        </tr>

                    </tfoot>
                </table>
            </div>
        </div>
        </div>
    </div>
      </div>
    </div>
  </div>



@endsection  --}}


<div class="col-12 ">
     @php
       $mois=   date("F");
    @endphp

     <div  style="padding-top: 10px">
        @if (Auth::user()->user_type=="ADMINUSER")
        <a href="{{ route('productform') }}" class="btn btn-primary  pull-right">Ajouter un produit</a>
        <a href="{{ route('getProductOrders') }}" class="btn btn-success  pull-right" style="color: white">Rapport sur les produits les plus vendus du mois
           de  {{ $mois }}
        </a>
         <a href="{{ route('stock-list-product') }}" class="btn btn-dark  pull-right">Stock</a>
        @endif
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
                            <th style="width: 20%">Nom</th>
                            <th style="width: 20%">Prix</th>
                            <th style="width: 20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                         @php
                             $i=1;
                         @endphp
                          @foreach ($Products as $product )
                       <tr>

                          <td style="color: black ">{{ $i++ }}</td>
                          <td style="color: black ">{{ $product->name }}</td>
                          <td style="color: black ">{{ number_format($product->price) }} F</td>
                          <td style="color: black " class=" pull-right">

                              <div class="btn-group btn-group-justified">
                                  <button style="color: white" type="button" class="btn btn-success"

                                  wire:click.prevent="AddToCart({{$product->id }},'{{  $product->name }}',{{  $product->price }})"
                                  >
                                      <i class="material-icons">shopping_cart</i>Ajouter</button>
                                     @if (Auth::user()->user_type=="ADMINUSER")
                                     <a href="{{ route('editproduct',$product) }}" style="color: white" type="button" class="btn btn-warning">
                                         <i class="material-icons">edit</i>
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
                            <th>N°</th>
                            <th>Nom</th>
                            <th>Prix</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>


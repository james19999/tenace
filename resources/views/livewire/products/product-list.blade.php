
<div class="col-12 ">
     <div  style="padding-top: 10px">
        <a href="{{ route('productform') }}" class="btn btn-primary  pull-right">Ajouter un produit</a>
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
                                  <button style="color: white" type="button" class="btn btn-warning">
                                      <i class="material-icons">edit</i>
                                      Modifier</button>
                                  <button type="button" class="btn btn-danger">
                                      <i class="material-icons">delete</i>
                                    Supprimer</button>
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





 <form method="POST" action="{{ route('palceorder') }}">
    @csrf
     <div class="col-12 ">
         <div class="row">
             <div class="col-md-4">

                 <div  style="padding-top: 10px">
                    <select class="form-control" name="costumer_id">
                         @foreach ($Costumers as $Costumer )
                         <option value="{{ $Costumer->id }}">{{ $Costumer->name }} | {{ $Costumer->phone }} </option>

                         @endforeach
                      </select>
                 </div>
             </div>
             <div class="col-md-4" style="padding-top: 1%">
                 <input type="number" name="tax" id="" value="{{old('tax')}}"  placeholder="Frais de livraison" class="form-control" >
             </div>
             <div class="col-md-4" style="padding-top: 1%">
                <button type="submit" style="color: white" class="btn btn-success btn-block">Valider
                 ({{ Cart::instance('cart')->subtotal()  }}) F</button>
             </div>
             <input type="hidden" name="subtotal" value="{{ Cart::instance('cart')->subtotal() }}">
         </div>
        <div class="card shadow">
            <div class="card-body ">
                 @if (Session::has('messages'))
                 <div class="alert alert-success">
                    <strong>{{ session('messages') }}</strong>
                </div>
                 @endif
                   @if (Cart::instance('cart')->count()>0)
                   <div class="table-responsive">
                       <table id="example" class="table table-hover w-100">
                           <thead class="thead-light">
                               <tr>
                                   <th style="width: 20%">N°</th>
                                   <th style="width: 20%">Designation</th>
                                   <th style="width: 20%">Prix</th>
                                   <th style="width: 20%">Total</th>
                                   <th style="width: 20%">Quantité</th>
                                   <th style="width: 20%">Actions</th>
                               </tr>
                           </thead>
                           <tbody>
                                @php
                                    $i=1;
                                @endphp
                                 @foreach (Cart::instance('cart')->content() as $items )

                              <tr>

                                 <td style="color: black ">{{ $i++ }}</td>
                                 <td style="color: black ">{{ $items->model->name ?? '' }}</td>
                                 <td style="color: black ">{{ $items->model->price ?? '' }} F</td>
                                 <td style="color: black ">{{ $items->subtotal() ?? '' }} F</td>
                                 <td style="color: black ">{{ $items->qty ?? '' }} </td>
                                 <td style="color: black " class=" pull-right">

                                     <div class="btn-group btn-group-justified">
                                         <button style="color: white" type="button" class="btn btn-success"
                                         wire:click.prevent="decrement('{{ $items->rowId }}')"
                                         >

                                             <i class="material-icons"></i>-</button>
                                         <button style="color: white" type="button" class="btn btn-warning"
                                         wire:click.prevent="increment('{{ $items->rowId }}')"
                                         >
                                             <i class="material-icons">+</i>
                                              </button>
                                         <button type="button" class="btn btn-danger"
                                         wire:click.prevent="destroy('{{ $items->rowId }}')"
                                          >
                                             <i class="material-icons">delete</i>
                                           </button>
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
                                    <th>Total</th>
                                   <th>Quantité</th>
                                   <th>Actions</th>
                               </tr>
                           </tfoot>
                       </table>
                   </div>

                   @else

                   @endif
            </div>
        </div>
    </form>
     </div>




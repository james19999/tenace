 <form method="POST" action="{{ route('palceorder') }}">
    @csrf
     <div class="col-12 ">

        @if (Session::has('error'))
        <div class="alert alert-danger">
           <strong>{{ session('error') }}</strong>
           </div>
        @endif
         <div class="row mb-4" >
             <div class="col-md-3">

                 <div  style="padding-top: 10px">
                     <select class="js-example-basic-single  form-control" name="costumer_id">
                        <option value="default">Nouveau client</option>
                         @foreach ($Costumers as $Costumer )

                         <option value="{{ $Costumer->id }}">{{ $Costumer->name }} | {{ $Costumer->phone }} </option>

                         @endforeach
                      </select>
                 </div>
             </div>
             <div class="col-md-3" style="padding-top: 1%">
                 <input type="time" name="time" id="" value="{{old('time')}}"  placeholder="Heure" class="form-control" >
                 @error('time') <span class="text-danger">{{ $message }}</span>@enderror
             </div>
             <div class="col-md-3" style="padding-top: 1%">
                 <input type="number" name="tax" id="" value="{{old('tax',0)}}"  placeholder="Frais de livraison" class="form-control" >
                 @error('tax') <span class="text-danger">{{ $message }}</span>@enderror
             </div>
             <div class="col-md-3" style="padding-top: 1%">
                <button type="submit" style="color: white"
                       @if (Cart::instance('cart')->count()>0)

                       @else

                       disabled
                       @endif

                class="btn btn-success btn-block">Valider
                 ({{ Cart::instance('cart')->subtotal()  }}) F</button>
             </div>
             <input type="hidden" name="subtotal" value="{{ Cart::instance('cart')->subtotal() }}">
         </div>
         <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                    <label for="name">Nom:</label>
                    <input type="text" class="form-control" id="name"   name="name"  placeholder="Nom"  value="{{ old('name') }}" >
                    @error('name') <span class="text-danger">{{ $message }}</span>@enderror
                </div>


              </div>
              <div class="col-md-3">
                <div class="form-group">
                    <label for="phone">Téléphone:</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="Téléphone"  value="{{ old('phone') }}">
                    @error('phone') <span class="text-danger">{{ $message }}</span>@enderror
                </div>

              </div>
              <div class="col-md-3">
                <div class="form-group">
                    <label for="phone">Adresse:</label>
                    <input type="text" class="form-control" id="adresse" name="adresse" placeholder="Adresse"  value="{{ old('adresse') }}">
                    @error('adresse') <span class="text-danger">{{ $message }}</span>@enderror
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                    <label for="phone">Remise (optionnel):</label>
                    <input type="number" class="form-control" id="remise" name="remis" placeholder="Remise"  value="{{ old('remis',0) }}">
                    @error('remis') <span class="text-danger">{{ $message }}</span>@enderror
                </div>
              </div>
         </div>
          <div class="row">
             <div class="col-md-8">
                    <div class="form-group">
                        <label for="">Type de commande</label>
                         <select name="type" id="" class="form-control">
                             <option value="PU">Public</option>
                             <option value="PR">Privé</option>
                         </select>
                    </div>
             </div>
          </div>
          <div class="row">
             <div class="col-md-12">
                    <div class="form-group">
                        <label for="phone">Commentaire (optionnel):</label>
                          <textarea name="avis" placeholder="commentaire" class="form-control"  cols="30" rows="3">
                            {{ old('avis') }}
                          </textarea>
                        @error('avis') <span class="text-danger">{{ $message }}</span>@enderror
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
                                          @if ($items->model->qt_initial > $items->qty)
                                         wire:click.prevent="increment('{{ $items->rowId }}')"

                                          @else
                                           disabled
                                          @endif
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




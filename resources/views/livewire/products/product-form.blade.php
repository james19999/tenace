

<div  class="row d-flex justify-content-center">
      
     <div class="col-12 col-sm-7 " >
        <div class="card shadow">
            <div class="card-header" style="background-color: #7e1615">
                <h4 style="text-align: center;font-weight: 900 ;color: white">

                Ajouter les produits
                </h4>
            </div>
            <div class="card-body ">
                <form wire:submit.prevent="saveProducts">
                    @foreach($products as $index => $product)
                        <div class="form-row mb-3">
                            <div class="col-md-5">
                                <input type="text" class="form-control" wire:model="products.{{ $index }}.name" placeholder="Nom">
                            </div>
                            <div class="col-md-5">
                                <input type="number" class="form-control" wire:model="products.{{ $index }}.price" placeholder="Prix">
                            </div>
                            <div class="col">
                                @if ($index > 0)
                                    <button type="button" class="btn btn-danger" wire:click="removeProduct({{ $index }})">Supprimer</button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="form-row">
                        <div class="col">
                            <button type="button" class="btn btn-primary" wire:click="addProduct">Ajouter une ligne de produit</button>
                        </div>
                        <div class="col">
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        <div class="card-footer" style="background-color: #7e1615" >

        </div>
    </div>
   </div>
</div>
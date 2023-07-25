<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>TENACE COS</title>
</head>
<body>
    <div class="card shadow">

        <div class="card-body ">
            <div class="container table-responsive">
              <div class="row">
                <div class="col-12" id="printableArea">
                   <div class="row">
                        <div class="col-md-6">
                           <h2>Client</h2>
                           <p>Nom : {{ $ordercostumer->name ?? '' }}</p>
                           <p>Téléphone : {{ $ordercostumer->phone ?? '' }}</p>
                           <p>Adresse   : {{ $ordercostumer->adresse ?? ''}}</p>
                        </div>
                        <div class="col-md-6">
                            <h2>Commande</h2>
                            <p>Numéro : {{ $code }}  </p>
                            <p>Date  : {{ $created_at }}  </p>
                            <p>Heure de livraison  : {{ $time }}  </p>
                        </div>
                   </div>
                  <table class="table table-hover w-100 ">
                    <thead>
                      <tr>
                        <th>Désignation</th>
                        <th>Prix</th>
                        <th>Qauntité</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                       @foreach ($orders as $items )

                       <tr>
                         <td>{{ $items->product->name ?? ''}}</td>
                         <td>{{ $items->price ?? ''  }} F </td>
                         <td>{{ $items->quantity }}</td>
                         <td>{{ $items->quantity * $items->product->price }} F</td>
                       </tr>
                       @endforeach

                    </tbody>
                  </table>
                  <p>Sous total: {{ $subtotal }} F</p>
                  <p>Frais de livraison:  {{ $tax }} F</p>
                  <p>Total:  {{ $total }} F</p>
                </div>
              </div>
            </div>

        <div>
    <div>
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>

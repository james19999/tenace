@extends('layouts.admin')

@section('content')
<style>
    .card {
        width: 300px;
        background-color: #ffffff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.2s ease-in-out;
      }

      .card:hover {
        transform: scale(1.05);
      }

      .card-img {
        width: 100%;
        height: 150px;
        object-fit: cover;
      }

      .card-content {
        padding: 16px;
      }

      .card-title {
        font-size: 1.5rem;
        margin-bottom: 8px;
      }

      .card-text {
        color: #6c757d;
      }

      .btn {
        display: inline-block;
        padding: 8px 16px;
        margin-top: 8px;
        background-color: #007bff;
        color: #ffffff;
        text-decoration: none;
        border-radius: 4px;
      }
</style>
  <div class="container">
      <div class="col-md-12">
        <a href="{{ route('top-costumers') }}">Retour</a>
          <div class="row">
              <div class="col-md-4">
                <div class="card">
                    <div class="card-content">
                      <h5 class="card-title">{{ $costumers->name }}</h5>
                      <p class="card-text">
                       <strong>
                      {{ $costumers->phone }}
                      </strong>
                      </p>
                    </div>
                  </div>
              </div>
              <div class="col-md-4">
                <div class="card">
                    <div class="card-content">

                      <h5 class="card-title">Total commande
                        <span class="badge badge-primary">
                            ( {{ $costumers->orders_count }})
                        </span>
                         </h5>
                    </div>
                  </div>
              </div>
              <div class="col-md-4">
                <div class="card">
                    <div class="card-content">
                      <h5 class="card-title">Montant :  {{ $costumers->orders_sum_total }} F</h5>
                    </div>
                  </div>
              </div>
          </div>
           <div class="row mt-4">
                @forelse ($costumers->orders as $orde)

                <div class="col-md-6 card">
                    <p class="mt-2"> N° : {{ $orde->code }}  | {{ $orde->created_at }} </p>


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
                                  @foreach ($orde->orderItems  as $items)
                                      <tr style="color: black">
                                          <td>{{ $items->product->name ?? '' }}</td>
                                          <td>{{ $items->price ?? '' }} F </td>
                                          <td>{{ $items->quantity }}</td>
                                          <td>{{ $items->quantity * $items->product->price }} F</td>
                                      </tr>
                                  @endforeach

                              </tbody>
                          </table>
                          <strong>

                              <p>Total :  {{ $orde->total}} F</p>
                          </strong>

                </div>

                @empty

                @endforelse
           </div>

      </div>
  </div>
@endsection

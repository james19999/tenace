<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Facture {{ $order->code }}</title>


    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <link rel="shortcut icon" href="{{ asset('assets/images/tena.png') }}">


    <style>
        body {

            background: #f4f6f9;

            font-family: Arial, Helvetica, sans-serif;

        }



        .invoice {

            background: #fff;

            border-radius: 12px;

            box-shadow: 0 5px 20px rgba(0, 0, 0, .12);

            padding: 40px;

            margin-top: 30px;

        }



        .logo {

            width: 90px;

        }



        .invoice-title {

            font-size: 38px;

            font-weight: bold;

            color: #0d6efd;

        }



        .company-name {

            font-size: 25px;

            font-weight: bold;

        }



        .table thead th {

            background: #0d6efd !important;

            color: white !important;

        }



        .table td,
        .table th {

            vertical-align: middle;

        }



        .total-box {

            width: 100%;

        }



        .total-box td {

            padding: 8px;

        }



        .total-final {

            background: #0d6efd;

            color: white;

            font-size: 18px;

        }



        .signature {

            margin-top: 100px;

        }



        .footer {

            margin-top: 50px;

            text-align: center;

            color: #777;

        }




        @page {

            size: A4;

            margin: 15mm;

        }




        @media print {


            body {

                background: white !important;

            }


            .no-print {

                display: none !important;

            }


            .invoice {

                margin: 0;

                padding: 0;

                box-shadow: none;

                border: none;

            }



            .table {

                page-break-inside: auto;

            }


            tr {

                page-break-inside: avoid;

            }


        }
    </style>


</head>


<body>


    <div class="container">


        <!-- Boutons -->

        <div class="text-end mt-4 mb-3 no-print">


            <button onclick="window.print()" class="btn btn-primary">

                <i class="fa fa-print"></i>

                Imprimer

            </button>



            <a href="{{ url()->previous() }}" class="btn btn-secondary">

                <i class="fa fa-arrow-left"></i>

                Retour

            </a>


        </div>





        <!-- FACTURE -->

        <div class="invoice">



            <!-- HEADER -->

            <div class="row align-items-center">


                <div class="col-md-8">




                    <div class="company-name">

                        TENACOS-TOGO

                    </div>


                    <p class="text-muted mb-0">

                        Adresse entreprise

                        <br>

                        Téléphone : +22892521341

                        <br>

                        Email : contact@entreprise.com

                    </p>


                </div>





                <div class="col-md-6 text-end">


                    <h1 class="invoice-title">

                        FACTURE

                    </h1>


                    <h5>

                        N° {{ $order->code }}

                    </h5>


                    <span class="badge bg-success">

                        {{ $order->created_at->format('d/m/Y H:i') }}

                    </span>


                </div>



            </div>





            <hr>





            <!-- CLIENT -->


            <div class="row mb-4">


                <div class="col-md-6">


                    <h5 class="fw-bold">

                        Client

                    </h5>



                    <strong>

                        {{ $order->costumer->name ?? 'Client comptoir' }}

                    </strong>


                    <br>


                    {{ $order->costumer->phone ?? '' }}


                    <br>


                    {{ $order->costumer->adresse ?? '' }}



                </div>





                <div class="col-md-6 text-end">


                    <h5 class="fw-bold">

                        Commande

                    </h5>



                    <p class="mb-1">

                        <strong>N° :</strong>

                        {{ $order->code }}

                    </p>



                    <p class="mb-1">

                        <strong>Date :</strong>

                        {{ $order->created_at->format('d/m/Y') }}

                    </p>



                    <p>

                        <strong>Heure :</strong>

                        {{ $order->created_at->format('H:i') }}

                    </p>



                </div>



            </div>






            <!-- PRODUITS -->


            <table class="table table-bordered">


                <thead>


                    <tr>

                        <th>#</th>

                        <th>Désignation</th>

                        <th class="text-center">
                            Prix
                        </th>

                        <th class="text-center">
                            Qté
                        </th>

                        <th class="text-end">
                            Total
                        </th>


                    </tr>


                </thead>



                <tbody>


                    @foreach ($order->orderItems as $item)
                        <tr>


                            <td>

                                {{ $loop->iteration }}

                            </td>



                            <td>

                                {{ $item->product->name }}

                            </td>




                            <td class="text-center">

                                {{ number_format($item->price) }}

                                FCFA

                            </td>




                            <td class="text-center">

                                {{ $item->quantity }}

                            </td>




                            <td class="text-end fw-bold">


                                {{ number_format($item->price * $item->quantity) }}

                                FCFA


                            </td>



                        </tr>
                    @endforeach



                </tbody>


            </table>






            <!-- TOTAL -->


            <div class="row mt-4">


                <div class="col-md-6">


                    <h6>

                        Merci pour votre confiance.

                    </h6>


                    <small class="text-muted">

                        Cette facture tient lieu de reçu.

                    </small>


                </div>





                <div class="col-md-6">


                    <table class="table total-box">


                        <tr>


                            <td>

                                Sous-total

                            </td>


                            <td class="text-end">

                                {{ number_format($order->subtotal) }}

                                FCFA

                            </td>


                        </tr>





                        <tr>


                            <td>

                                Livraison

                            </td>


                            <td class="text-end">

                                {{ number_format($order->tax) }}

                                FCFA

                            </td>


                        </tr>





                        <tr>


                            <td>

                                Remise

                            </td>


                            <td class="text-end">

                                {{ $order->remis }} %

                            </td>


                        </tr>





                        <tr class="total-final">


                            <th>

                                TOTAL

                            </th>



                            <th class="text-end">


                                {{ number_format($order->montant) }}

                                FCFA


                            </th>



                        </tr>



                    </table>



                </div>



            </div>




        </div>



    </div>



</body>

</html>

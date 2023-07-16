
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">


<!-- Mirrored from htmlthemes.gitlab.io/admin/ by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 18 May 2023 20:57:06 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->
<head>
    <meta charset="UTF-8">
    <title>TENACE COSMETIQUE</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <link rel="shortcut icon" href="{{ asset('assets/images/tena.jpg') }}" type="image/png">
    <link rel="icon" href="{{ asset('assets/images/tena.jpg') }}" type="image/png">

    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css') }}">




    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/apexcharts/3.23.1/apexcharts.min.css') }}">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css
    ">



    @livewireStyles


</head>

<body>
    <div class="overlay-mask"></div>
    <div class="main-wrapper ">

             @include('partials.aside')

        <div class="right-area">
             @include('partials.header')

            <div class="main-content container">
                <div class="page-header">
                    <div>
                        {{-- <span class="h2" style="text-align: center">TENACE COSMETIQUE TOGO | {{ Auth::user()->name}}</span> --}}

                    </div>
                </div>
                @yield('content')

            </div>

        </div>
    </div>



    @livewireScripts


    <script src="{{asset('assets/js/vendor.bundle.js')}}"></script>


    <script src="{{ asset('assets/js/app.bundle.js') }}"></script>



    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>



    <script src="{{ asset('assets/js/demo/dashboard.bundle.js') }}"></script>



<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
  <script>
    $(document).ready(function() {
    $('#example').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    } );
    } );


 function printDiv(divName) {
    var printContents = document.getElementById(divName).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;

    window.print();

    document.body.innerHTML = originalContents;
    }
  </script>




</body>


<!-- Mirrored from htmlthemes.gitlab.io/admin/ by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 18 May 2023 21:03:03 GMT -->
</html>

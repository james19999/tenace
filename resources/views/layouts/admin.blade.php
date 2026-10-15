<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">



<head>
    <meta charset="UTF-8">
    <title>TENACE COSMETIQUE</title>
    @php
        $squareAdminPages = true;
    @endphp

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v=20261015.5">
    <link rel="stylesheet" href="{{ asset('assets/css/customer-service-notifications.css') }}?v=20261008.1">
    @if (request()->routeIs('service-cases.*'))
        <link rel="stylesheet" href="{{ asset('assets/css/customer-service-cases.css') }}?v=20261009.4">
    @endif
    @if (request()->routeIs('customer-loyalty.*'))
        <link rel="stylesheet" href="{{ asset('assets/css/customer-loyalty.css') }}?v=20261010.2">
    @endif
    @if (request()->routeIs('stock-control.*'))
        <link rel="stylesheet" href="{{ asset('assets/css/stock-control.css') }}?v=20261015.1">
    @endif
    @if (request()->routeIs('show-product'))
        <link rel="stylesheet" href="{{ asset('assets/css/product-detail.css') }}?v=20261012.3">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/legacy-square-pages.css') }}?v=20261013.8">

    <link rel="shortcut icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('assets/images/tena.png') }}" type="image/png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">




    <link rel="stylesheet" href="{{ asset('cdnjs.cloudflare.com/ajax/libs/apexcharts/3.23.1/apexcharts.min.css') }}">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css
    ">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    @livewireStyles

    @include('partials.pwa-head')

</head>

<body class="{{ $squareAdminPages ? 'legacy-square-pages' : '' }}">
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


    <script src="{{ asset('assets/js/vendor.bundle.js') }}"></script>


    <script src="{{ asset('assets/js/app.bundle.js') }}?v=20261012.2"></script>



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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#example').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        });
        $(document).ready(function() {
            $('#examples').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        });
        $(document).ready(function() {
            var $customerTable = $('#customer-table');
            if (!$customerTable.length) return;

            $customerTable.DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                ajax: $customerTable.data('source'),
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[1, 'asc']],
                columns: [
                    { data: 'number', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'phone', name: 'phone' },
                    { data: 'email', name: 'email' },
                    { data: 'address', name: 'adresse' },
                    { data: 'actions', orderable: false, searchable: false }
                ]
            });
        });


        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }


        // In your Javascript (external .js resource or <script> tag)
        $(document).ready(function() {
            $('.js-example-basic-single').select2();
            $('.js-customer-search').each(function() {
                var $customerSelect = $(this);
                $customerSelect.select2({
                    placeholder: 'Rechercher par nom ou téléphone',
                    minimumInputLength: 2,
                    ajax: {
                        url: $customerSelect.data('ajax-url'),
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { q: params.term || '', page: params.page || 1 };
                        },
                        processResults: function(data) { return data; }
                    }
                });
            });
        });



        function extraireNumeroMois() {
            // Obtenez la valeur de la date depuis l'élément d'entrée
            var dateInput = document.getElementById("dateInput").value;

            // Créez un objet de date à partir de la valeur de l'entrée
            var date = new Date(dateInput);

            // Obtenez le numéro du mois (de 0 à 11, où 0 représente janvier et 11 représente décembre)
            var numeroMois = date.getMonth() + 1;

            // Affichez le numéro du mois dans la console (vous pouvez le traiter comme vous le souhaitez)
            console.log("Numéro du mois : " + numeroMois);
        }
    </script>

    @include('partials.pwa-scripts')
    @if (request()->routeIs('stock-control.*'))
        <dialog class="stock-confirm-dialog" id="stockConfirmDialog" aria-labelledby="stockConfirmTitle" aria-describedby="stockConfirmMessage">
            <div class="stock-confirm-dialog-head"><span class="material-icons" aria-hidden="true">fact_check</span><button type="button" data-stock-confirm-cancel aria-label="Fermer">×</button></div>
            <h2 id="stockConfirmTitle">Confirmer l’action</h2>
            <p id="stockConfirmMessage"></p>
            <div class="stock-confirm-dialog-actions"><button type="button" class="stock-control-button" data-stock-confirm-cancel>Annuler</button><button type="button" class="stock-control-button stock-control-button-primary" id="stockConfirmAccept">Confirmer</button></div>
        </dialog>
        <script>
            (() => {
                const timers = new WeakMap();
                document.addEventListener('input', (event) => {
                    const input = event.target;
                    if (!(input instanceof HTMLInputElement) || !input.matches('.stock-live-search')) return;
                    const form = input.form;
                    if (!form) return;
                    window.clearTimeout(timers.get(input));
                    timers.set(input, window.setTimeout(() => {
                        if (typeof form.requestSubmit === 'function') form.requestSubmit();
                        else form.submit();
                    }, 550));
                });

                const dialog = document.getElementById('stockConfirmDialog');
                const accept = document.getElementById('stockConfirmAccept');
                let pendingForm = null;
                if (!dialog || !accept) return;
                document.addEventListener('submit', (event) => {
                    const form = event.target;
                    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-stock-confirm-title')) return;
                    event.preventDefault();
                    pendingForm = form;
                    dialog.querySelector('#stockConfirmTitle').textContent = form.dataset.stockConfirmTitle || 'Confirmer l’action';
                    dialog.querySelector('#stockConfirmMessage').textContent = form.dataset.stockConfirmMessage || '';
                    dialog.showModal();
                });
                dialog.querySelectorAll('[data-stock-confirm-cancel]').forEach((button) => button.addEventListener('click', () => dialog.close()));
                dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
                dialog.addEventListener('close', () => { pendingForm = null; });
                accept.addEventListener('click', () => {
                    if (!pendingForm) return;
                    const form = pendingForm;
                    pendingForm = null;
                    form.removeAttribute('data-stock-confirm-title');
                    form.removeAttribute('data-stock-confirm-message');
                    dialog.close();
                    form.requestSubmit();
                });
            })();
        </script>
    @endif
</body>


</html>

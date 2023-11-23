@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<canvas id="productOrdersChart" width="400" height="200"></canvas>
<script>


    var productOrdersData = @json($productOrders);

    var productNames = productOrdersData.map(function(item) {
        return item.name;
    });

    var orderCounts = productOrdersData.map(function(item) {
        return item.order_count;
    });

    var ctx = document.getElementById('productOrdersChart').getContext('2d');

    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: productNames,
            datasets: [{
                label: 'Nombre de commandes',
                data: orderCounts,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endsection


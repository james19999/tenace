@extends('layouts.admin')

@section('content')
    <form method="GET">
        <select name="year" onchange="this.form.submit()">

            @for ($y = now()->year; $y >= 2020; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>

                    {{ $y }}

                </option>
            @endfor

        </select>
    </form>
    <table class="table table-bordered">

        <thead>

            <tr>
                <th>Rang</th>
                <th>Livreur</th>
                <th>Commandes livrées</th>
                <th>Montant générer </th>
            </tr>

        </thead>

        <tbody>

            @foreach ($ranking as $index => $item)
                <tr>

                    <td>
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $item->name }}
                    </td>

                    <td>
                        {{ $item->total_deliveries }}
                    </td>

                    <td>
                        {{ number_format($item->total_revenue, 0, ',', ' ') }} FCFA
                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>
@endsection

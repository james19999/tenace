<div class="p-6 space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">

        <div>
            <h1 class="text-2xl font-bold">
                Audit des ventes produits
            </h1>

            <p class="text-gray-500 text-sm">
                Rapport journalier, hebdomadaire, mensuel et annuel
            </p>
        </div>

        <button onclick="window.print()" class="px-5 py-2 bg-[#43A047] text-white rounded-lg">

            🖨 Imprimer

        </button>

    </div>

    {{-- KPI --}}
    <div class="grid md:grid-cols-5 gap-4">

        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">
                Aujourd'hui
            </div>

            <div class="text-2xl font-bold">
                {{ number_format($this->totalDaily) }}
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">
                Cette semaine
            </div>

            <div class="text-2xl font-bold">
                {{ number_format($this->totalWeekly) }}
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">
                Ce mois
            </div>

            <div class="text-2xl font-bold">
                {{ number_format($this->totalMonthly) }}
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">
                Cette année
            </div>

            <div class="text-2xl font-bold text-green-700">
                {{ number_format($this->totalYearly) }}
            </div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">
                CA annuel
            </div>

            <div class="text-2xl font-bold text-blue-700">
                {{ number_format($this->totalAmount) }} XOF
            </div>
        </div>

    </div>

    {{-- TABLEAU --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-gray-100">

                <tr>
                    <th class="px-4 py-3 text-left">
                        Produit
                    </th>

                    <th class="px-4 py-3 text-center">
                        Jour
                    </th>

                    <th class="px-4 py-3 text-center">
                        Semaine
                    </th>

                    <th class="px-4 py-3 text-center">
                        Mois
                    </th>

                    <th class="px-4 py-3 text-center">
                        Année
                    </th>

                    <th class="px-4 py-3 text-right">
                        CA Annuel
                    </th>
                </tr>

            </thead>

            <tbody>

                @forelse($reports as $report)
                    <tr class="border-b hover:bg-gray-50">

                        <td class="px-4 py-3 font-medium">
                            {{ $report->name }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{ number_format($report->daily_qty) }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{ number_format($report->weekly_qty) }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{ number_format($report->monthly_qty) }}
                        </td>

                        <td class="px-4 py-3 text-center font-semibold">
                            {{ number_format($report->yearly_qty) }}
                        </td>

                        <td class="px-4 py-3 text-right text-green-700 font-bold">
                            {{ number_format($report->yearly_amount) }} XOF
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="text-center py-10 text-gray-500">
                            Aucun mouvement trouvé
                        </td>

                    </tr>
                @endforelse

            </tbody>

        </table>

    </div>

</div>

<?php

namespace App\Support;

final class WestAfricaShippingCatalog
{
    private static ?array $worldCities = null;

    /** @return array<string, array{label: string, cities: list<string>}> */
    public static function countries(): array
    {
        $countries = [
            'BJ' => ['label' => 'Bénin', 'cities' => ['Cotonou', 'Porto-Novo', 'Parakou', 'Abomey-Calavi', 'Djougou', 'Bohicon']],
            'BF' => ['label' => 'Burkina Faso', 'cities' => ['Ouagadougou', 'Bobo-Dioulasso', 'Koudougou', 'Banfora', 'Ouahigouya', 'Kaya']],
            'CI' => ['label' => 'Côte d’Ivoire', 'cities' => ['Abidjan', 'Yamoussoukro', 'Bouaké', 'San-Pédro', 'Daloa', 'Korhogo', 'Man']],
            'GN' => ['label' => 'Guinée', 'cities' => ['Conakry', 'Nzérékoré', 'Kindia', 'Kankan', 'Labé', 'Boké']],
            'ML' => ['label' => 'Mali', 'cities' => ['Bamako', 'Sikasso', 'Ségou', 'Mopti', 'Kayes', 'Koutiala']],
            'MR' => ['label' => 'Mauritanie', 'cities' => ['Nouakchott', 'Nouadhibou', 'Rosso', 'Atar', 'Kaédi', 'Kiffa']],
            'NE' => ['label' => 'Niger', 'cities' => ['Niamey', 'Zinder', 'Maradi', 'Agadez', 'Tahoua', 'Dosso']],
            'SN' => ['label' => 'Sénégal', 'cities' => ['Dakar', 'Thiès', 'Saint-Louis', 'Touba', 'Kaolack', 'Ziguinchor', 'Mbour']],
            'TG' => ['label' => 'Togo', 'cities' => ['Lomé', 'Sokodé', 'Kara', 'Kpalimé', 'Atakpamé', 'Dapaong']],
        ];

        if (class_exists(\ResourceBundle::class)) {
            $names = \ResourceBundle::create('fr', 'ICUDATA-region')?->get('Countries');
            if ($names) {
                foreach ($names as $code => $label) {
                    if (preg_match('/^[A-Z]{2}$/', (string) $code) && is_string($label) && ! isset($countries[$code])) {
                        $countries[$code] = ['label' => $label, 'cities' => []];
                    }
                }
            }
        } else {
            foreach ([
                'FR' => 'France', 'BE' => 'Belgique', 'CH' => 'Suisse', 'CA' => 'Canada', 'US' => 'États-Unis',
                'GB' => 'Royaume-Uni', 'DE' => 'Allemagne', 'ES' => 'Espagne', 'IT' => 'Italie', 'PT' => 'Portugal',
                'MA' => 'Maroc', 'NG' => 'Nigéria', 'GH' => 'Ghana', 'ZA' => 'Afrique du Sud',
            ] as $code => $label) {
                $countries[$code] ??= ['label' => $label, 'cities' => []];
            }
        }

        // Keep both Congo country codes available even when the host's ICU
        // country list is incomplete or does not provide localized labels.
        $countries['CG'] = [
            'label' => 'Congo',
            'cities' => ['Brazzaville', 'Pointe-Noire', 'Dolisie', 'Nkayi', 'Ouesso'],
        ];
        $countries['CD'] = [
            'label' => 'République démocratique du Congo',
            'cities' => ['Kinshasa', 'Lubumbashi', 'Goma', 'Kisangani', 'Bukavu', 'Matadi'],
        ];

        uasort($countries, fn (array $left, array $right): int => strcmp($left['label'], $right['label']));

        return $countries;
    }

    /** @return list<string> */
    public static function cities(string $countryCode): array
    {
        $code = strtoupper(trim($countryCode));
        $country = self::countries()[$code] ?? null;
        if ($country === null) {
            return [];
        }

        if (self::$worldCities === null) {
            $path = resource_path('data/world-cities.json');
            $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
            self::$worldCities = is_array($decoded) ? $decoded : [];
        }

        return array_values(array_unique(array_merge(
            $country['cities'] ?? [],
            self::$worldCities[$code] ?? []
        )));
    }
}

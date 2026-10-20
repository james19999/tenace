<?php

namespace App\Support;

final class WestAfricaShippingCatalog
{
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

        uasort($countries, fn (array $left, array $right): int => strcmp($left['label'], $right['label']));

        return $countries;
    }
}

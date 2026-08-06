<?php

return [
    'supported_locales' => [
        'en' => 'English',
        'es' => 'Spanish',
    ],

    'formats' => [
        'en' => [
            'date' => 'm/d/Y',
            'date_time' => 'm/d/Y g:i A',
            'number_locale' => 'en_US',
            'currency' => 'USD',
            'area_unit' => 'acres',
        ],
        'es' => [
            'date' => 'd/m/Y',
            'date_time' => 'd/m/Y H:i',
            'number_locale' => 'es_ES',
            'currency' => 'USD',
            'area_unit' => 'hectares',
        ],
    ],

    'area_units' => [
        'acres' => [
            'label' => 'Acres',
            'abbreviation' => 'ac',
        ],
        'hectares' => [
            'label' => 'Hectares',
            'abbreviation' => 'ha',
        ],
    ],
];

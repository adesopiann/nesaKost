<?php

return [
    'campuses' => [
        'kampus_1' => [
            'name' => 'UNESA Kampus 1 Ketintang',
            'latitude' => env('UNESA_K1_LATITUDE'),
            'longitude' => env('UNESA_K1_LONGITUDE'),
        ],
        'kampus_2' => [
            'name' => 'UNESA Kampus 2 Lidah Wetan',
            'latitude' => env('UNESA_K2_LATITUDE'),
            'longitude' => env('UNESA_K2_LONGITUDE'),
        ],
    ],

    'geocoding' => [
    'user_agent' => env('APP_NAME', 'nesaKost') . ' (' . env('APP_URL', 'http://localhost') . ')',
    'contact' => env('EKOST_GEOCODING_CONTACT'),
    ],


    'search_radius' => 5,
    'per_page' => 9,

    // Sesuaikan jika nilai status di database berbeda.
    'property_active_status' => 'active',
    'room_available_status' => 'available',
];

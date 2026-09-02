<?php
return [
    'app_name' => 'Los Santos Roleplay Vietnamese',
    'base_url' => '/lsrp-ucp',
    'max_characters' => 3,
    'bcrypt_cost' => 12,

    // Chien dich dang ky som #TWOYEARS. Tat enabled khi ket thuc pre-registration.
    'twoyears' => [
        'enabled' => true,
        'package_code' => '#TWOYEARS',
        'vehicle_model' => 462, // Faggio
        'vehicle_delivery_enabled' => true, // V4.5 cap Faggio truc tiep vao player_vehicles khi nhan vat dau tien duoc phe duyet.
    ],

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'lsrp',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    // Thay # bang link cong dong that cua server khi san sang.
    'socials' => [
        'discord' => '#',
        'facebook' => 'https://www.facebook.com/lsrpvn.official',
        'youtube' => 'https://www.youtube.com/@lsrvn',
    ],

    // Master account usernames allowed to use /admin.
    'admin_usernames' => [
        'abcde'
    ],
];

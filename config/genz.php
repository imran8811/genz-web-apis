<?php

return [
    'restaurant' => [
        'name' => 'GEN Z Foods',
        'tagline' => 'Premium Fast Food Restaurant',
        'address' => 'Kacha Phatak, Sher Shah Road, Multan',
        'phone' => '03 000-911-000',
        'whatsapp' => '03000911000',
        'timing' => '02:00 PM - 2:00 AM',
        'features' => [
            'Family Hall', 'Relax Environment', 'Roof Top Sitting',
            'Professional Riders', 'Quick Delivery', 'Take Away',
            'Dine In', 'Quick Services',
        ],
    ],
    'currency' => [
        'code' => 'PKR',
        'symbol' => 'Rs',
    ],
    // Flat delivery fee (PKR). Set 0 for free delivery.
    'delivery_fee' => 0,

    // Canonical menu feed — source of truth is now genz-admin (genz-admin-apis),
    // pulled by `php artisan menu:sync` to keep a trusted price copy for checkout
    // re-pricing. ADMIN_MENU_URL is preferred; RMS_MENU_URL stays as a fallback
    // for backward compatibility during cutover.
    'admin_menu_url' => env('ADMIN_MENU_URL'),
    'rms_menu_url' => env('RMS_MENU_URL'),

    // Public URL of the customer website (genz-web), used to build links in
    // transactional emails (e.g. the "View order" button).
    'web_app_url' => rtrim(env('WEB_APP_URL', 'https://genzfoods.pk'), '/'),

    // Support / contact shown in emails.
    'support_email' => env('SUPPORT_EMAIL', 'support@genzfoods.pk'),

    // Forward online orders into the RMS (genz-rms-apis) so they appear in the
    // POS. Secret is a shared key both apps hold; the RMS verifies it.
    'rms' => [
        'orders_url' => env('RMS_ORDERS_URL', 'https://api.rms.genzfoods.pk/api/integration/orders'),
        'secret' => env('RMS_INTEGRATION_SECRET'),
    ],
];

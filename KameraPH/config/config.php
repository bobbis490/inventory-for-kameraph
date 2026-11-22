<?php
return [
    // Unified Azure MySQL Database (shared by all 10 modules)
    "host"    => "kamaera-ph-mysql.mysql.database.azure.com",
    "port"    => 3306,
    "dbname"  => "kph-database",
    "user"    => "kph_app",
    "pass"    => "KphApp_2026!",
    "charset" => "utf8mb4",

    // Local XAMPP fallback (uncomment only if testing offline without internet):
    // "host" => "localhost", "dbname" => "kph-database", "user" => "root", "pass" => "",

    "modules" => [
        "accounts"    => "http://localhost/kph_user_account_identity/api",
        "sellers"     => "http://localhost/kph_seller_verification/api",
        "catalog"     => "http://localhost/kph_catalog_specification/api",
        "engagement"  => "http://localhost/kph_marketing_engagement/api",
        "shopping"    => "http://localhost/kph_shopping_promotions/api",
        "orders"      => "http://localhost/kph_order_payment/api",
        "fulfillment" => "http://localhost/kph_fulfillment_logistics/api",
        "shipping"    => "http://localhost/kph_shipping_delivery/api",
        "support"     => "http://localhost/kph_post_purchase_support/api",
        "admin"       => "http://localhost/kph_administration_analytics/api",
    ],
];

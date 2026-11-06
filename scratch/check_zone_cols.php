<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = \Illuminate\Support\Facades\Schema::getColumnListing('zones');
echo "Zones columns:\n";
print_r($cols);

$zone = \App\Models\Zone::first();
echo "\nFirst Zone data:\n";
if ($zone) {
    echo "ID: " . $zone->id . "\n";
    echo "Name: " . $zone->name . "\n";
    echo "minimum_shipping_charge: " . $zone->minimum_shipping_charge . "\n";
    echo "per_km_shipping_charge: " . $zone->per_km_shipping_charge . "\n";
    echo "maximum_shipping_charge: " . $zone->maximum_shipping_charge . "\n";
    echo "increased_delivery_fee: " . $zone->increased_delivery_fee . "\n";
    echo "increased_delivery_fee_status: " . $zone->increased_delivery_fee_status . "\n";
    echo "max_cod_order_amount: " . $zone->max_cod_order_amount . "\n";
}

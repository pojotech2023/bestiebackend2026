<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Admin instant_order: " . var_export(\App\Models\BusinessSetting::where('key', 'instant_order')->first()?->value, true) . PHP_EOL;
    $restaurant = \App\Models\Restaurant::where('name', 'like', '%Test%')->first();
    if ($restaurant) {
        echo "Found restaurant: " . $restaurant->name . " (ID: " . $restaurant->id . ")" . PHP_EOL;
        echo "Restaurant instant_order: " . var_export($restaurant->restaurant_config?->instant_order, true) . PHP_EOL;
        echo "Restaurant schedule_order: " . var_export($restaurant->schedule_order, true) . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

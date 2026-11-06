<?php
require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); $setting = DB::table('business_settings')->where('key', 'customer_verification')->first(); echo json_encode($setting);

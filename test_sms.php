<?php
require 'vendor/autoload.php'; $app = require_once 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); $settings = DB::table('business_settings')->whereIn('key', ['msg91_sms', 'twilio_sms', '2factor_sms', 'nexmo_sms'])->get(); echo json_encode($settings);

<?php

return [
    'env' => env('PAYTM_ENVIRONMENT', 'production'), // values : (local | production)
    'merchant_id' => env('PAYTM_MERCHANT_ID', 'ZYcedP50108107065518'),
    'merchant_key' => env('PAYTM_MERCHANT_KEY', 'i5LjdOJZaBe4pmTC'),
    'merchant_website' => env('PAYTM_MERCHANT_WEBSITE', 'https://bestie.co.in/'),
    'channel' => env('PAYTM_CHANNEL', 'WEB'),
    'industry_type' => env('PAYTM_INDUSTRY_TYPE', ''),  
];

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FCM DEBUG ===\n\n";

// 1. File Check
$storagePath = storage_path('app/firebase/service-account.json');
$basePath    = base_path(env('FIREBASE_CREDENTIALS_PATH'));

echo "1. FILE CHECK:\n";
echo "   storage_path exists: " . (file_exists($storagePath) ? "YES ✓" : "NO ✗") . "\n";
echo "   base_path exists:    " . (file_exists($basePath)    ? "YES ✓" : "NO ✗") . "\n";
echo "   Resolved path used:  " . (file_exists($storagePath) ? $storagePath : $basePath) . "\n\n";

// 2. ENV Vars
echo "2. ENV VARS:\n";
echo "   FIREBASE_BUSINESS_CREDENTIALS: " . env('FIREBASE_BUSINESS_CREDENTIALS', 'NOT SET') . "\n";
echo "   FIREBASE_CUSTOMER_CREDENTIALS: " . env('FIREBASE_CUSTOMER_CREDENTIALS', 'NOT SET') . "\n";
echo "   FIREBASE_BUSINESS_URL:         " . env('FIREBASE_BUSINESS_URL',         'NOT SET') . "\n";
echo "   FIREBASE_CUSTOMER_URL:         " . env('FIREBASE_CUSTOMER_URL',         'NOT SET') . "\n\n";

// 3. Service Account JSON
echo "3. SERVICE ACCOUNT JSON:\n";
$path = file_exists($storagePath) ? $storagePath : $basePath;
if (file_exists($path)) {
    $json = json_decode(file_get_contents($path), true);
    echo "   type:         " . ($json['type']         ?? 'MISSING ✗') . "\n";
    echo "   project_id:   " . ($json['project_id']   ?? 'MISSING ✗') . "\n";
    echo "   client_email: " . ($json['client_email'] ?? 'MISSING ✗') . "\n";
    echo "   private_key:  " . (!empty($json['private_key']) ? "Present ✓" : "MISSING ✗") . "\n";
    echo "   key_preview:  " . substr($json['private_key'] ?? '', 0, 40) . "\n\n";
} else {
    echo "   File not found! ✗\n\n";
}

// 4. Access Token
echo "4. ACCESS TOKEN:\n";
try {
    $fcm        = app(\App\Services\FCMService::class);
    $reflection = new ReflectionClass($fcm);
    $prop       = $reflection->getProperty('accessToken');
    $prop->setAccessible(true);
    $token = $prop->getValue($fcm);
    echo "   Token: " . ($token ? substr($token, 0, 40) . "... ✓" : "NULL ✗ — Token fetch failed!") . "\n\n";
} catch (\Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n\n";
}

// 5. Business App
echo "5. TEST SEND → BUSINESS APP:\n";
$businessFcmToken = 'favXNyulTLiIptKssXq50W:APA91bF66M9ZlQVdVY3-R9qp83Rh5Ic1I_eA9yoD8R-t9IxGqsjh7SYUhXLCrrgb-9-PDCSkiHChL3b2nDS7XLjJRsePqUtpQEyDRGY0B1BlEy9FjoQTOFo';

if ($businessFcmToken === 'BUSINESS_APP_FCM_TOKEN') {
    echo "   Skipped — DB business fcm_token\n";
    echo "   Query: DB::table('business_profiles')->whereNotNull('fcm_token')->value('fcm_token');\n\n";
} else {
    try {
        $fcm      = app(\App\Services\FCMService::class);
        $response = $fcm->sendToBusinessApp(
            $businessFcmToken,
            'Business FCM',
            'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since 1966, when designers at Letraset',
            url('storage/notifications/notification.png')
        );
        echo "   Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    } catch (\Throwable $e) {
        echo "   ERROR: " . $e->getMessage() . "\n\n";
    }
}

// 6. Customer App
echo "6. TEST SEND → CUSTOMER APP:\n";
$customerFcmToken = 'dLcLogwuRXqeQaL7bLa82J:APA91bE6CaAhEQes1rxmLksxyx2Z2SD3KIryck0pzGzUt6wwtAIo3aNgaIILrvsU6R1tJ7TpPgNAEo8WXq-nwhJye29o7L6OaNJSlnQSe9pkY443zcu_zeQ';

if ($customerFcmToken === 'CUSTOMER_APP_FCM_TOKEN') {
    echo "   Skipped — DB customer fcm_token\n";
    echo "   Query: DB::table('customer_profiles')->whereNotNull('fcm_token')->value('fcm_token');\n\n";
} else {
    try {
        $fcm      = app(\App\Services\FCMService::class);
        $response = $fcm->sendToCustomerApp(
            $customerFcmToken,
            'Customer FCM',
            'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since 1966, when designers at Letraset',
            url('storage/notifications/notification.png')
        );
        echo "   Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    } catch (\Throwable $e) {
        echo "   ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "=== DEBUG COMPLETE ===\n";
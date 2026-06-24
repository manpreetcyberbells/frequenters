<?php
namespace App\Services;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FCMService
{
    // protected $accessToken;
    // public function __construct()
    // {
    //     $this->accessToken = $this->getAccessToken();
    // }

    // PRODUCTION PATH
    // private function getAccessToken()
    // {
    //     $serviceAccountFile = base_path(env('FIREBASE_CREDENTIALS_PATH'));
    //     // die($serviceAccountFile);
    //     $scopes = [env('FIREBASE_SCOPES_PATH')];
    //     $credentials = new ServiceAccountCredentials($scopes, $serviceAccountFile);
    //     $token = $credentials->fetchAuthToken();
    //     return $token['access_token'] ?? null;
    // }

    // Dev Path
    // private function getAccessToken()
    // {
    //     $serviceAccountFile = storage_path('app/firebase/service-account.json');
    //     $scopes = [env('FIREBASE_SCOPES_PATH')];
    //     $credentials = new ServiceAccountCredentials($scopes, $serviceAccountFile);
    //     $token = $credentials->fetchAuthToken();
    //     return $token['access_token'] ?? null;
    // }

    // private function getAccessToken()
    // {
    //     $serviceAccountFile = file_exists(storage_path('app/firebase/service-account.json')) ? storage_path('app/firebase/service-account.json') : base_path(env('FIREBASE_CREDENTIALS_PATH'));
    //     $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
    //     $credentials = new ServiceAccountCredentials($scopes, $serviceAccountFile);
    //     $token       = $credentials->fetchAuthToken();
    //     return $token['access_token'] ?? null;
    // }

    // public function sendNotificationToDevice($fcmToken, $title, $body, $imageUrl = null, $data = [])
    // {
    //     $url = env('FIREBASE_SEND_MESSAGE_URL');
    //     if (empty($data)) {
    //         $data = [
    //             'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
    //             'type' => 'default',
    //         ];
    //     }

    //     $payload = [
    //         "message" => [
    //             "token" => $fcmToken,
    //             "notification" => [
    //                 "title" => $title,
    //                 "body"  => $body,
    //             ],
    //             "data" => $data,
    //         ]
    //     ];

    //     if ($imageUrl) {
    //         $payload['message']['notification']['image'] = $imageUrl;
    //     }

    //     // if (!empty($data)) {
    //     //     $payload['message']['data'] = $data;
    //     // }

    //     $response = Http::withToken($this->accessToken)
    //         ->withHeaders([
    //             'Content-Type' => 'application/json',
    //         ])
    //         ->post($url, $payload);
    //     return $response->json();
    // }

    public function sendToBusinessApp(string $fcmToken, string $title, string $body, $imageUrl = null): array
    {
        $token = $this->getAccessToken(
            storage_path('app/firebase/business-service-account.json')
        );
        return $this->send($token, env('FIREBASE_BUSINESS_URL'), $fcmToken, $title, $body, $imageUrl);
    }

    public function sendToCustomerApp(string $fcmToken, string $title, string $body, $imageUrl = null): array
    {
        $token = $this->getAccessToken(
            storage_path('app/firebase/customer-service-account.json')
        );
        return $this->send($token, env('FIREBASE_CUSTOMER_URL'), $fcmToken, $title, $body, $imageUrl);
    }

    private function getAccessToken(string $filePath): ?string
    {
        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $filePath
        );
        $token = $credentials->fetchAuthToken();
        return $token['access_token'] ?? null;
    }

    private function send(string $accessToken, string $url, string $fcmToken, string $title, string $body, $imageUrl = null): array
    {
        $payload = [
            'message' => [
                'token'        => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type'         => 'default',
                ],
            ],
        ];

        if ($imageUrl) {
            $payload['message']['notification']['image'] = $imageUrl;
        }

        $response = Http::withToken($accessToken)
            ->post($url, $payload);

        if (!$response->successful()) {
            \Log::error('FCM Error', ['status' => $response->status(), 'body' => $response->json()]);
        }

        return $response->json();
    }
}


<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FCMService
{
    protected $accessToken;

    public function __construct()
    {
        $this->accessToken = $this->getAccessToken();
    }

    private function getAccessToken()
    {
        $serviceAccountFile = base_path(env('FIREBASE_CREDENTIALS_PATH'));
        // die($serviceAccountFile);
        $scopes = [env('FIREBASE_SCOPES_PATH')];
        $credentials = new ServiceAccountCredentials($scopes, $serviceAccountFile);
        $token = $credentials->fetchAuthToken();
        return $token['access_token'] ?? null;
    }

    public function sendNotificationToDevice($fcmToken, $title, $body, $imageUrl = null, $data = [])
    {
        $url = env('FIREBASE_SEND_MESSAGE_URL');

        if (empty($data)) {
            $data = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'type' => 'default',
            ];
        }

        $payload = [
            "message" => [
                "token" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body"  => $body,
                ],
                "data" => $data,
            ]
        ];

        if ($imageUrl) {
            $payload['message']['notification']['image'] = $imageUrl;
        }

        // if (!empty($data)) {
        //     $payload['message']['data'] = $data;
        // }

        $response = Http::withToken($this->accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        return $response->json();
    }
}

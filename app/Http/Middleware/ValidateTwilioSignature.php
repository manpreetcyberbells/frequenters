<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

class ValidateTwilioSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('connect.validate_twilio_signature')) {
            return $next($request);
        }

        $token     = (string) config('connect.twilio.auth_token');
        $signature = (string) $request->header('X-Twilio-Signature', '');
        $url       = $request->fullUrl();
        $params    = $request->isMethod('POST') ? $request->post() : [];

        $validator = new RequestValidator($token);

        if (! $validator->validate($signature, $url, $params)) {
            Log::warning('[Connect] Rejected webhook with invalid Twilio signature', [
                'url' => $request->path(),
                'ip'  => $request->ip(),
            ]);

            return response('Invalid signature', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

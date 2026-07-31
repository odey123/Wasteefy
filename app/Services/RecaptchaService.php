<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret'),
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        return $response->ok() && $response->json('success') === true;
    }
}

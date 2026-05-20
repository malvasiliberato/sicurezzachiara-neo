<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $homePage = $request->input('ui_home_page', 'companies');

        $target = match ($homePage) {
            'dashboard' => route('dashboard'),
            'method' => route('sicurezzachiara.method'),
            default => route('companies.index'),
        };

        $intended = $request->session()->get('url.intended');

        if ($intended && in_array(parse_url($intended, PHP_URL_PATH) ?: '/', ['/', '/login'], true)) {
            $request->session()->forget('url.intended');
        }

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($target);
    }
}

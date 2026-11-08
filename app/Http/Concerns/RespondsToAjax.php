<?php

namespace App\Http\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait RespondsToAjax
{
    /**
     * Respons AJAX (Accept: application/json) dengan JSON {ok, message},
     * selain itu tetap redirect + flash 'status' seperti biasa.
     */
    protected function ajaxOrRedirect(Request $request, string $message, RedirectResponse $redirect): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return $redirect->with('status', $message);
    }
}

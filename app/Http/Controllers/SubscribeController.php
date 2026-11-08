<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SubscribeController extends Controller
{
    public function store(Request $request): JsonResponse|View
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);

        $subscriber = Subscriber::updateOrCreate(
            ['email' => strtolower($data['email'])],
            ['token' => Subscriber::newToken(), 'is_confirmed' => false]
        );

        Mail::to($subscriber->email)->queue(new \App\Mail\SubscriptionConfirm($subscriber));

        if ($request->expectsJson()) {
            return response()->json(['data' => ['message' => 'Tautan konfirmasi telah dikirim ke '.$subscriber->email]]);
        }

        return view('public.subscribe-confirmation', ['email' => $subscriber->email]);
    }

    public function confirm(Request $request): View
    {
        $subscriber = Subscriber::where('token', $request->query('token'))->firstOrFail();
        $subscriber->update(['is_confirmed' => true]);

        return view('public.subscribe-confirmed');
    }

    public function unsubscribe(Request $request): View
    {
        $subscriber = Subscriber::where('token', $request->query('token'))->first();
        $subscriber?->delete();

        return view('public.subscribe-confirmed');
    }
}

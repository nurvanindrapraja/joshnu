<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class AdminSubscriberController extends Controller
{
    /**
     * Daftar email yang berlangganan JOSHNU (footer "Berlangganan JOSHNU").
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $query = Subscriber::query()->orderBy('id', 'desc');

        if ($q !== '') {
            $query->where('email', 'like', '%'.$q.'%');
        }

        if ($status === 'confirmed') {
            $query->where('is_confirmed', true);
        } elseif ($status === 'pending') {
            $query->where('is_confirmed', false);
        }

        $subscribers = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Subscriber::count(),
            'confirmed' => Subscriber::where('is_confirmed', true)->count(),
            'pending' => Subscriber::where('is_confirmed', false)->count(),
        ];

        return view('admin.subscribers', compact('subscribers', 'stats', 'q', 'status'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['user', 'action', 'entity_type', 'from', 'to']);

        $logs = AuditLog::with('user')
            ->when(! empty($filters['user']), fn ($q) => $q->where('user_id', $filters['user']))
            ->when(! empty($filters['action']), fn ($q) => $q->where('action', 'like', '%'.$filters['action'].'%'))
            ->when(! empty($filters['entity_type']), fn ($q) => $q->where('entity_type', $filters['entity_type']))
            ->when(! empty($filters['from']), fn ($q) => $q->where('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->where('created_at', '<=', $filters['to']))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit-logs', compact('logs', 'filters'));
    }
}

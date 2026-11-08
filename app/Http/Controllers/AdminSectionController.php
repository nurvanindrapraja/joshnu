<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminSectionController extends Controller
{
    public function index(): View
    {
        $sections = $this->listData();

        return view('admin.sections', ['sections' => $sections]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:sections,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $slug = $this->uniqueSlug($data['name']);

        $section = Section::create($data + [
            'slug' => $slug,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        AuditLog::log($request->user()->id, 'section.store', 'Section', $section->id);

        $message = "Section \"{$section->name}\" berhasil ditambahkan.";
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'sections' => $this->listData()]);
        }

        return back()->with('status', $message);
    }

    public function update(Request $request, Section $section): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:sections,name,'.$section->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $section->update($data + [
            'sort_order' => $data['sort_order'] ?? $section->sort_order,
            'is_active' => (bool) ($data['is_active'] ?? $section->is_active),
        ]);

        AuditLog::log($request->user()->id, 'section.update', 'Section', $section->id);

        $message = "Section \"{$section->name}\" berhasil diperbarui.";
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'sections' => $this->listData()]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Section $section): JsonResponse|RedirectResponse
    {
        // Section yang sudah memiliki artikel (publikasi) tidak dapat dihapus
        if ($section->publications()->exists()) {
            $message = "Section \"{$section->name}\" tidak dapat dihapus karena sudah memiliki artikel.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return back()->with('error', $message);
        }

        if ($section->submissions()->exists()) {
            $message = "Section \"{$section->name}\" tidak dapat dihapus karena masih memiliki submission.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return back()->with('error', $message);
        }

        $name = $section->name;
        $section->delete();
        AuditLog::log($request->user()->id, 'section.destroy', 'Section', $section->id);

        $message = "Section \"{$name}\" berhasil dihapus.";
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'sections' => $this->listData()]);
        }

        return back()->with('status', $message);
    }

    private function listData()
    {
        return Section::query()
            ->withCount(['publications', 'submissions'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'section';
        $slug = $base;
        $i = 2;
        while (Section::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}

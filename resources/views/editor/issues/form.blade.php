@extends('layouts.workspace')
@section('title', $issue ? 'Edit Issue' : 'Buat Issue')
@section('content')
<div class="max-w-xl mx-auto">
    <form method="POST" enctype="multipart/form-data" action="{{ $issue ? route('editor.issues.update', $issue) : route('editor.issues.store') }}" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
        @csrf
        @if($issue)@method('PUT')@endif
        @include('editor.issues._form', ['issue' => $issue, 'idSuffix' => ''])
        <div class="flex justify-end gap-2">
            <a href="{{ route('editor.issues.index') }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 text-gray-600">Batal</a>
            <button class="bg-primary text-white rounded-lg px-5 py-2 text-sm font-medium">Simpan</button>
        </div>
    </form>
</div>
@endsection

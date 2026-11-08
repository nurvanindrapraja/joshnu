@extends('layouts.workspace')
@section('title', __('Profile'))
@section('content')
<div class="max-w-3xl mx-auto space-y-5">
    <div class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        @include('profile.partials.update-password-form')
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6">
        @include('profile.partials.delete-user-form')
    </div>
</div>
@endsection

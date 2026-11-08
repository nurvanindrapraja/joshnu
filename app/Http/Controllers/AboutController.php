<?php

namespace App\Http\Controllers;

use App\Models\JournalSetting;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        $setting = JournalSetting::current();

        return view('public.about', ['setting' => $setting]);
    }
}

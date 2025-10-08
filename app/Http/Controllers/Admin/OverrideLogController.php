<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OverrideLog;

class OverrideLogController extends Controller
{
    public function index()
    {
        $logs = OverrideLog::with(['piket', 'guru'])->latest()->paginate(20);
        return view('admin.laporan.override_log', compact('logs'));
    }
}

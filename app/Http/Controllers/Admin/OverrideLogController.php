<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OverrideLog;

class OverrideLogController extends Controller
{
    public function index()
    {
        // Ambil relasi piket, guru, dan jadwal pelajaran
        $logs = OverrideLog::with(['piket', 'guru', 'jadwalPelajaran'])
                            ->latest()
                            ->paginate(20);
                            
        return view('admin.laporan.override_log', compact('logs'));
    }
}
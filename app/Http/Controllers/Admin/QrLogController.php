<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QrCodeLog;

class QrLogController extends Controller
{
    public function index()
    {
        $logs = QrCodeLog::with('user')->latest()->paginate(20);
        return view('admin.qr_log.index', compact('logs'));
    }
}
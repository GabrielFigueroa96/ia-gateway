<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\Tenant;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function loginForm()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate(['password' => 'required']);

        if ($request->password !== config('app.admin_password')) {
            return back()->withErrors(['password' => 'Contraseña incorrecta']);
        }

        session(['admin_logged_in' => true]);
        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        session()->forget('admin_logged_in');
        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $tenants = Tenant::orderBy('nombre')->get();
        $logs    = MessageLog::with('tenant')->whereNotNull('from')->latest()->limit(100)->get();
        $stats   = [
            'total'    => MessageLog::where('type', '!=', 'outgoing')->count(),
            'hoy'      => MessageLog::where('type', '!=', 'outgoing')->whereDate('created_at', today())->count(),
            'errores'  => MessageLog::whereDate('created_at', today())->where('api_ok', false)->where('type', '!=', 'outgoing')->count(),
        ];
        return view('admin.dashboard', compact('tenants', 'logs', 'stats'));
    }
}

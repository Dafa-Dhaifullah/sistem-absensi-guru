<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // Menggunakan model User bawaan Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AkunAdminController extends Controller
{
    /**
     * Menampilkan daftar akun dengan role 'admin'.
     */
    public function index()
    {
        $semuaAdmin = User::where('role', 'admin')->latest()->paginate(10);
        return view('admin.akun_admin.index', ['semuaAdmin' => $semuaAdmin]);
    }

    /**
     * Menampilkan form untuk menambah akun admin baru.
     */
    public function create()
    {
        return view('admin.akun_admin.create');
    }

    /**
     * Menyimpan akun admin baru.
     */
   // Ganti method store() Anda dengan ini
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'username' => ['required', 'string', 'max:255', 'unique:'.User::class], // Pastikan username ada
        'email' => ['nullable', 'string', 'email', 'max:255', 'unique:'.User::class], // <-- REVISI DI SINI
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    User::create([
        'name' => $request->name,
        'username' => $request->username,
        'email' => $request->email, // <-- REVISI DI SINI
        'password' => Hash::make($request->password),
        'role' => 'admin', 
    ]);

    return redirect()->route('admin.akun-admin.index')->with('success', 'Akun admin berhasil ditambahkan.');
}

// Ganti method update() Anda dengan ini
public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'username' => ['required', 'string', 'max:255', 'unique:'.User::class.',username,'.$user->id], // Validasi unik
        'email' => ['nullable', 'string', 'email', 'max:255', 'unique:'.User::class.',email,'.$user->id], // <-- REVISI DI SINI
        'password' => ['nullable', 'confirmed', Rules\Password::defaults()], 
    ]);

    $dataUpdate = [
        'name' => $request->name,
        'username' => $request->username,
        'email' => $request->email, // <-- REVISI DI SINI
    ];

    if ($request->filled('password')) {
        $dataUpdate['password'] = Hash::make($request->password);
    }

    $user->update($dataUpdate);

    return redirect()->route('admin.akun-admin.index')->with('success', 'Akun admin berhasil diperbarui.');
}

    /**
     * Menampilkan detail (opsional).
     */
    public function show($id)
    {
        //
    }

    /**
     * Menampilkan form untuk mengedit akun admin.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.akun_admin.edit', ['user' => $user]);
    }

    /**
     * Meng-update akun admin.
     */
    
    /**
     * Menghapus akun admin.
     */
    public function destroy($id)
    {
        // Tambahkan logika agar admin tidak bisa menghapus akunnya sendiri
        if (auth()->id() == $id) {
            return redirect()->route('admin.akun-admin.index')->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.akun-admin.index')->with('success', 'Akun admin berhasil dihapus.');
    }
}
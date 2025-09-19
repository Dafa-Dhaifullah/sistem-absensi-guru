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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin', // Otomatis set role sebagai 'admin'
        ]);

        return redirect()->route('admin.akun-admin.index')->with('success', 'Akun admin berhasil ditambahkan.');
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
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()], // Password boleh kosong
        ]);

        // Kumpulkan data update
        $dataUpdate = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // Hanya update password jika diisi
        if ($request->filled('password')) {
            $dataUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataUpdate);

        return redirect()->route('admin.akun-admin.index')->with('success', 'Akun admin berhasil diperbarui.');
    }

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
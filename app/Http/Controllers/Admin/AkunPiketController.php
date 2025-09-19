<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AkunPiketController extends Controller
{
    /**
     * Menampilkan daftar akun dengan role 'piket'.
     */
    public function index()
    {
        $semuaPiket = User::where('role', 'piket')->latest()->paginate(10);
        return view('admin.akun_piket.index', ['semuaPiket' => $semuaPiket]);
    }

    /**
     * Menampilkan form untuk menambah akun piket baru.
     */
    public function create()
    {
        return view('admin.akun_piket.create');
    }

    /**
     * Menyimpan akun piket baru.
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
            'role' => 'piket', // Otomatis set role sebagai 'piket'
        ]);

        return redirect()->route('admin.akun-piket.index')->with('success', 'Akun piket berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail (opsional).
     */
    public function show($id)
    {
        //
    }

    /**
     * Menampilkan form untuk mengedit akun piket.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.akun_piket.edit', ['user' => $user]);
    }

    /**
     * Meng-update akun piket.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()], // Password boleh kosong
        ]);

        $dataUpdate = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $dataUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataUpdate);

        return redirect()->route('admin.akun-piket.index')->with('success', 'Akun piket berhasil diperbarui.');
    }

    /**
     * Menghapus akun piket.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.akun-piket.index')->with('success', 'Akun piket berhasil dihapus.');
    }

    /**
     * (FITUR TAMBAHAN) Mereset password akun piket ke default.
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Tentukan password default Anda
        $defaultPassword = 'piket123'; 
        
        $user->password = Hash::make($defaultPassword);
        $user->save();

        return redirect()->back()->with('success', 'Password untuk ' . $user->name . ' berhasil di-reset ke "' . $defaultPassword . '".');
    }
}
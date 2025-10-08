<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PenggunaController extends Controller
{
    // Menampilkan daftar pengguna, bisa difilter berdasarkan role
    public function index(Request $request)
    {
        $query = User::query();

        // Jika ada filter role di URL (?role=guru)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $semuaPengguna = $query->orderBy('name', 'asc')->paginate(15);

        return view('admin.pengguna.index', compact('semuaPengguna'));
    }

   /**
     * Menampilkan form tambah pengguna
     */
    public function create(Request $request)
    {
        // Ambil 'role' dari parameter URL, default ke 'guru' jika tidak ada
        $role = $request->query('role', 'guru'); 
        
        // Kirim variabel $role ke view
        return view('admin.pengguna.create', compact('role'));
    }

    /**
     * Menyimpan pengguna baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'nip' => 'nullable|numeric|unique:users,nip', // <-- Diubah ke 'numeric'
            'no_wa' => 'nullable|numeric', // <-- Diubah ke 'numeric'
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,kepala_sekolah,piket,guru',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'nip' => $request->nip,
            'no_wa' => $request->no_wa,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        // Redirect kembali ke halaman index DENGAN FILTER ROLE YANG SAMA
        return redirect()->route('admin.pengguna.index', ['role' => $request->role])
                         ->with('success', 'Pengguna baru berhasil ditambahkan.');
    }


    // Menampilkan form edit pengguna
    public function edit(User $pengguna) // Menggunakan Route Model Binding
    {
        return view('admin.pengguna.edit', compact('pengguna'));
    }

    // Mengupdate pengguna
    public function update(Request $request, User $pengguna)
    {
         $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $pengguna->id,
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $pengguna->id,
            'nip' => 'nullable|numeric|unique:users,nip,' . $pengguna->id, // <-- Diubah ke 'numeric'
            'no_wa' => 'nullable|numeric', // <-- Diubah ke 'numeric'
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,kepala_sekolah,piket,guru',
        ]);

        $dataUpdate = $request->only(['name', 'username', 'email', 'nip', 'no_wa', 'role']);
        if ($request->filled('password')) {
            $dataUpdate['password'] = Hash::make($request->password);
        }

        $pengguna->update($dataUpdate);

        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    // Method untuk menampilkan halaman form import
public function showImportForm()
{
    return view('admin.pengguna.import');
}

// Method untuk memproses file Excel
public function importExcel(Request $request)
{
    $request->validate(['file' => 'required|mimes:xlsx,xls']);

    try {
        Excel::import(new PenggunaImport, $request->file('file'));
        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna berhasil diimpor!');
    } catch (ValidationException $e) {
        $failures = $e->failures();
        $errorMessages = [];
        foreach ($failures as $failure) {
            // Pesan error akan lebih detail: Error di baris X: Pesan Error
            $errorMessages[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
        }
        return redirect()->route('admin.pengguna.import.form')->with('import_errors', $errorMessages);
    }
}

    // Menghapus pengguna
    public function destroy(User $pengguna)
    {
        if (auth()->id() == $pengguna->id) {
            return redirect()->back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }
        $pengguna->delete();
        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    // Mereset password
    public function resetPassword(User $user)
    {
        $defaultPassword = 'smkn6garut'; // Tentukan password default
        $user->password = Hash::make($defaultPassword);
        $user->save();
        return redirect()->back()->with('success', 'Password untuk ' . $user->name . ' berhasil di-reset.');
    }
}
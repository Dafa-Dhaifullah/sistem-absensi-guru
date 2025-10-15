<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Imports\PenggunaImport; 
use Maatwebsite\Excel\Facades\Excel; 
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Database\QueryException;

class PenggunaController extends Controller
{
    // Menampilkan daftar pengguna, bisa difilter berdasarkan role
     public function index(Request $request)
    {
        $query = User::query();

        // Filter berdasarkan role (jika ada)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

       
        //Logika Pencarian //
        if ($request->filled('search')) {
            $search = $request->search;
            // Mencari di beberapa kolom sekaligus
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }
        // ==========================================================

        $semuaPengguna = $query->orderBy('name', 'asc')->paginate(15);

        // Kirim semua parameter filter ke view untuk pagination
        $semuaPengguna->appends($request->all());

        return view('admin.pengguna.index', compact('semuaPengguna'));
    }

     public function show(User $pengguna)
    {
        // Hitung statistik kehadiran pengguna untuk bulan ini
        $laporanBulanIni = $pengguna->laporanHarian()
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->get();
        
        $summary = [
            'hadir' => $laporanBulanIni->where('status', 'Hadir')->count(),
            'terlambat' => $laporanBulanIni->where('status_keterlambatan', 'Terlambat')->count(),
            'sakit' => $laporanBulanIni->where('status', 'Sakit')->count(),
            'izin' => $laporanBulanIni->where('status', 'Izin')->count(),
            'alpa' => $laporanBulanIni->where('status', 'Alpa')->count(),
            'dl' => $laporanBulanIni->where('status', 'DL')->count(),
        ];

        return view('admin.pengguna.show', compact('pengguna', 'summary'));
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
            'username' => 'required|string|max:255|unique:users|regex:/^[a-zA-Z0-9\s]+$/',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'nip' => 'nullable|numeric|unique:users,nip',
            'no_wa' => 'nullable|numeric',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,kepala_sekolah,guru',
        ], [
            
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan spasi.'
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

        return redirect()->route('admin.pengguna.index', ['role' => $request->role])
                         ->with('success', 'Pengguna baru berhasil ditambahkan.');
    }



    // Menampilkan form edit pengguna
    public function edit(User $pengguna) 
    {
        return view('admin.pengguna.edit', compact('pengguna'));
    }

    // Mengupdate pengguna
    public function update(Request $request, User $pengguna)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $pengguna->id . '|regex:/^[a-zA-Z0-9\s]+$/',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $pengguna->id,
            'nip' => 'nullable|numeric|unique:users,nip,' . $pengguna->id,
            'no_wa' => 'nullable|numeric',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,kepala_sekolah,guru',
        ], [
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan spasi.'
        ]);


       // 1. Ambil semua data yang valid KECUALI password
        $dataUpdate = $request->except(['password', 'password_confirmation']);
        
        // 2. Cek apakah pengguna mencoba mengubah role-nya sendiri
        if (auth()->id() == $pengguna->id) {
            unset($dataUpdate['role']); // Hapus 'role' dari data update jika mengedit diri sendiri
        }
        
        // 3. HANYA update password jika field password diisi
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
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new \App\Imports\PenggunaImport, $request->file('file'));
            return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna berhasil diimpor!');
        
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                // Ambil pesan error utama (misal: "Username :value sudah terdaftar")
                $errorMessage = implode(', ', $failure->errors());
                
                // Ambil nama kolom yang error (misal: 'username')
                $attribute = $failure->attribute();
                
                // Ambil nilai yang menyebabkan error dari data baris tersebut
                $failedValue = $failure->values()[$attribute] ?? ''; 
                
                // Ganti placeholder ':value' dengan nilai yang sebenarnya
                $finalMessage = str_replace(':value', $failedValue, $errorMessage);
                
                $errorMessages[] = "Baris " . $failure->row() . ": " . $finalMessage;
            }
            // ==========================================================
            
            return redirect()->route('admin.pengguna.import.form')->with('import_errors', $errorMessages);
        
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return redirect()->route('admin.pengguna.import.form')->with('import_errors', [
                    'Terjadi kesalahan: Ditemukan data duplikat (Username, NIP, atau Email) di dalam file Excel atau data yang sudah ada di sistem. Silakan periksa kembali file Anda.'
                ]);
            }
            return redirect()->route('admin.pengguna.import.form')->with('import_errors', ['Terjadi kesalahan database yang tidak terduga.']);
        }
    }

    // Menghapus pengguna
    public function destroy(User $pengguna)
    {
        if (auth()->id() == $pengguna->id) {
            return redirect()->back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }
        $pengguna->delete(); 
        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil diarsipkan.');
    }

    // Mereset password
    public function resetPassword(User $user)
    {
        $defaultPassword = 'smkn6garut'; // Tentukan password default
        $user->password = Hash::make($defaultPassword);
        $user->save();
        return redirect()->back()->with('success', 'Password untuk ' . $user->name . ' berhasil di-reset.');
    }

    public function arsip()
    {
        $penggunaArsip = User::onlyTrashed()->paginate(15);
        return view('admin.pengguna.arsip', compact('penggunaArsip'));
    }

    // Memulihkan pengguna dari arsip
    public function restore($id)
    {
        User::onlyTrashed()->findOrFail($id)->restore();
        return redirect()->route('admin.pengguna.arsip')->with('success', 'Pengguna berhasil dipulihkan.');
    }

    // Menghapus pengguna secara permanen dari arsip
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->forceDelete();
        return redirect()->route('admin.pengguna.arsip')->with('success', 'Pengguna berhasil dihapus permanen.');
    }
}
<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Jadwal Piket Mingguan') }}
        </h2>
    </x-slot>

    <!-- ============================================== -->
    <!-- == LOGIKA ALPINE.JS UNTUK MODAL & PENCARIAN == -->
    <!-- ============================================== -->
    <div 
        x-data="{
            modalOpen: false,
            modalHari: '',
            modalSesi: '',
            formAction: '',
            search: '',
            allGurus: {{ $daftarGuruPiket->map(fn($guru) => ['id' => $guru->id, 'name' => $guru->name]) }},
            selectedIds: [],
            
            get filteredGurus() {
                if (this.search === '') {
                    return this.allGurus;
                }
                return this.allGurus.filter(guru => {
                    return guru.name.toLowerCase().includes(this.search.toLowerCase());
                });
            },

            openModal(hari, sesi, selectedUserIds) {
                this.modalHari = hari;
                this.modalSesi = sesi;
                this.formAction = `{{ url('admin/jadwal-piket/update') }}/${hari}/${sesi}`;
                this.selectedIds = JSON.parse(selectedUserIds);
                this.modalOpen = true;
            }
        }"
        class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <p class="mb-4 text-gray-600">
                        Daftar tim guru piket yang bertugas. Klik "Edit Tim" pada setiap slot untuk memilih guru.
                    </p>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Piket Pagi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Piket Siang</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($hari as $h)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-1/5 align-top">{{ $h }}</td>
                                    @foreach ($sesi as $s)
                                    <td class="px-6 py-4 text-sm text-gray-900 w-2/5 align-top">
                                        @php
                                            $daftarPiket = $jadwalTersimpan->get($h, collect())->get($s, collect());
                                            $selectedUserIds = json_encode($daftarPiket->pluck('user_id'));
                                        @endphp
                                        
                                        <div class="flex justify-between items-start">
                                            <div class="flex flex-wrap gap-2">
                                                @forelse ($daftarPiket as $piket)
                                                    <span class="px-3 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full">
                                                        {{ $piket->user->name ?? 'Error' }}
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-gray-400 italic">-- Belum ada guru --</span>
                                                @endforelse
                                            </div>
                                            <button @click="openModal('{{ $h }}', '{{ $s }}', '{{ $selectedUserIds }}')"
                                                    class="flex-shrink-0 text-xs text-indigo-600 hover:text-indigo-900 font-medium ml-4">
                                                [Edit Tim]
                                            </button>
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- == MODAL PEMILIHAN GURU == -->
        <!-- ============================================== -->
        <div x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            
            <div @click.away="modalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
                <form :action="formAction" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="p-6 border-b">
                        <h3 class="text-xl font-semibold">Pilih Guru Piket</h3>
                        <p class="text-sm text-gray-500">Untuk: <span x-text="modalHari" class="font-medium"></span> - Sesi <span x-text="modalSesi" class="font-medium"></span></p>
                    </div>

                    <div class="p-6 flex-grow overflow-y-auto">
                        <!-- Kolom Pencarian -->
                        <div class="mb-4">
                            <x-input-label for="search" :value="__('Cari Nama Guru')" />
                            <x-text-input id="search" x-model="search" type="text" class="w-full mt-1" placeholder="Ketik untuk mencari..." />
                        </div>
                        
                        <!-- Daftar Checkbox -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <template x-for="guru in filteredGurus" :key="guru.id">
                                <label class="flex items-center p-3 rounded-lg hover:bg-gray-50 border cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-300">
                                    <input type="checkbox" name="user_ids[]" :value="guru.id" x-model="selectedIds"
                                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ms-3 text-sm font-medium text-gray-700" x-text="guru.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 border-t flex justify-end gap-4">
                        <x-secondary-button @click.prevent="modalOpen = false">Batal</x-secondary-button>
                        <x-primary-button type="submit">Simpan Perubahan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
        <!-- ============================================== -->

    </div>
</x-admin-layout>


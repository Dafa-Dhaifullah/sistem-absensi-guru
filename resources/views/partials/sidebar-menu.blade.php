{{-- resources/views/partials/sidebar-menu.blade.php --}}
@php
  $user = Auth::user();
  $role = $user->role ?? null;

  // Route dashboard berdasar role
  $dashboardRoute = 'dashboard';
  if ($role === 'admin') $dashboardRoute = 'admin.dashboard';
  elseif ($role === 'pimpinan') $dashboardRoute = 'pimpinan.dashboard';

  // Helper kelas aktif
  $active = fn($pattern, $extra = true) =>
      (request()->routeIs($pattern) && $extra) ? 'sidebar-active' : 'hover:bg-gray-700';
@endphp

<ul class="space-y-1 text-sm">
  {{-- Umum --}}
  <li>
    <a href="{{ route($dashboardRoute) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active($dashboardRoute) }}">
      <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
      </svg>
      <span class="sidebar__label">Dashboard</span>
    </a>
  </li>

  @if($role === 'admin')
    <hr class="border-gray-700 my-2 sidebar__hr">
    <li class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase sidebar__section">Data Pengguna</li>

    <li>
      <a href="{{ route('admin.pengguna.index') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.pengguna.*', request('role') == 'guru' || request()->routeIs('admin.pengguna.*')) }}"
         title="Data Pengguna">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.075c0 1.313-.964 2.505-2.287 2.697H5.287c-1.323-.192-2.287-1.384-2.287-2.697v-4.075M12 12.25c-2.485 0-4.5-2.015-4.5-4.5s2.015-4.5 4.5-4.5 4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5z" />
        </svg>
        <span class="sidebar__label">Data Pengguna</span>
      </a>
    </li>

    <hr class="border-gray-700 my-2 sidebar__hr">
    <li class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase sidebar__section">Manajemen</li>

    <li>
      <a href="{{ route('admin.jadwal-pelajaran.index') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.jadwal-pelajaran.*') }}"
         title="Manajemen Jadwal Pelajaran">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18M9 12.75h6" />
        </svg>
        <span class="sidebar__label">Manajemen Jadwal Pelajaran</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.jadwal-piket.index') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.jadwal-piket.*') }}"
         title="Manajemen Jadwal Piket">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
        </svg>
        <span class="sidebar__label">Manajemen Jadwal Piket</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.kalender-blok.index') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.kalender-blok.*') }}"
         title="Manajemen Kalender Blok">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h7.5M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
        </svg>
        <span class="sidebar__label">Manajemen Kalender Blok</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.hari-libur.index') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.hari-libur.*') }}"
         title="Manajemen Hari Libur">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.324h5.383c.493 0 .706.656.34.978l-4.36 3.192a.563.563 0 00-.182.635l1.658 5.131a.563.563 0 01-.84.609l-4.38-3.192a.563.563 0 00-.664 0l-4.38 3.192a.563.563 0 01-.84-.609l1.658-5.131a.563.563 0 00-.182-.635l-4.36-3.192a.563.563 0 01.34-.978h5.383a.563.563 0 00.475-.324L11.48 3.5z" />
        </svg>
        <span class="sidebar__label">Manajemen Hari Libur</span>
      </a>
    </li>

    <li>
    <a href="{{ route('admin.master-jam.index') }}" 
       class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.master-jam.*') }}"
       title="Pelajaran">
        <svg class="w-5 h-5 text-gray-400 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="sidebar__label">Manajemen Jam Pelajaran</span>
    </a>
</li>

    <li>
      <a href="{{ route('admin.qrcode.generator.index') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.qrcode.*') }}"
         title="Generator QrCode">
        <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 14.25h.008v.008h-.008v-.008zM19.5 16.5h.008v.008h-.008v-.008zM19.5 18.75h.008v.008h-.008v-.008zM17.25 16.5h.008v.008h-.008v-.008zM15 18.75h.008v.008H15v-.008zM17.25 18.75h.008v.008h-.008v-.008zM19.5 14.25h.008v.008h-.008v-.008zM15 16.5h.008v.008H15v-.008zM15 14.25h.008v.008H15v-.008z" />
        </svg>
        <span class="sidebar__label">Generator QrCode</span>
      </a>
    </li>
  @endif

  @if(in_array($role, ['admin', 'pimpinan']))
    <hr class="border-gray-700 my-2 sidebar__hr">
    <li class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase sidebar__section">Laporan</li>

    <li>
      <a href="{{ route('admin.laporan.terlambat.harian') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.laporan.terlambat.harian') }}">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="sidebar__label">Laporan Terlambat</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.laporan.override_log') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.laporan.override_log') }}">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
        </svg>
        <span class="sidebar__label">Log Aktivitas Piket</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.laporan.realtime') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.laporan.realtime') }}">
        <svg class="w-5 h-5 {{ request()->routeIs('admin.laporan.realtime') ? 'text-white' : 'text-gray-300 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.871A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h9.75a2.25 2.25 0 012.25 2.25z" />
        </svg>
        <span class="sidebar__label">Jadwal Real-time</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.laporan.bulanan') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.laporan.bulanan') }}"
         title="Rekap Bulanan">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1.125-1.5M12 16.5v4.5m-3-4.5v4.5m7.5-4.5 v4.5m-7.5 0h7.5" />
        </svg>
        <span class="sidebar__label">Rekap Bulanan</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.laporan.mingguan') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.laporan.mingguan') }}"
         title="Rekap Mingguan">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 100 15 7.5 7.5 0 000-15zM21 21l-5.197-5.197" />
        </svg>
        <span class="sidebar__label">Rekap Mingguan</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.laporan.individu') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.laporan.individu') }}"
         title="Laporan Individu">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0z" />
        </svg>
        <span class="sidebar__label">Laporan Individu</span>
      </a>
    </li>

    <li>
      <a href="{{ route('admin.laporan.arsip') }}"
         class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ $active('admin.laporan.arsip') }}"
         title="Arsip Logbook">
        <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
        <span class="sidebar__label">Arsip Logbook</span>
      </a>
    </li>
  @endif
</ul>

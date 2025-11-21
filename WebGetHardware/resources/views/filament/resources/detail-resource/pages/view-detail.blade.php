@php
    $qs = http_build_query([
        'from' => request('from'),
        'to' => request('to'),
    ]);
@endphp

<x-filament::page>
    {{-- Tombol Export --}}
    <div class="flex justify-end gap-3 mb-4">
        <x-filament::button color="success" tag="a"
            href="{{ route('detail.export.pdf', $record->tag_id) }}?{{ $qs }}">
            📄 Export PDF
        </x-filament::button>

        <x-filament::button color="info" tag="a"
            href="{{ route('detail.export.excel', $record->tag_id) }}?{{ $qs }}">
            📊 Export Excel
        </x-filament::button>
    </div>

    {{-- Card Info Kendaraan & Supir --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3">
            <div>
                <p class="text-sm font-bold dark:text-white" style="color:black;">
                    Nomor Kendaraan
                </p>
                <p class="text-lg font-semibold dark:text-white" style="color:black;">
                    {{ $record->vehicle_number }}
                </p>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3">
            <div>
                <p class="text-sm font-bold dark:text-white" style="color:black;">
                    Nama Supir
                </p>
                <p class="text-lg font-semibold dark:text-white" style="color:black;">
                    {{ $record->owner_name }}
                </p>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-3">
            <div>
                <p class="text-sm font-bold dark:text-white" style="color:black;">
                    Total Berat Bersih
                </p>
                <p class="text-lg font-semibold text-black dark:text-white">
                    {{ number_format($totalNetto ?? 0, 2, ',', '.') }} Kg
                </p>
            </div>
        </div>
    </div>

    {{-- Tabel Detail Angkutan --}}
    <div class="overflow-hidden rounded-xl shadow">
        <table class="table-auto w-full text-left border-2 border-black">
            <thead class="bg-gray-800 dark:bg-gray-700">
                {{-- Baris Filter di Header --}}
                <tr>
                    <th colspan="3" class="px-4 py-3">
                        <form method="GET" class="flex flex-col md:flex-row items-end gap-4 justify-end">
                            <div>
                                <label class="block text-sm mb-1 font-semibold text-black dark:text-white">Dari
                                    Tanggal</label>
                                <input type="date" name="from" value="{{ request('from') }}"
                                    class="rounded-lg border-gray-300 dark:bg-gray-900 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-sm mb-1 font-semibold text-black dark:text-white">Sampai
                                    Tanggal</label>
                                <input type="date" name="to" value="{{ request('to') }}"
                                    class="rounded-lg border-gray-300 dark:bg-gray-900 dark:text-white" />
                            </div>
                            <div class="pt-5">
                                <x-filament::button type="submit" color="primary">Filter</x-filament::button>
                                <a href="{{ url()->current() }}" class="ml-2 text-sm text-red-400">Reset</a>
                            </div>
                        </form>
                    </th>
                </tr>

                {{-- Judul Kolom --}}
                <tr>
                    <th class="px-4 py-3 dark:text-gray-100">Tanggal Pengangkutan</th>
                    <th class="px-4 py-3 dark:text-gray-100">Waktu Saat Timbang di Jetty</th>
                    <th class="px-4 py-3 dark:text-gray-100">Berat Bersih (Kg)</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse ($details as $row)
                    <tr>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $row->tanggal }}</td>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $row->jam }}</td>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                            {{ number_format($row->netto, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                            Belum ada data angkutan truk untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::page>

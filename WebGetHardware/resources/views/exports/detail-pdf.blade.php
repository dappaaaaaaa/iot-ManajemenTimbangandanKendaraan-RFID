{{-- resources/views/exports/detail-pdf.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Detail Export PDF</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 6px;
            text-align: left;
        }

        h2 {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <h2>Detail Data Angkutan - {{ $header->tag_id }}</h2>
    <p>Nama Supir: {{ $header->owner_name }}</p>
    <p>Nomor Kendaraan: {{ $header->vehicle_number }}</p>
    @if ($periode)
        <p>Periode: {{ $periode }}</p>
    @endif

    <table class="table-auto w-full border-collapse border border-gray-500">
        <thead class="bg-gray-200">
            <tr>
                <th class="border px-2 py-1">Tanggal Pengangkutan</th>
                <th class="border px-2 py-1">Jam Timbang Kotor di Jetty</th>
                <th class="border px-2 py-1">Berat Bersih (Kg)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $row)
                <tr>
                    <td class="border px-2 py-1">{{ $row->tanggal }}</td>
                    <td class="border px-2 py-1">{{ $row->jam }}</td>
                    <td class="border px-2 py-1">{{ number_format($row->netto, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>

@php
    $totalNetAtJetty = 0;
    $totalMeasurements = count($measurements);

    foreach ($measurements as $item) {
        $totalNetAtJetty += $item->gross_at_jetty - $item->tare_at_jetty;
    }
@endphp

<table>
    <thead>
        <tr>
            <th>Total Berat Bersih</th>
            <th>{{ number_format($totalNetAtJetty, 2, ',', '.') }} Kg</th>
            <th>Jumlah Angkutan</th>
            <th>{{ number_format($totalMeasurements) }}</th>
        </tr>
        <tr>
            <th>Nomor Kendaraan</th>
            <th>Nama Supir</th>
            <th>Kartu RFID</th>
            <th>Berat Kotor Tambang</th>
            <th>Berat Kotor Jetty</th>
            <th>Berat Tara</th>
            <th>Berat Bersih</th>
            <th>Losis</th>
            <th>Waktu Masuk Jetty</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($measurements as $item)
            <tr>
                <td>{{ $item->vehicle_number }}</td>
                <td>{{ $item->owner_name }}</td>
                <td>{{ $item->tag_id }}</td>
                <td>{{ number_format($item->gross_at_mine, 2, ',', '.') }}</td>
                <td>{{ number_format($item->gross_at_jetty, 2, ',', '.') }}</td>
                <td>{{ number_format($item->tare_at_jetty, 2, ',', '.') }}</td>
                <td>{{ number_format($item->gross_at_jetty - $item->tare_at_jett, 2, ',', '.') }}</td>
                <td>{{ number_format($item->gross_at_mine - $item->gross_at_jetty, 2, ',', '.') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->jetty_entry_time)->format('d-m-Y H:i:s') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

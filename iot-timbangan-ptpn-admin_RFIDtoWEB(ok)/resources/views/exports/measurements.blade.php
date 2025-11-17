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
            <th>Total Berat Bersih di Jetty:</th>
            <th>{{ number_format($totalNetAtJetty, 2, ',', '.') }} Kg</th>
            <th>Jumlah Angkutan:</th>
            <th>{{ number_format($totalMeasurements) }}</th>
        </tr>
        <tr>
            <th>Kode Nomor Dermaga</th>
            <th>Kode Nomor Tambang</th>
            <th>Pengirim</th>
            <th>Penerima</th>
            <th>Jenis Batubara</th>
            <th>Vendor</th>
            <th>Nomor Kendaraan</th>
            <th>Gross Mine</th>
            <th>Tare Mine</th>
            <th>Gross Jetty</th>
            <th>Tare Jetty</th>
            <th>Jetty Entry Time</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($measurements as $item)
            <tr>
                <td>{{ $item->kode_no_dermaga }}</td>
                <td>{{ $item->kode_no_tambang }}</td>
                <td>{{ $item->pengirim }}</td>
                <td>{{ $item->penerima }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td>{{ $item->vendor }}</td>
                <td>{{ $item->vehicle_number }}</td>
                <td>{{ number_format($item->gross_at_mine, 2, ',', '.') }}</td>
                <td>{{ number_format($item->tare_at_mine, 2, ',', '.') }}</td>
                <td>{{ number_format($item->gross_at_jetty, 2, ',', '.') }}</td>
                <td>{{ number_format($item->tare_at_jetty, 2, ',', '.') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->jetty_entry_time)->format('d-m-Y H:i:s') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

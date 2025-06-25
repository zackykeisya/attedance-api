<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Laporan Absensi</h1>
    <table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Karyawan</th>
            <th>Tanggal</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $key => $attendance)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ optional($attendance->user)->name ?? 'N/A' }}</td>
            <td>{{ $attendance->date ? $attendance->date->format('d/m/Y') : '-' }}</td>
            <td>{{ $attendance->check_in ? $attendance->check_in->format('H:i:s') : '-' }}</td>
            <td>{{ $attendance->check_out ? $attendance->check_out->format('H:i:s') : '-' }}</td>
            <td>{{ $attendance->status ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>

<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;

class AttendanceExport implements FromCollection {
    public function collection() {
        return Attendance::with('user')->get()->map(function ($item) {
            return [
                'Nama' => $item->user->name,
                'Email' => $item->user->email,
                'Tanggal' => $item->date,
                'Jam Masuk' => $item->clock_in,
                'Jam Keluar' => $item->clock_out,
            ];
        });
    }
}

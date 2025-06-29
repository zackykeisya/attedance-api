<?php

namespace App\Repositories;

use App\Models\Attendance;

class AttendanceRepository {
    // Mengambil record absensi hari ini untuk user tertentu
    public function todayRecord($userId, $date) {
        return Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();
    }

    // Membuat data absensi baru
    public function create($data) {
        return Attendance::create($data);
    }

    // Mengupdate data absensi berdasarkan id
    public function update($id, $data) {
        $attendance = Attendance::findOrFail($id);
        $attendance->update($data);
        return $attendance;
    }

    // Mengambil riwayat absensi user tertentu, bisa filter rentang tanggal
    public function historyByUser($userId, $from = null, $to = null) {
        $query = Attendance::where('user_id', $userId);

        if ($from && $to) {
            $query->whereBetween('date', [$from, $to]);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    // Mengambil seluruh riwayat absensi (bisa filter user dan tanggal), untuk admin
    public function getAllHistory($userId = null, $from = null, $to = null) {
        $query = Attendance::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($from && $to) {
            $query->whereBetween('date', [$from, $to]);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    // Mengambil semua data absensi (untuk export, tanpa filter)
    public function getAll() {
        return Attendance::with('user')->orderBy('date', 'desc')->get();
    }

    // Mengambil absensi berdasarkan tanggal tertentu
    public function getByDate($date) {
        return Attendance::with('user')
            ->where('date', $date)
            ->orderBy('clock_in')
            ->get();
    }

    // Mengambil absensi berdasarkan user dan tanggal tertentu
    public function getByUserAndDate($userId, $date) {
        return Attendance::with('user')
            ->where('user_id', $userId)
            ->where('date', $date)
            ->get();
    }

    // Statistik absensi per bulan (jumlah absensi tiap bulan)
    public function statistikBulanan() {
        return Attendance::selectRaw('DATE_FORMAT(date, "%Y-%m") as bulan, COUNT(*) as total')
            ->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->get();
    }

    // Mengambil data absensi berdasarkan id (find by id)
    public function find($id) {
        return Attendance::findOrFail($id);
    }
}



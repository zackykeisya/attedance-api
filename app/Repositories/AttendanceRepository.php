<?php

namespace App\Repositories;

use App\Models\Attendance;

class AttendanceRepository {
    //today 
    public function todayRecord($userId, $date) {
        return Attendance::where('user_id', $userId)
            ->where('date', $date)
            ->first();
    }

    public function create($data) {
        return Attendance::create($data);
    }

    public function update($id, $data) {
        $attendance = Attendance::findOrFail($id);
        $attendance->update($data);
        return $attendance;
    }

    public function historyByUser($userId, $from = null, $to = null) {
        $query = Attendance::where('user_id', $userId);

        if ($from && $to) {
            $query->whereBetween('date', [$from, $to]);
        }

        return $query->orderBy('date', 'desc')->get();
    }

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

    // Ambil semua data (tanpa filter, untuk export)
    public function getAll() {
    return Attendance::with('user')->orderBy('date', 'desc')->get(); // ✅ pastikan pakai with('user')
    }

    // Filter berdasarkan tanggal saja
    public function getByDate($date) {
        return Attendance::with('user')
            ->where('date', $date)
            ->orderBy('clock_in')
            ->get();
    }

    // Filter berdasarkan user_id dan tanggal
    public function getByUserAndDate($userId, $date) {
        return Attendance::with('user')
            ->where('user_id', $userId)
            ->where('date', $date)
            ->get();
    }

    // Statistik absensi per bulan
    public function statistikBulanan() {
        return Attendance::selectRaw('DATE_FORMAT(date, "%Y-%m") as bulan, COUNT(*) as total')
            ->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->get();
    }
    public function find($id)
{
    return Attendance::findOrFail($id);
}


}



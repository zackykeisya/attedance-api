<?php

namespace App\Repositories;

use App\Models\Attendance;

class AttendanceRepository {
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

}

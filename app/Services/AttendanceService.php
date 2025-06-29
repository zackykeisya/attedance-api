<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService {
    protected $repo;

    public function __construct(AttendanceRepository $repo) {
        $this->repo = $repo;
    }

    public function today($userId) {
        $today = Carbon::now()->toDateString();
        return $this->repo->todayRecord($userId, $today);
    }

    public function clockIn($userId) {
        $today = Carbon::now()->toDateString();
        $record = $this->repo->todayRecord($userId, $today);

        if ($record) {
            return ['error' => 'Sudah melakukan clock-in hari ini'];
        }

        return $this->repo->create([
            'user_id' => $userId,
            'clock_in' => Carbon::now(),
            'date' => $today,
        ]);
    }

    public function clockOut($userId) {
        $today = Carbon::now()->toDateString();
        $record = $this->repo->todayRecord($userId, $today);

        if (!$record) {
            return ['error' => 'Belum melakukan clock-in'];
        }

        if ($record->clock_out) {
            return ['error' => 'Sudah clock-out'];
        }

        return $this->repo->update($record->id, [
            'clock_out' => Carbon::now()
        ]);
    }

    public function getUserHistory($userId, $from = null, $to = null) {
        return $this->repo->historyByUser($userId, $from, $to);
    }

    public function getAll($userId = null, $from = null, $to = null) {
        return $this->repo->getAllHistory($userId, $from, $to);
    }

    public function getAllHistory() {
        return $this->repo->getAll();
    }

    public function filterByUser($userId) {
        return $this->repo->getAllHistory($userId);
    }

    public function filterByDate($date) {
        return $this->repo->getByDate($date);
    }

    public function filterCombined($userId, $date) {
        return $this->repo->getByUserAndDate($userId, $date);
    }

    public function getStatistik() {
        return $this->repo->statistikBulanan();
    }
  public function resetClock($id)
{
    $attendance = $this->repo->find($id);
    $attendance->clock_in = null;
    $attendance->clock_out = null;
    $attendance->save();
    return $attendance;
}



}


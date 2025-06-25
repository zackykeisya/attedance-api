<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Permission;
use App\Services\AttendanceService;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller 
{
    protected $service;

    public function __construct(AttendanceService $service) {
        $this->service = $service;
    }

    // app/Http/Controllers/AttendanceController.php

public function today(Request $request)
{
    try {
        $date = $request->query('date', Carbon::today()->toDateString());
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $permission = Permission::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->where('status', 'approved')
            ->first();

        if ($permission) {
            return response()->json([
                'is_permission' => true,
                'permission_type' => $permission->type,
                'description' => $permission->description,
                'date' => $date
            ]);
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if (!$attendance) {
            return response()->json([
                'clock_in' => null,
                'clock_out' => null,
                'is_permission' => false,
                'permission_type' => null,
                'date' => $date
            ]);
        }

        return response()->json([
            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'is_permission' => isset($attendance->is_permission) ? $attendance->is_permission : false,
            'permission_type' => isset($attendance->permission_type) ? $attendance->permission_type : null,
            'date' => $date
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Internal Server Error',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function clockIn(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $now = Carbon::now('Asia/Jakarta')->toTimeString();

        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            if ($existing->clock_in && !$existing->clock_out) {
                return response()->json(['message' => 'Sudah clock in, belum clock out.'], 400);
            }

            if (!$existing->clock_in && !$existing->clock_out) {
                $existing->clock_in = $now;
                $existing->save();
                return response()->json(['message' => 'Clock in ulang berhasil setelah reset.', 'data' => $existing]);
            }

            if ($existing->clock_in && $existing->clock_out) {
                return response()->json(['message' => 'Sudah clock in dan clock out hari ini.'], 400);
            }
        }

        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->date = $today;
        $attendance->clock_in = $now;
        $attendance->save();

        return response()->json(['message' => 'Clock in berhasil.', 'data' => $attendance]);
    }

    public function clockOut(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $now = Carbon::now('Asia/Jakarta')->toTimeString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Belum melakukan clock in.'], 400);
        }

        if ($attendance->clock_out) {
            return response()->json(['message' => 'Sudah melakukan clock out hari ini.'], 400);
        }

        if (!$attendance->clock_in) {
            return response()->json(['message' => 'Data absensi tidak valid.'], 400);
        }

        $attendance->clock_out = $now;
        $attendance->save();

        return response()->json(['message' => 'Clock out berhasil.', 'data' => $attendance]);
    }

    public function history($id, Request $request)
    {
        $user = auth()->user();
        
        // Authorization check
        if ($user->role === 'karyawan' && $user->id != $id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        // Get attendance data
        $attendances = Attendance::where('user_id', $id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        // Get permission data
        $permissions = Permission::where('user_id', $id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        // Calculate statistics
        $lateCount = 0;
        $onTimeCount = 0;
        $permissionCount = 0;

        foreach ($attendances as $attendance) {
            if (isset($attendance->is_permission) && $attendance->is_permission) {
                $permissionCount++;
            } elseif ($attendance->clock_in) {
                $clockIn = Carbon::createFromFormat('H:i:s', $attendance->clock_in);
                if ($clockIn->gt(Carbon::createFromTime(8, 0, 0))) {
                    $lateCount++;
                } else {
                    $onTimeCount++;
                }
            }
        }

        return response()->json([
            'user' => User::find($id),
            'month' => $month,
            'year' => $year,
            'total_absen' => $attendances->count(),
            'tepat_waktu' => $onTimeCount,
            'terlambat' => $lateCount,
            'izin' => $permissionCount,
            'riwayat_absen' => $attendances,
            'riwayat_izin' => $permissions
        ]);
    }

    public function allHistory() {
        $data = $this->service->getAllHistory();
        return AttendanceResource::collection($data);
    }

    public function filterByUser(Request $request) {
        $userId = $request->query('user_id');
        $data = $this->service->filterByUser($userId);
        return AttendanceResource::collection($data);
    }

    public function filterByDate(Request $request) {
        $date = $request->query('date');
        $data = $this->service->filterByDate($date);
        return AttendanceResource::collection($data);
    }

    public function filterCombined(Request $request) {
        $userId = $request->query('user_id');
        $from = $request->query('from');
        $to = $request->query('to');
        $data = $this->service->filterCombined($userId, $from, $to);
        return AttendanceResource::collection($data);
    }

    public function statistik()
    {
        try {
            $data = Attendance::select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as bulan"),
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN clock_in > '08:00:00' THEN 1 ELSE 0 END) as telat")
            )
            ->groupBy('bulan')
            ->orderBy('bulan', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'bulan' => date('F Y', strtotime($item->bulan . '-01')),
                    'total' => $item->total,
                    'telat' => $item->telat,
                ];
            });

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showHistory($id, Request $request)
    {
        $auth = auth()->user();
        if ($auth->role === 'karyawan' && $auth->id != $id) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $user = User::findOrFail($id);

        $attendances = Attendance::where('user_id', $id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $lateCount = 0;
        $onTimeCount = 0;
        $expectedTime = Carbon::createFromTime(8, 0, 0);

        foreach ($attendances as $attendance) {
            if ($attendance->clock_in && preg_match('/^\d{2}:\d{2}:\d{2}$/', $attendance->clock_in)) {
                $clockInTime = Carbon::createFromFormat('H:i:s', $attendance->clock_in);
                if ($clockInTime->gt($expectedTime)) {
                    $lateCount++;
                } else {
                    $onTimeCount++;
                }
            }
        }

        return response()->json([
            'user' => new UserResource($user),
            'month' => $month,
            'year' => $year,
            'total_absen' => $attendances->count(),
            'tepat_waktu' => $onTimeCount,
            'terlambat' => $lateCount,
            'riwayat' => AttendanceResource::collection($attendances)
        ]);
    }

    public function exportExcel(Request $request) {
        $from = $request->query('from');
        $to = $request->query('to');
        $userId = $request->query('user_id');

        $attendances = $this->service->filterCombined($userId, $from, $to);

        return Excel::download(new AttendanceExport($attendances), 'absensi.xlsx');
    }

    public function exportPdf(Request $request) {
        $from = $request->query('from');
        $to = $request->query('to');
        $userId = $request->query('user_id');
        $attendances = $this->service->filterCombined($userId, $from, $to);
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('attendance.pdf', compact('attendances'));
        return $pdf->download('absensi.pdf');
    }

    public function reset($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->clock_in = null;
        $attendance->clock_out = null;
        $attendance->save();

        return response()->json(['message' => 'Absensi berhasil di-reset.']);
    }
    public function skipDay()
{
    $users = \App\Models\User::where('role', 'karyawan')->get();
    $date = Carbon::now('Asia/Jakarta')->addDay()->format('Y-m-d');
    $waktu = Carbon::createFromTime(8, 0, 0); // jam masuk tepat waktu

    foreach ($users as $user) {
        // Hanya tambah jika belum ada data tanggal itu
        $exists = Attendance::where('user_id', $user->id)->where('date', $date)->first();
        if (!$exists) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $date,
                'clock_in' => $waktu->toTimeString(),
                'clock_out' => $waktu->copy()->addHours(8)->toTimeString(),
            ]);
        }
    }

    return response()->json(['message' => 'Berhasil skip ke hari berikutnya dengan absensi otomatis.']);
}

    public function resetDay()
{
    // Dihitung sebagai hari sebenarnya
    $now = Carbon::now('Asia/Jakarta')->format('Y-m-d');

    // Hapus semua data absensi di masa depan (setelah hari ini)
    Attendance::where('date', '>', $now)->delete();

    return response()->json(['message' => 'Berhasil kembali ke hari yang sebenarnya. Data masa depan telah dihapus.']);
}

}

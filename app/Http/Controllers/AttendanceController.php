<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\User;
use App\Services\AttendanceService;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $service;

    public function __construct(AttendanceService $service)
    {
        $this->service = $service;
    }

    // Mengambil data absensi hari ini untuk user yang sedang login.
    // Jika ada izin yang disetujui, akan mengembalikan info izin.
    // Jika tidak, mengembalikan data clock_in, clock_out, dan status absensi hari ini.
    public function today(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

            $date = $request->query('date', Cache::get('current_date', Carbon::now('Asia/Jakarta')->toDateString()));

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

            return response()->json([
                'clock_in' => $attendance->clock_in ?? null,
                'clock_out' => $attendance->clock_out ?? null,
                'is_permission' => $attendance->is_permission ?? false,
                'permission_type' => $attendance->permission_type ?? null,
                'date' => $date
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat absensi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Melakukan clock in untuk user yang sedang login.
    // Jika sudah clock in dan belum clock out, akan menolak request.
    // Jika sudah di-reset (clock_in null), bisa clock in ulang.
    public function clockIn(Request $request)
    {
        try {
            $user = auth()->user();
            $today = Cache::get('current_date', Carbon::now('Asia/Jakarta')->toDateString());
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

                return response()->json(['message' => 'Sudah clock in dan clock out hari ini.'], 400);
            }

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => $now,
                'clock_out' => null,
                'is_permission' => false,
                'permission_type' => null,
            ]);

            return response()->json(['message' => 'Clock in berhasil.', 'data' => $attendance]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal clock in', 'error' => $e->getMessage()], 500);
        }
    }

    // Melakukan clock out untuk user yang sedang login.
    // Hanya bisa dilakukan jika sudah clock in dan belum clock out.
    public function clockOut(Request $request)
    {
        try {
            $user = auth()->user();
            $today = Cache::get('current_date', Carbon::now('Asia/Jakarta')->toDateString());
            $now = Carbon::now('Asia/Jakarta')->toTimeString();

            $attendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            if (!$attendance) return response()->json(['message' => 'Belum clock in.'], 400);
            if ($attendance->clock_out) return response()->json(['message' => 'Sudah clock out.'], 400);

            $attendance->clock_out = $now;
            $attendance->save();

            return response()->json(['message' => 'Clock out berhasil.', 'data' => $attendance]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal clock out', 'error' => $e->getMessage()], 500);
        }
    }

    // Mengambil riwayat absensi dan izin untuk user tertentu (by id) dalam bulan dan tahun tertentu.
    // Juga menghitung statistik kehadiran (tepat waktu, terlambat, izin).
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

    // Mengambil seluruh riwayat absensi (biasanya untuk admin).
    public function allHistory() {
        $data = $this->service->getAllHistory();
        return AttendanceResource::collection($data);
    }

    // Filter absensi berdasarkan user id.
    public function filterByUser(Request $request) {
        $userId = $request->query('user_id');
        $data = $this->service->filterByUser($userId);
        return AttendanceResource::collection($data);
    }

    // Filter absensi berdasarkan tanggal.
    public function filterByDate(Request $request) {
        $date = $request->query('date');
        $data = $this->service->filterByDate($date);
        return AttendanceResource::collection($data);
    }

    // Filter absensi berdasarkan kombinasi user id dan rentang tanggal.
    public function filterCombined(Request $request) {
        $userId = $request->query('user_id');
        $from = $request->query('from');
        $to = $request->query('to');
        $data = $this->service->filterCombined($userId, $from, $to);
        return AttendanceResource::collection($data);
    }

    // Statistik absensi (harian/bulanan): total kehadiran dan jumlah keterlambatan.
    public function statistik(Request $request)
    {
        try {
            $range = $request->query('range', 'monthly'); // default monthly

            if ($range === 'daily') {
                $data = Attendance::select(
                    DB::raw("DATE(date) as tanggal"),
                    DB::raw("COUNT(*) as total"),
                    DB::raw("SUM(CASE WHEN clock_in > '08:00:00' THEN 1 ELSE 0 END) as telat")
                )
                ->where('is_permission', false)
                ->whereNotNull('clock_in')
                ->where('clock_in', '!=', '00:00:00')
                ->groupBy('tanggal')
                ->orderBy('tanggal', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'tanggal' => date('d F Y', strtotime($item->tanggal)),
                        'total' => $item->total,
                        'telat' => $item->telat,
                    ];
                });
            } else { // monthly
                $data = Attendance::select(
                    DB::raw("DATE_FORMAT(date, '%Y-%m') as bulan"),
                    DB::raw("COUNT(*) as total"),
                    DB::raw("SUM(CASE WHEN clock_in > '08:00:00' THEN 1 ELSE 0 END) as telat")
                )
                ->where('is_permission', false)
                ->whereNotNull('clock_in')
                ->where('clock_in', '!=', '00:00:00')
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
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Menampilkan detail riwayat absensi dan statistik untuk user tertentu.
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

    // Export data absensi ke file Excel.
    public function exportExcel(Request $request) {
        $from = $request->query('from');
        $to = $request->query('to');
        $userId = $request->query('user_id');

        $attendances = $this->service->filterCombined($userId, $from, $to);

        return Excel::download(new AttendanceExport($attendances), 'absensi.xlsx');
    }

    // Export data absensi ke file PDF.
    public function exportPdf(Request $request) {
        $from = $request->query('from');
        $to = $request->query('to');
        $userId = $request->query('user_id');
        $attendances = $this->service->filterCombined($userId, $from, $to);
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('attendance.pdf', compact('attendances'));
        return $pdf->download('absensi.pdf');
    }


    // Reset clock_in dan clock_out absensi tertentu (by id).
    public function reset($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->clock_in = null;
        $attendance->clock_out = null;
        $attendance->save();

        return response()->json(['message' => 'Absensi berhasil di-reset.']);
    }


    // Simulasi hari berikutnya: menambah data absensi kosong untuk semua karyawan dan update tanggal aktif.
    public function skipDay()
    {
        try {
            $nextDate = Carbon::now('Asia/Jakarta')->addDay()->format('Y-m-d');

            // Simpan tanggal ke cache
            Cache::put('current_date', $nextDate);

            // Buat data absensi kosong untuk semua karyawan
            $users = User::where('role', 'karyawan')->get();
            foreach ($users as $user) {
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $nextDate,
                    'clock_in' => null,
                    'clock_out' => null,
                    'is_permission' => false,
                    'permission_type' => null,
                ]);
            }

            return response()->json(['message' => 'Hari berhasil disimulasikan', 'date' => $nextDate]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal skip hari',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Reset hari ke tanggal hari ini dan hapus data absensi di masa depan.
    public function resetDay()
    {
        try {
            $now = Carbon::now('Asia/Jakarta')->format('Y-m-d');

            // Reset current_date ke hari ini
            Cache::put('current_date', $now);

            Attendance::where('date', '>', $now)
                ->where(function ($q) {
                    $q->whereNull('is_permission')
                      ->orWhere('is_permission', false);
                })
                ->delete();

            return response()->json(['message' => 'Berhasil kembali ke hari yang sebenarnya.']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal reset hari',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Mengambil riwayat absensi user yang sedang login dalam rentang tanggal tertentu.
    public function myHistory(Request $request)
    {
        try {
            $user = $request->user();
            $from = $request->query('from');
            $to = $request->query('to');

            $attendances = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$from, $to])
                ->get();

            return response()->json([
                'data' => AttendanceResource::collection($attendances)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil riwayat',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}



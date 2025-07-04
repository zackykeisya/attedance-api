<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    // Mengambil data izin hari ini untuk user yang sedang login.
    public function today(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $permission = Permission::where('user_id', $user->id)
            ->where('date', $today)
            ->latest()
            ->first();

        if (!$permission) {
            return response()->json(['message' => 'Belum ada pengajuan izin hari ini'], 404);
        }

        return response()->json([
            'message' => 'Data izin hari ini ditemukan',
            'data' => $permission
        ]);
    }

    // Mengambil daftar izin milik user yang sedang login, bisa difilter by tanggal dan status.
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $query = Permission::where('user_id', $user->id)->orderBy('date', 'desc');

            // Filter berdasarkan rentang tanggal jika ada
            if ($request->has('from') && $request->has('to')) {
                $from = $request->query('from');
                $to = $request->query('to');

                if (!strtotime($from) || !strtotime($to)) {
                    return response()->json(['message' => 'Format tanggal tidak valid'], 400);
                }

                $query->whereBetween('date', [$from, $to]);
            }

            // Filter berdasarkan status jika ada
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $permissions = $query->get();

            return response()->json([
                'message' => 'Data izin berhasil diambil',
                'data' => $permissions
            ]);

        } catch (\Exception $e) {
            Log::error('Permission index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data izin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Mengambil daftar izin seluruh user (khusus admin), bisa difilter status.
    public function adminIndex(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = Permission::with('user')->orderBy('date', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $permissions = $query->get();

        return response()->json([
            'message' => 'Data izin admin berhasil diambil',
            'data' => $permissions
        ]);
    }

    // Mengajukan izin baru oleh user.
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'type' => 'required|in:sick,leave,other',
                'description' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $date = $request->date;

            // Cek jika sudah melakukan absensi pada tanggal tersebut
            if (Attendance::where('user_id', $user->id)->where('date', $date)->whereNotNull('clock_in')->exists()) {
                return response()->json([
                    'message' => 'Anda sudah melakukan absensi untuk tanggal ini'
                ], 400);
            }

            // Cek jika sudah mengajukan izin pada tanggal tersebut
            if (Permission::where('user_id', $user->id)->where('date', $date)->exists()) {
                return response()->json([
                    'message' => 'Anda sudah mengajukan izin untuk tanggal ini'
                ], 400);
            }

            // Simpan izin baru
            $permission = Permission::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'date' => $date,
                'type' => $request->type,
                'description' => $request->description,
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Izin berhasil diajukan',
                'data' => $permission
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission submission failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Mengubah status izin (approve/reject) oleh admin.
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $permission = Permission::find($id);
        if (!$permission) {
            return response()->json(['message' => 'Data izin tidak ditemukan'], 404);
        }

        $permission->status = $request->status;
        $permission->save();

        // Jika disetujui, update atau buat data absensi sebagai izin
        if ($request->status === 'approved') {
            Attendance::updateOrCreate(
                [
                    'user_id' => $permission->user_id,
                    'date' => $permission->date
                ],
                [
                    'clock_in' => '00:00:00',
                    'clock_out' => '00:00:00',
                    'is_permission' => true,
                    'permission_type' => $permission->type
                ]
            );
        }

        return response()->json([
            'message' => 'Status izin berhasil diperbarui',
            'data' => $permission
        ]);
    }

    // Menghapus data izin (biasanya oleh admin).
    public function reset($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(['message' => 'Data izin tidak ditemukan.'], 404);
        }

        $permission->delete();

        return response()->json([
            'message' => 'Izin berhasil dihapus'
        ]);
    }
}
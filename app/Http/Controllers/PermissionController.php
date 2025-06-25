<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'type' => 'required|in:sick,leave,other',
                'description' => 'required|string|max:500'
            ]);

            $user = $request->user();
            $date = $request->date;

            // Cek duplikasi izin
            if (Permission::where('user_id', $user->id)->where('date', $date)->exists()) {
                return response()->json([
                    'message' => 'Anda sudah mengajukan izin untuk tanggal ini'
                ], 400);
            }

            // Cek apakah sudah ada absensi
            if (Attendance::where('user_id', $user->id)->where('date', $date)->exists()) {
                return response()->json([
                    'message' => 'Anda sudah memiliki absensi untuk tanggal ini'
                ], 400);
            }

            $permission = Permission::create([
                'user_id' => $user->id,
                'date' => $date,
                'type' => $request->type,
                'description' => $request->description,
                'status' => 'pending'
            ]);

            return response()->json([
                'message' => 'Izin berhasil diajukan',
                'data' => $permission
            ], 201);

        } catch (\Exception $e) {
            Log::error('Permission submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $query = Permission::where('user_id', $user->id)->orderBy('date', 'desc');

            if ($request->has('from') && $request->has('to')) {
                $from = $request->query('from');
                $to = $request->query('to');

                // Validasi format tanggal
                if (!strtotime($from) || !strtotime($to)) {
                    return response()->json(['message' => 'Format tanggal tidak valid'], 400);
                }

                $query->whereBetween('date', [$from, $to]);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $permissions = $query->get();

            return response()->json($permissions);

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

    public function adminIndex(Request $request)
    {
        $status = $request->query('status', 'pending');

        $permissions = Permission::with('user')
            ->where('status', $status)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($permissions);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $permission = Permission::findOrFail($id);

        $permission->status = $request->status;
        $permission->save();

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
            'message' => 'Status izin berhasil diperbarui'
        ]);
    }
}

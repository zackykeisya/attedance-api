<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Services\UserService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    // ✅ Ambil semua user dengan role "karyawan"
    public function karyawan()
    {
        $karyawan = $this->service->getKaryawan();
        return UserResource::collection($karyawan);
    }

    // ✅ Tambah user karyawan baru
    public function createKaryawan(Request $request)
    {
        $this->validate($request, UserRequest::rules($request));
        $data = $request->all();
        $data['role'] = 'karyawan';
        return new UserResource($this->service->create($data));
    }

    // ✅ Ambil detail user karyawan berdasarkan ID
    public function showKaryawan($id)
    {
        return new UserResource($this->service->findByRole($id, 'karyawan'));
    }

    // ✅ Update user karyawan (biarkan password kosong jika tidak ingin diubah)
    public function updateKaryawan(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Validasi: perbolehkan email yang sama dengan email sekarang
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,karyawan',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['message' => 'User berhasil diperbarui.']);
    }

    // ✅ Hapus user karyawan
    public function destroyKaryawan($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }
}

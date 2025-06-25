<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller {
    public function login(Request $request) {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $credentials = $request->only(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Email atau password salah'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user()
        ]);
    }

public function register(Request $request) {
    try {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json($e->errors(), 422); // ✅ tampilkan error detail
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => app('hash')->make($request->password),
        'role' => 'admin'
    ]);

    return response()->json(['message' => 'Registrasi berhasil', 'user' => $user], 201);
}






public function logout()
{
    auth()->logout(); // Invalidate token
    return response()->json(['message' => 'Logout berhasil']);
}

}

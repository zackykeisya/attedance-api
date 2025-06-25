<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class UserRequest {
    public static function rules(Request $request)
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'role' => 'in:admin,karyawan' // boleh dikirim, tapi bukan required
    ];

        if ($request->isMethod('post')) {
            // Saat create, password wajib
            $rules['password'] = 'required|string|min:6';
        } else {
            // Saat update, password opsional tapi harus minimal 6 jika diisi
            $rules['password'] = 'nullable|string|min:6';
        }

        return $rules;
    }
}

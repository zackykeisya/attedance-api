<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class UserRequest {
    public static function rules(Request $request) {
        $rules = [
            'name' => 'required|string',
            'email' => 'required|email',
            'role' => 'required|in:admin,karyawan',
        ];

        if ($request->isMethod('post')) {
            $rules['password'] = 'required|min:6';
        }

        return $rules;
    }
}

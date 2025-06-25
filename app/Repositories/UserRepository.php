<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository {
    public function getKaryawan()
{
    return User::where('role', 'karyawan')->get();
}


    public function find($id) {
        return User::where('id', $id)->where('role', 'karyawan')->firstOrFail();
    }

    public function create($data) {
        $data['role'] = 'karyawan';
        return User::create($data);
    }

    public function updateKaryawan($id, $data)
{
    $user = User::where('id', $id)->where('role', 'karyawan')->firstOrFail();
    $user->update($data);
    return $user;
}


    public function delete($id) {
        $user = $this->find($id);
        $user->delete();
    }
}


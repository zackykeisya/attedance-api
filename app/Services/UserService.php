<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService {
    protected $repo;

    public function __construct(UserRepository $repo) {
        $this->repo = $repo;
    }

    public function all() {
        return $this->repo->getByRole('karyawan');
    }

    public function find($id) {
        return $this->repo->findByRole($id, 'karyawan');
    }

    public function create($data) {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'karyawan';
        return $this->repo->create($data);
    }

    public function updateKaryawan($id, $data)
{
    if (isset($data['password']) && $data['password']) {
        $data['password'] = \Hash::make($data['password']);
    } else {
        unset($data['password']); // agar tidak menimpa password lama dengan null
    }

    return $this->repo->updateKaryawan($id, $data);
}


    public function delete($id) {
        return $this->repo->deleteByRole($id, 'karyawan');
    }
    public function getKaryawan()
{
    return $this->repo->getKaryawan();
}

}


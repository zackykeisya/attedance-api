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
        return $this->repo->all();
    }

    public function find($id) {
        return $this->repo->find($id);
    }

    public function create($data) {
        $data['password'] = Hash::make($data['password']);
        return $this->repo->create($data);
    }

    public function update($id, $data) {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return $this->repo->update($id, $data);
    }

    public function delete($id) {
        return $this->repo->delete($id);
    }
}

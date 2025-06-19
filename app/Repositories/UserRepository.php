<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository {
    public function all() {
        return User::all();
    }

    public function find($id) {
        return User::findOrFail($id);
    }

    public function create($data) {
        return User::create($data);
    }

    public function update($id, $data) {
        $user = $this->find($id);
        $user->update($data);
        return $user;
    }

    public function delete($id) {
        $user = $this->find($id);
        $user->delete();
    }
}

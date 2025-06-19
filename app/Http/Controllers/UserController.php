<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Services\UserService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller {
    protected $service;

    public function __construct(UserService $service) {
        $this->service = $service;
    }

    public function index() {
        return UserResource::collection($this->service->all());
    }

    public function store(Request $request) {
        $this->validate($request, UserRequest::rules($request));
        return new UserResource($this->service->create($request->all()));
    }

    public function show($id) {
        return new UserResource($this->service->find($id));
    }

    public function update(Request $request, $id) {
        $this->validate($request, UserRequest::rules($request));
        return new UserResource($this->service->update($id, $request->all()));
    }

    public function destroy($id) {
        $this->service->delete($id);
        return response()->json(['message' => 'User deleted']);
    }
}

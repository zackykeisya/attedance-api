<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use App\Http\Resources\AttendanceResource;
use Illuminate\Http\Request;

class AttendanceController extends Controller {
    protected $service;

    public function __construct(AttendanceService $service) {
        $this->service = $service;
    }

    public function clockIn(Request $request) {
        $result = $this->service->clockIn($request->user()->id);
        return isset($result['error'])
            ? response()->json(['message' => $result['error']], 400)
            : response()->json($result);
    }

    public function clockOut(Request $request) {
        $result = $this->service->clockOut($request->user()->id);
        return isset($result['error'])
            ? response()->json(['message' => $result['error']], 400)
            : response()->json($result);
    }

    public function history(Request $request) {
        $from = $request->query('from');
        $to = $request->query('to');

        $data = $this->service->getUserHistory($request->user()->id, $from, $to);
        return AttendanceResource::collection($data);
    }

}

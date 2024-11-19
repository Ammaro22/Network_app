<?php

namespace App\Http\Controllers;

use App\Services\CheckService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CheckController extends Controller
{
    protected $checkService;

    public function __construct(CheckService $checkService)
    {
        $this->checkService = $checkService;
    }

    public function checkIn(Request $request)
    {
        try {
            $fileIds = $request->input('file_ids');
            $this->checkService->checkIn($fileIds);
            return response()->json(['message' => 'Check-ins recorded successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function checkOut(Request $request)
    {
        try {
            $fileIds = $request->input('file_ids');
            $this->checkService->checkOut($fileIds);
            return response()->json(['message' => 'Check-outs recorded successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function getGroupChecks(Request $request)
    {
        try {
            $checks = $this->checkService->getGroupChecks();
            return response()->json(['checks' => $checks]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

}

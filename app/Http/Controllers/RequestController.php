<?php

namespace App\Http\Controllers;

use App\Services\RequestService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RequestController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    public function index($groupId)
    {
        try {
            $requests = $this->requestService->getRequestsByGroupId($groupId);
            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function accept($requestId)
    {
        try {
            return $this->requestService->acceptRequest($requestId);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject($requestId)
    {
        try {
            return $this->requestService->rejectRequest($requestId);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

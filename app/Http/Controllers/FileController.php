<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }
    public function uploadToGroup(FileUploadRequest $request): JsonResponse
    {
        try {
            $uploadedFiles = $this->fileService->uploadFiles($request->file('files'), $request->input('group_id'));

            return response()->json([
                'data' => $uploadedFiles,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteFiles(Request $request): JsonResponse
    {
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:file_groups,file_id',
        ]);
        $userId = Auth::id();
        return $this->fileService->deleteFiles($request->input('file_ids'), $userId);
    }

    public function index(Request $request, $groupId)
    {
        $perPage = $request->get('per_page', 10);
        $files = $this->fileService->getFilesByGroupId($groupId, $perPage);
        return response()->json(['data'=>$files]);
    }
}

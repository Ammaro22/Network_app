<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Repositories\FileRepository;
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

    public function updateFile(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $groupId = $request->input('group_id');

            $fileService = new FileService(new FileRepository());

            if (!$fileService->isUserInGroup($groupId, $userId)) {
                throw new \Exception("Unauthorized access to this group.", 403);
            }

            if (!$request->hasFile('files')) {
                throw new \Exception("No files provided.", 422);
            }

            $files = $request->file('files');


            $response = $fileService->updateFile($request, $id, $groupId);

            return response()->json($response, 200);
        } catch (\Exception $e) {

            $statusCode = (int) $e->getCode();
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function showSimilarFiles(Request $request)
    {
        $fileName = $request->input('name');
        $groupId = $request->input('group_id');

        if (empty($fileName) || empty($groupId)) {
            return response()->json(['message' => 'File name and group ID are required.'], 400);
        }

        $files = $this->fileService->findSimilarFiles($fileName, $groupId);

        if ($files->isEmpty()) {
            return response()->json(['message' => 'No similar files found in the specified group.'], 404);
        }

        return response()->json($files, 200);
    }


}

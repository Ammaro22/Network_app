<?php


namespace App\Services;

use App\Models\File;
use App\Models\File_before_accept;
use App\Models\Group_member;
use App\Repositories\FileRepository;
use App\Traits\Imageable;
use App\Models\File_group;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileService
{
    use Imageable;

    protected $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    public function uploadFiles($files, $groupId)
    {
        $userId = Auth::id();


        if (!Group::where('id', $groupId)->exists()) {
            return response()->json(['message' => 'Group not found.'], 404);
        }
        $isOwner = Group::where('id', $groupId)->where('user_id', $userId)->exists();
        $isMember = Group_member::where('group_id', $groupId)->where('user_id', $userId)->exists();

        if ($isOwner) {
            $uploadedFileIds = $this->sssave($files, $groupId);

            \Log::info("Uploaded file IDs: ", ['ids' => $uploadedFileIds]);

            if (empty($uploadedFileIds)) {
                return response()->json(['message' => 'No files were uploaded.'], 400);
            }

            foreach ($uploadedFileIds as $fileId) {
                File_group::create([
                    'file_id' => $fileId,
                    'group_id' => $groupId,
                ]);
            }

            return response()->json(['message' => 'Files processed successfully.'], 200);
        } elseif ($isMember) {

            foreach ($files as $getFile) {
                $request = \App\Models\Request::create(['group_id' => $groupId]);
                $this->ssave([$getFile], $request->id);
            }

            return response()->json(['message' => 'Files added for approval successfully.'], 201);
        } else {
            return response()->json(['message' => 'Unauthorized. You do not have permission to upload files.'], 403);
        }
    }


    public function deleteFiles($fileIds, $userId)
    {

        $fileGroups = File_group::whereIn('file_id', $fileIds)->with('group')->get();

        foreach ($fileGroups as $fileGroup) {
            if ($fileGroup->group->user_id != $userId) {
                return response()->json(['message' => 'Unauthorized. You do not own this group.'], 403);
            }

            $fileGroup->delete();

            $file = File::find($fileGroup->file_id);
            if ($file) {
                Storage::delete($file->path);

                $file->delete();
            }
        }

        return response()->json(['message' => 'Files deleted successfully.']);
    }

    public function getFilesByGroupId($groupId, $perPage = 10)
    {
        return $this->fileRepository->getFilesByGroupId($groupId, $perPage);
    }

}

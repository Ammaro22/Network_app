<?php


namespace App\Services;

use App\Models\Change;
use App\Models\Check;
use App\Models\File;
use App\Models\File_before_accept;
use App\Models\FileEdit;
use App\Models\Fileold;
use App\Models\Group_member;
use App\Models\User;
use App\Repositories\FileRepository;
use App\Traits\Imageable;
use App\Models\File_group;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileService
{
    use Imageable;

    protected $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

//    public function uploadFiles($files, $groupId)
//    {
//        $userId = Auth::id();
//        $user = User::find($userId);
//        $userName = $user ? $user->user_name : 'Unknown User';
//
//        if (!Group::where('id', $groupId)->exists()) {
//            return response()->json(['message' => 'Group not found.'], 404);
//        }
//
//        $isOwner = Group::where('id', $groupId)->where('user_id', $userId)->exists();
//        $isMember = Group_member::where('group_id', $groupId)->where('user_id', $userId)->exists();
//
//        if ($isOwner) {
//            foreach ($files as $file) {
//                $originalName = $file->getClientOriginalName();
//                $fileBaseName = preg_replace('/(_\d+)*$/', '', pathinfo($originalName, PATHINFO_FILENAME));
//                $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
//                $fullFileName = $fileBaseName . '.' . $fileExtension;
//
//                $existingFilesInGroup = File_group::where('group_id', $groupId)
//                    ->join('files', 'file_groups.file_id', '=', 'files.id')
//                    ->get(['files.name']);
//
//                foreach ($existingFilesInGroup as $existingFile) {
//                    $existingBaseName = preg_replace('/(_\d+)*$/', '', pathinfo($existingFile->name, PATHINFO_FILENAME));
//                    $existingExtension = pathinfo($existingFile->name, PATHINFO_EXTENSION);
//
//                    if ($existingBaseName === $fileBaseName && $existingExtension === $fileExtension) {
//                        return response()->json(['message' => "File '{$fullFileName}' already exists in this group. Upload failed."], 409);
//                    }
//                }
//            }
//
//            $uploadedFileIds = $this->sssave($files, $groupId);
//
//            \Log::info("Uploaded file IDs: ", ['ids' => $uploadedFileIds]);
//
//            if (empty($uploadedFileIds)) {
//                return response()->json(['message' => 'No files were uploaded.'], 400);
//            }
//
//            foreach ($uploadedFileIds as $fileId) {
//                File_group::create([
//                    'file_id' => $fileId,
//                    'group_id' => $groupId,
//                ]);
//            }
//
//            return response()->json(['message' => 'Files processed successfully.'], 200);
//        } elseif ($isMember) {
//            foreach ($files as $getFile) {
//                $request = \App\Models\Request::create(['group_id' => $groupId, 'user_name' => $userName]);
//                $this->ssave([$getFile], $request->id);
//            }
//
//            return response()->json(['message' => 'Files added for approval successfully.'], 201);
//        } else {
//            return response()->json(['message' => 'Unauthorized. You do not have permission to upload files.'], 403);
//        }
//    }

    public function uploadFiles($files, $groupId)
    {
        $userId = Auth::id();
        $user = User::find($userId);
        $userName = $user ? $user->user_name : 'Unknown User';


        if (!Group::where('id', $groupId)->exists()) {
            return response()->json(['message' => 'Group not found.'], 404);
        }


        $isOwner = Group::where('id', $groupId)->where('user_id', $userId)->exists();
        $isMember = Group_member::where('group_id', $groupId)->where('user_id', $userId)->exists();

        if ($isOwner) {
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $fileBaseName = preg_replace('/(_\d+)*$/', '', pathinfo($originalName, PATHINFO_FILENAME));
                $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                $fullFileName = $fileBaseName . '.' . $fileExtension;

                $existingFilesInGroup = File_group::where('group_id', $groupId)
                    ->join('files', 'file_groups.file_id', '=', 'files.id')
                    ->lockForUpdate()
                    ->get(['files.name']);

                foreach ($existingFilesInGroup as $existingFile) {
                    $existingBaseName = preg_replace('/(_\d+)*$/', '', pathinfo($existingFile->name, PATHINFO_FILENAME));
                    $existingExtension = pathinfo($existingFile->name, PATHINFO_EXTENSION);

                    if ($existingBaseName === $fileBaseName && $existingExtension === $fileExtension) {
                        return response()->json(['message' => "File '{$fullFileName}' already exists in this group. Upload failed."], 409);
                    }
                }
            }


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
                $request = \App\Models\Request::create(['group_id' => $groupId, 'user_name' => $userName]);
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

            $file = File::find($fileGroup->file_id);
            if ($file) {
                if ($file->state == 1) {
                    return response()->json(['message' => 'Cannot delete file. It is currently reserved.'], 403);
                }

                Storage::delete($file->path);
                $file->delete();
            }

            $fileGroup->delete();

            Log::channel('stack')->info('delete files ', [
                'file_id' => $fileGroup->file_id,
                'user_id' => $fileGroup->group->user_id,
                'group_id' => $fileGroup->group_id,
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        }

        return response()->json(['message' => 'Files deleted successfully.']);
    }

    public function getFilesByGroupId($groupId, $perPage = 10)
    {
        Log::channel('stack')->info('get files in group', [
            'group_id'=>$groupId,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);

        return $this->fileRepository->getFilesByGroupId($groupId, $perPage);
    }       //log


//    public function updateFile(Request $request, $filesId, $groupId)
//    {
//        try {
//            $userId = $request->user()->id;
//
//            if (!$this->isUserInGroup($groupId, $userId)) {
//                throw new \Exception("Unauthorized access to this group.", 403);
//            }
//
//            if (!$request->hasFile('files')) {
//                throw new \Exception("No files provided.", 422);
//            }
//
//            $files = $request->file('files');
//
//            $file = $this->fileRepository->findFileById($filesId);
//            if (!$file) {
//                throw new \Exception("File not found.", 404);
//            }
//            $isInGroup = $file->file_group()->where('group_id', $groupId)->exists();
//            if (!$isInGroup) {
//                throw new \Exception("The requested file does not belong to the specified group.", 403);
//            }
//
//            if ($file->state !== 1) {
//                throw new \Exception("The file is not reserved for editing.", 403);
//            }
//
//            $checkInRecord = Check::where('file_id', $filesId)
//                ->where('user_id', $userId)
//                ->where('type_check', 'checkin')
//                ->first();
//
//            if (!$checkInRecord || $checkInRecord->user_id !== $userId) {
//                throw new \Exception("The file is not reserved for editing by you.", 403);
//            }
//
//            $originalFileId = $this->fileRepository->saveOldFileRecord($file, $groupId);
//            $uploadedFiles = $this->fileRepository->processFileEdits($files);
//
//            if (empty($uploadedFiles)) {
//                throw new \Exception("No file edits found. Uploaded files array is empty.", 404);
//            }
//
//            $file->delete();
//
//            foreach ($uploadedFiles as $editRecord) {
//                if (!is_object($editRecord)) {
//                    throw new \Exception("New file record not found in edits. Received: " . json_encode($editRecord), 404);
//                }
//
//                $newFile = File::create([
//                    'name' => $editRecord->name,
//                    'path' => $editRecord->path,
//                    'state' => 1
//                ]);
//
//                $this->fileRepository->addFileToGroup($newFile->id, $groupId);
//                Check::create([
//                    'file_id' => $newFile->id,
//                    'user_id' => $userId,
//                    'type_check' => 'checkin',
//                    'created_at' => now(),
//                    'updated_at' => now()
//                ]);
//
//                $editRecord->delete();
//            }
//
//
//            $comparisonResponse = $this->fileRepository->compareFiles(new Request([
//                'original_file_id' => $originalFileId,
//                'modified_file_id' => $newFile->id ,
//                'user_name' => $request->user()->user_name
//            ]));
//
//            return response()->json([
//                "message" => "File updated successfully",
//                "file" => $file,
//                "comparison" => json_decode($comparisonResponse->getContent())
//            ], 200);
//        } catch (\Exception $e) {
//            Log::error("Error updating file: " . $e->getMessage());
//            return response()->json([
//                "message" => "Error: " . $e->getMessage(),
//                "code" => $e->getCode()
//            ], $e->getCode() ?: 500);
//        }
//    }

    public function updateFile(Request $request, $filesId, $groupId)
    {
        try {
            $userId = $request->user()->id;

            if (!$this->isUserInGroup($groupId, $userId)) {
                throw new \Exception("Unauthorized access to this group.", 403);
            }

            if (!$request->hasFile('files')) {
                throw new \Exception("No files provided.", 422);
            }

            $files = $request->file('files');

            $file = $this->fileRepository->findFileById($filesId);
            if (!$file) {
                throw new \Exception("File not found.", 404);
            }

            if (!$file->file_group()->where('group_id', $groupId)->exists()) {
                throw new \Exception("The requested file does not belong to the specified group.", 403);
            }

            if ($file->state !== 1) {
                throw new \Exception("The file is not reserved for editing.", 403);
            }

            $checkInRecord = Check::where('file_id', $filesId)
                ->where('user_id', $userId)
                ->where('type_check', 'checkin')
                ->first();

            if (!$checkInRecord || $checkInRecord->user_id !== $userId) {
                throw new \Exception("The file is not reserved for editing by you.", 403);
            }

            $originalFileId = $this->fileRepository->saveOldFileRecord($file, $groupId);
            $uploadedFiles = $this->fileRepository->processFileEdits($files);

            if (empty($uploadedFiles)) {
                throw new \Exception("No file edits found. Uploaded files array is empty.", 404);
            }

            $file->delete();

            foreach ($uploadedFiles as $editRecord) {

                if (!is_object($editRecord)) {
                    throw new \Exception("New file record not found in edits. Received: " . json_encode($editRecord), 404);
                }

                $newFile = File::create([
                    'name' => $editRecord->name,
                    'path' => $editRecord->path,
                    'state' => 1
                ]);

                $this->fileRepository->addFileToGroup($newFile->id, $groupId);
                Check::create([
                    'file_id' => $newFile->id,
                    'user_id' => $userId,
                    'type_check' => 'checkin',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $editRecord->delete();
            }

            $comparisonResponse = $this->fileRepository->compareFiles(new Request([
                'original_file_id' => $originalFileId,
                'modified_file_id' => $newFile->id,
                'user_name' => $request->user()->user_name
            ]));

            return response()->json([
                "message" => "File updated successfully",
                "file" => $file,
                "comparison" => json_decode($comparisonResponse->getContent())
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error updating file: " . $e->getMessage());
            return response()->json([
                "message" => "Error: " . $e->getMessage(),
                "code" => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }


    public function isUserInGroup($groupId, $userId)
    {
        $groupMember = Group_member::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        return $groupMember !== null || Group::where('id', $groupId)
                ->where('user_id', $userId)
                ->exists();
    }

    public function findSimilarFiles($fileName, $groupId)
    {
        return $this->fileRepository->getSimilarFiles($fileName, $groupId);
    }

    public function findChangeFile($fileName, $groupId)
    {
        return $this->fileRepository->getChangesForFile($fileName, $groupId);
    }


    public function transferFile(Request $request)
    {
        $fileId = $request->input('file_id');
        $fileOldId = $request->input('file_old_id');
        $userId = $request->user()->id;

        try {

            $file = File::find($fileId);
            $fileOld = Fileold::find($fileOldId);

            if (!$file) {
                return response()->json(['error' => 'File not found in table file.'], 404);
            }

            if (!$fileOld) {
                return response()->json(['error' => 'File not found in table file_old.'], 404);
            }
            $file = $this->fileRepository->findFileById($fileId);

            if ($file->state !== 1) {
                throw new \Exception("The file is not reserved for editing.", 403);
            }

            $checkInRecord = Check::where('file_id', $fileId)
                ->where('user_id', $userId)
                ->where('type_check', 'checkin')
                ->first();

            if (!$checkInRecord || $checkInRecord->user_id !== $userId) {
                throw new \Exception("The file is not reserved for editing by you.", 403);
            }


            $oldPath = public_path($fileOld->path);
            $newPath = str_replace('file/', 'fileEdit/', $fileOld->path);
            $newFullPath = public_path($newPath);

            if (!file_exists(dirname($newFullPath))) {
                mkdir(dirname($newFullPath), 0777, true);
            }

            if (!rename($oldPath, $newFullPath)) {
                return response()->json(['error' => 'Failed to move the file to the new directory.'], 500);
            }

            $newFile = File::create([
                'name' => $fileOld->name,
                'path' => $newPath,
                'state' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Check::create([
                'file_id' => $newFile->id,
                'user_id' => $userId,
                'type_check' => 'checkin',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $filePath = public_path($file->path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            Change::where('file_new_name', $file->name)->delete();

            $file->delete();
            $fileOld->delete();

            return response()->json([
                'message' => 'File transferred successfully with updated path.',
                'new_file' => $newFile
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred during the transfer.',
                'details' => $e->getMessage()
            ], 500);
        }
    }







}

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

            $this->fileRepository->saveOldFileRecord($file);
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

            return response()->json([
                "message" => "File updated successfully",
                "file" => $file
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

    protected function compareLargeFiles($oldFilePath, $newFilePath, $fileId, $userId)
    {
        $oldFileHandle = fopen($oldFilePath, 'r');
        $newFileHandle = fopen($newFilePath, 'r');

        $oldLineNumber = 1;
        $newLineNumber = 1;
        $userName = Auth::user()->user_name;

        while (!feof($oldFileHandle) || !feof($newFileHandle)) {
            $oldLine = fgets($oldFileHandle);
            $newLine = fgets($newFileHandle);

            if ($oldLine !== $newLine) {
                if ($oldLine === false) {

                    Change::create([
                        'file_id' => $fileId,
                        'old_value' => null,
                        'new_value' => trim($newLine),
                        'field_name' => "New Line " . $newLineNumber,
                        'user_id' => $userId,
                        'user_name' => $userName,
                    ]);
                } elseif ($newLine === false) {

                    Change::create([
                        'file_id' => $fileId,
                        'old_value' => trim($oldLine),
                        'new_value' => null,
                        'field_name' => "Deleted Line " . $oldLineNumber,
                        'user_id' => $userId,
                        'user_name' => $userName,
                    ]);
                } else {

                    Change::create([
                        'file_id' => $fileId,
                        'old_value' => trim($oldLine),
                        'new_value' => trim($newLine),
                        'field_name' => "Line " . $oldLineNumber,
                        'user_id' => $userId,
                        'user_name' => $userName,
                    ]);
                }
            }

            if ($oldLine !== false) {
                $oldLineNumber++;
            }
            if ($newLine !== false) {
                $newLineNumber++;
            }
        }

        fclose($oldFileHandle);
        fclose($newFileHandle);
    }

}

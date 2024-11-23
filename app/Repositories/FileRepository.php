<?php
namespace App\Repositories;

use App\Models\File;
use App\Models\File_group;
use App\Models\Fileold;
use App\Models\Group;
use App\Traits\Imageable;
use Illuminate\Support\Facades\Log;

class FileRepository
{
    use Imageable;
    public function getFilesByGroupId($groupId, $perPage = 10)
    {
        return File::whereHas('file_group', function ($query) use ($groupId) {
            $query->where('group_id', $groupId);
        })->paginate($perPage);
    }

    public function findFileById($id)
    {
        return File::find($id);
    }

    public function createFile(array $data)
    {
        return File::create($data);
    }

    public function deleteFile(File $file)
    {
        return $file->delete();
    }

    public function addFileToGroup($fileId, $groupId)
    {

        $group = Group::find($groupId);
        if (!$group) {
            throw new \Exception("Group not found.", 404);
        }

        $fileGroup = new File_group();
        $fileGroup->file_id = $fileId;
        $fileGroup->group_id = $groupId;
        $fileGroup->save();
    }
    public function processFileEdits($files)
    {

        $uploadedFiles = $this->ssssave($files);
        if (empty($uploadedFiles)) {
            throw new \Exception("No file edits found. Uploaded files array is empty.", 404);
        }

        return $uploadedFiles;
    }

//    public function saveOldFileRecord($file)
//    {
//
//        $newName = pathinfo($file->name, PATHINFO_FILENAME) . '_v1.' . pathinfo($file->name, PATHINFO_EXTENSION);
//
//        $oldFileRecord = new Fileold();
//        $oldFileRecord->name = $newName;
//        $oldFileRecord->path = 'file/' . $newName;
//        $oldFileRecord->save();
//        $destinationPath = public_path('file');
//        $sourcePath = $file->path;
//
//        if (!file_exists($destinationPath)) {
//            mkdir($destinationPath, 0755, true);
//        }
//
//        $newFilePath = $destinationPath . '/' . $file->name;
//        rename($sourcePath, $newFilePath);
//    }
    public function saveOldFileRecord($file, $groupId)
    {
        $filename = pathinfo($file->name, PATHINFO_FILENAME);
        $extension = pathinfo($file->name, PATHINFO_EXTENSION);

        $version = 1;
        do {
            $newName = $filename . '_v' . $version . '.' . $extension;
            $newPath = public_path('file/' . $newName);
            $version++;
        } while (file_exists($newPath));

        $oldFileRecord = new Fileold();
        $oldFileRecord->name = $newName;
        $oldFileRecord->path = 'file/' . $newName;
        $oldFileRecord->group_id = $groupId;
        $oldFileRecord->save();

        $destinationPath = public_path('file');
        $sourcePath = $file->path;

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $newFilePath = $destinationPath . '/' . $newName;
        rename($sourcePath, $newFilePath);
    }


//    public function getSimilarFiles($fileName, $groupId)
//    {
//        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
//        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
//
//        $cleanBaseName = preg_replace('/_\d+$/', '', $baseName);
//
//        return Fileold::where('name', 'like', $cleanBaseName . '%')
//            ->where('name', 'like', '%' . $extension)
//            ->whereHas('file_group', function($query) use ($groupId) {
//                $query->where('group_id', $groupId);
//            })
//            ->get();
//    }
    public function getSimilarFiles($fileName, $groupId)
    {

        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $cleanBaseName = preg_replace('/(_v\d+)?$/', '', $baseName);

        return Fileold::where('name', 'like', $cleanBaseName . '%')
        ->where('name', 'like', '%' . $extension)
        ->whereHas('group', function($query) use ($groupId) {
            $query->where('group_id', $groupId);
        })
            ->get();
    }

}

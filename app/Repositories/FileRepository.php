<?php
namespace App\Repositories;

use App\Models\File;
use App\Models\File_group;
use App\Models\Fileold;
use App\Models\Group;
use App\Traits\Imageable;

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

    public function saveOldFileRecord($file)
    {

        $oldFileRecord = new Fileold();
        $oldFileRecord->name = $file->name;
        $oldFileRecord->path = 'file/' . $file->name;
        $oldFileRecord->save();


        $destinationPath = public_path('file');
        $sourcePath = $file->path;

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $newFilePath = $destinationPath . '/' . $file->name;
        rename($sourcePath, $newFilePath);
    }

    public function getSimilarFiles($fileName)
    {

        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $cleanBaseName = preg_replace('/_\d+$/', '', $baseName);

        return Fileold::where('name', 'like', $cleanBaseName . '%')
            ->where('name', 'like', '%' . $extension)
            ->get();
    }

}

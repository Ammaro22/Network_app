<?php
namespace App\Repositories;

use App\Models\Check;
use App\Models\File;

class CheckRepository
{
    public function createCheck($userId, $fileId, $typeCheck)
    {
        return Check::create([
            'user_id' => $userId,
            'file_id' => $fileId,
            'type_check' => $typeCheck,
        ]);
    }

    public function updateFileState(array $fileIds, int $state)
    {
        return File::whereIn('id', $fileIds)->update(['state' => $state]);
    }

    public function getChecksByFileGroupIds(array $fileGroupIds)
    {
        return Check::whereIn('file_id', $fileGroupIds)->with(['file'])->get();
    }
    public function getFilesByIds(array $fileIds)
    {
        return File::whereIn('id', $fileIds)->get();
    }

}

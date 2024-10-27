<?php
namespace App\Repositories;

use App\Models\File;

class FileRepository
{

    public function getFilesByGroupId($groupId, $perPage = 10)
    {
        return File::whereHas('file_group', function ($query) use ($groupId) {
            $query->where('group_id', $groupId);
        })->paginate($perPage);
    }
}

<?php
namespace App\Services;

use App\Models\Check;
use App\Models\File;
use App\Repositories\CheckRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckService
{
    protected $checkRepository;

    public function __construct(CheckRepository $checkRepository)
    {
        $this->checkRepository = $checkRepository;
    }

//    public function checkIn(array $fileIds)
//    {
//        $user = Auth::user();
//        if (!$user) {
//            throw new \Exception('Unauthorized', 401);
//        }
//
//        if (empty($fileIds) || !is_array($fileIds)) {
//            throw new \Exception('File IDs are required and should be an array', 400);
//        }
//
//
//        foreach ($fileIds as $fileId) {
//            $this->checkRepository->createCheck($user->id, $fileId, 'checkin');
//        }
//
//
//        $this->checkRepository->updateFileState($fileIds, 1);
//    }

    public function checkIn(array $fileIds)
    {
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Unauthorized', 401);
        }

        if (empty($fileIds) || !is_array($fileIds)) {
            throw new \Exception('File IDs are required and should be an array', 400);
        }

        // التأكد من وجود الملفات في قاعدة البيانات
        $existingFiles = File::whereIn('id', $fileIds)->pluck('id')->toArray();

        if (count($existingFiles) !== count($fileIds)) {
            throw new \Exception('One or more file IDs do not exist.', 404);
        }

        foreach ($fileIds as $fileId) {
            $this->checkRepository->createCheck($user->id, $fileId, 'checkin');
        }

        $this->checkRepository->updateFileState($fileIds, 1);
    }

    public function checkOut(array $fileIds)
    {
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Unauthorized', 401);
        }

        if (empty($fileIds) || !is_array($fileIds)) {
            throw new \Exception('File IDs are required and should be an array', 400);
        }

        foreach ($fileIds as $fileId) {

            $checkInRecord = Check::where('file_id', $fileId)
                ->where('user_id', $user->id)
                ->where('type_check', 'checkin')
                ->lockForUpdate()
                ->first();

            if (!$checkInRecord) {
                throw new \Exception("User is not authorized to check out file with ID {$fileId}. No check-in record found.", 403);
            }

            $this->checkRepository->createCheck($user->id, $fileId, 'checkout');
        }

        $this->checkRepository->updateFileState($fileIds, 0);
    }

    public function getGroupChecks()
    {
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Unauthorized', 401);
        }
        $fileGroupIds = [];
        foreach ($user->group as $group) {
            foreach ($group->file_group as $fileGroup) {
                $fileGroupIds[] = $fileGroup->file_id;
            }
        }

        $checks = $this->checkRepository->getChecksByFileGroupIds($fileGroupIds);

        return $checks->map(function ($check) {
            return [
                'id' => $check->id,
                'user_id' => $check->user_id,
                'user_name' => $check->user ? $check->user->user_name : null,
                'file_id' => $check->file_id,
                'type_check' => $check->type_check,
                'created_at' => $check->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }


}

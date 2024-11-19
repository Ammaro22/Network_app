<?php
namespace App\Services;

use App\Repositories\CheckRepository;
use Illuminate\Support\Facades\Auth;

class CheckService
{
    protected $checkRepository;

    public function __construct(CheckRepository $checkRepository)
    {
        $this->checkRepository = $checkRepository;
    }

    public function checkIn(array $fileIds)
    {
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Unauthorized', 401);
        }

        if (empty($fileIds) || !is_array($fileIds)) {
            throw new \Exception('File IDs are required and should be an array', 400);
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
                'file_id' => $check->file_id,
                'type_check' => $check->type_check,
                'created_at' => $check->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }

}

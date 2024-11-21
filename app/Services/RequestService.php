<?php
namespace App\Services;

use App\Models\File;
use App\Models\File_before_accept;
use App\Models\File_group;
use App\Models\Group;
use App\Models\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequestService
{


    public function getRequestsByGroupId($groupId)
    {
        $user = auth()->user();
        if (!$user) {
            throw new \Exception('Unauthorized access. User is not logged in.');
        }
        $group = Group::find($groupId);
        if (!$group) {
            throw new \Exception('Group not found.');
        }


        if ($group->user_id !== $user->id) {
            throw new \Exception('Unauthorized access. You are not the owner of this group.');
        }
        Log::channel('stack')->info('show requests', [
            'request from user'=>$group->user_id,
            'request in group'=>$group->id,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);

        return Request::where('group_id', $groupId)->get();
    }      //log

//    public function acceptRequest($requestId)
//    {
//        $user = Auth::user();
//        $request = Request::find($requestId);
//
//        if (!$request) {
//            throw new \Exception('Request not found.');
//        }
//
//        $group = $request->group;
//        if (!$group) {
//            throw new \Exception('Group not found.');
//        }
//
//        if ($group->user_id !== $user->id) {
//            throw new \Exception('Unauthorized access. You are not the owner of this group.');
//        }
//
//        $filesBeforeAccept = File_before_accept::where('request_id', $requestId)->get();
//
//        $filesToCheck = [];
//        foreach ($filesBeforeAccept as $fileBeforeAccept) {
//            $fileBaseName = preg_replace('/_\d+$/', '', pathinfo($fileBeforeAccept->name, PATHINFO_FILENAME));
//            $fileExtension = pathinfo($fileBeforeAccept->name, PATHINFO_EXTENSION);
//            $filesToCheck[] = ['baseName' => $fileBaseName, 'extension' => $fileExtension];
//        }
//
//        $existingFiles = File::all();
//
//        foreach ($filesToCheck as $fileToCheck) {
//            foreach ($existingFiles as $existingFile) {
//                $existingBaseName = preg_replace('/_\d+$/', '', pathinfo($existingFile->name, PATHINFO_FILENAME));
//                $existingExtension = pathinfo($existingFile->name, PATHINFO_EXTENSION);
//
//                if ($existingBaseName === $fileToCheck['baseName'] && $existingExtension === $fileToCheck['extension']) {
//                    return response()->json(['message' => "File with the same name '{$existingFile->name}' already exists. Upload failed."], 409);
//                }
//            }
//        }
//
//        $filesUploaded = [];
//
//        foreach ($filesBeforeAccept as $fileBeforeAccept) {
//            $file = File::create([
//                'name' => $fileBeforeAccept->name,
//                'path' => $fileBeforeAccept->path,
//                'state' => $fileBeforeAccept->state,
//            ]);
//
//            File_group::create([
//                'file_id' => $file->id,
//                'group_id' => $request->group_id,
//            ]);
//
//            $filesUploaded[] = $file;
//        }
//
//        if (!empty($filesUploaded)) {
//            $request->delete();
//            File_before_accept::where('request_id', $requestId)->delete();
//        }
//
//        Log::channel('stack')->info('accept requests', [
//            'Accepted Request Information' => [
//                'user_id :' . $user->id,
//                'group_id :' . $group->id,
//            ],
//            'ip_address' => request()->ip(),
//            'timestamp' => now(),
//        ]);
//
//        return response()->json(['message' => 'Request accepted and files moved successfully.']);
//    }

    public function acceptRequest($requestId)
    {
        $user = Auth::user();
        $request = Request::find($requestId);

        if (!$request) {
            throw new \Exception('Request not found.');
        }

        $group = $request->group;
        if (!$group) {
            throw new \Exception('Group not found.');
        }

        if ($group->user_id !== $user->id) {
            throw new \Exception('Unauthorized access. You are not the owner of this group.');
        }

        $filesBeforeAccept = File_before_accept::where('request_id', $requestId)->get();

        $filesToCheck = [];
        foreach ($filesBeforeAccept as $fileBeforeAccept) {
            $fileBaseName = preg_replace('/_\d+$/', '', pathinfo($fileBeforeAccept->name, PATHINFO_FILENAME));
            $fileExtension = pathinfo($fileBeforeAccept->name, PATHINFO_EXTENSION);
            $filesToCheck[] = ['baseName' => $fileBaseName, 'extension' => $fileExtension];
        }

        $existingFilesInGroup = File_group::where('group_id', $request->group_id)
            ->join('files', 'file_groups.file_id', '=', 'files.id')
            ->get(['files.name']);

        foreach ($filesToCheck as $fileToCheck) {
            foreach ($existingFilesInGroup as $existingFile) {
                $existingBaseName = preg_replace('/_\d+$/', '', pathinfo($existingFile->name, PATHINFO_FILENAME));
                $existingExtension = pathinfo($existingFile->name, PATHINFO_EXTENSION);

                if ($existingBaseName === $fileToCheck['baseName'] && $existingExtension === $fileToCheck['extension']) {
                    return response()->json(['message' => "File with the same name '{$existingFile->name}' already exists in this group. Upload failed."], 409);
                }
            }
        }

        $filesUploaded = [];

        foreach ($filesBeforeAccept as $fileBeforeAccept) {
            $file = File::create([
                'name' => $fileBeforeAccept->name,
                'path' => $fileBeforeAccept->path,
                'state' => $fileBeforeAccept->state,
            ]);

            File_group::create([
                'file_id' => $file->id,
                'group_id' => $request->group_id,
            ]);

            $filesUploaded[] = $file;
        }

        if (!empty($filesUploaded)) {
            $request->delete();
            File_before_accept::where('request_id', $requestId)->delete();
        }

        Log::channel('stack')->info('accept requests', [
            'Accepted Request Information' => [
                'user_id' => $user->id,
                'group_id' => $group->id,
            ],
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);

        return response()->json(['message' => 'Request accepted and files moved successfully.']);
    }

    public function rejectRequest($requestId)
    {
        $user = Auth::user();
        $request = Request::find($requestId);
        if (!$request) {
            throw new \Exception('Request not found.');
        }
        $group = $request->group;
        if (!$group) {
            throw new \Exception('Group not found.');
        }
        if ($group->user_id !== $user->id) {
            throw new \Exception('Unauthorized access. You are not the owner of this group.');
        }
        File_before_accept::where('request_id', $requestId)->delete();


        $request->delete();
        Log::channel('stack')->info('reject requests', [
            'rejected Request Information'=>
                ['user_id :' .$user->id,
                 'group_id :' .$group->id,
                ],
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return response()->json(['message' => 'Request rejected and associated files deleted successfully.']);
    }     //log


}

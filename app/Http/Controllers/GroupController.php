<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    protected $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function createGroup(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $userId = Auth::id();
        $groupName = $request->input('name');

        // تحقق من وجود مجموعة بنفس الاسم
        if (Group::where('name', $groupName)->exists()) {
            return response()->json(['message' => 'A group with the same name already exists.'], 409);
        }

        $groupData = [
            'user_id' => $userId,
            'name' => $groupName,
        ];

        $group = $this->groupService->createGroup($groupData);

        return response()->json([
            'message' => 'Group created successfully',
            'group' => $group
        ], 201);
    }

    public function addUsers(GroupRequest $request, $groupId)
    {
        try {
            return $this->groupService->addUsersToGroup($groupId, $request->user_ids);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function removeUser(Request $request, $groupId)
    {
        try {
            return $this->groupService->removeUserFromGroup($request, $groupId);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getUsers($groupId)
    {
        try {
            return $this->groupService->getUsersInGroup($groupId);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getGroup()
    {
        $userId = Auth::id();
        $groups = $this->groupService->getGroupCreatedByUser($userId);
        return response()->json(['data'=>$groups]);
    }

    public function getGroupUserIn()
    {
        $userId = Auth::id();
        $groups = $this->groupService->getGroupUserIn($userId);
        return response()->json(['data'=>$groups]);

    }
    public function destroy($id)
    {
        if ($this->groupService->deleteGroupById($id)) {
            return response()->json(['message' => 'Group deleted successfully'], 200);
        }
        return response()->json(['message' => 'Group not found'], 404);
    }


}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
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
        $groupData = [
            'user_id' => $userId,
            'name' => $request->input('name'),
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

    public function index()
    {
        $userId = Auth::id();
        $groups = $this->groupService->getUserGroups($userId);
        return response()->json($groups);
    }
}

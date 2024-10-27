<?php


namespace App\Services;

use App\Models\Group;
use App\Models\Group_member;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupService
{
    public function createGroup()
    {
        $userId = Auth::id();
        return Group::create([
            'user_id' => $userId,
        ]);
    }
    public function addUsersToGroup($groupId, array $userIds)
    {
        $user = Auth::user();

        $group = Group::find($groupId);
        if (!$group) {
            throw new \Exception('Group not found.');
        }

        if ($group->user_id !== $user->id) {
            throw new \Exception('Unauthorized access. You are not the owner of this group.');
        }

        $addedUsers = [];
        $alreadyInGroup = [];

        foreach ($userIds as $userId) {
            if (Group_member::where('user_id', $userId)->where('group_id', $groupId)->exists()) {
                $alreadyInGroup[] = $userId;
            } else {

                Group_member::create([
                    'user_id' => $userId,
                    'group_id' => $groupId,
                ]);
                $addedUsers[] = $userId;
            }
        }
        $message = 'Users added to group successfully.';

        if (!empty($alreadyInGroup)) {
            $message .= ' The following users were already in the group: ' . implode(', ', $alreadyInGroup) . '.';
        }

        return response()->json(['message' => $message, 'added_users' => $addedUsers]);
    }
    public function removeUserFromGroup(Request $request, $groupId)
    {
        $user = Auth::user();
        $group = Group::find($groupId);
        if (!$group) {
            throw new \Exception('Group not found.');
        }
        if ($group->user_id !== $user->id) {
            throw new \Exception('Unauthorized access. You are not the owner of this group.');
        }
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user_id;
        $groupMember = Group_member::where('group_id', $groupId)->where('user_id', $userId)->first();
        if (!$groupMember) {
            throw new \Exception('User is not a member of this group.');
        }
        $groupMember->delete();

        return response()->json(['message' => 'User removed from group successfully.']);
    }
    public function getUsersInGroup($groupId)
    {
        $user = Auth::user();
        $group = Group::find($groupId);
        if (!$group) {
            throw new \Exception('Group not found.');
        }
        if ($group->user_id !== $user->id) {
            throw new \Exception('Unauthorized access. You are not the owner of this group.');
        }
        $members = Group_member::where('group_id', $groupId)
            ->with('user:id,full_name,user_name')
            ->get();

        $result = $members->map(function ($member) {
            return [
                'user_id' => $member->user_id,
                'group_id' => $member->group_id,
                'full_name' => $member->user->full_name,
                'user_name' => $member->user->user_name,
            ];
        });

        return response()->json($result);
    }
}

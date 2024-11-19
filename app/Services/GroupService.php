<?php


namespace App\Services;

use App\Models\Group;
use App\Models\Group_member;

use App\Models\User;
use App\Repositories\GroupRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupService
{

    protected $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }


    public function createGroup(array $data)
    {
        $user= $this->groupRepository->create($data);
        Log::channel('stack')->info('Group created successfully', [
            'user_id' => $user->id,
            'group_name'=>$user->name,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return $user;
    }      //log


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

        $response= response()->json(['message' => $message, 'added_users' => $addedUsers]);
        Log::channel('stack')->info('Users added to group successfully.', [
            'user_id' => $user->id,
            'group_id'=>$group->id,
            'group_name'=>$group->name,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return $response;
    }     //log


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

        $response= response()->json(['message' => 'User removed from group successfully.']);
        Log::channel('stack')->info('User removed from group successfully.', [
            'user_id' => $user->id,
            'group_id'=>$group->id,
            'group_name'=>$group->name,
            'userRemovedId'=>$groupMember->id,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return $response;
    }      //log


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

        Log::channel('stack')->info('show users in group', [
            'users_list'=> $result.' '.$group->name,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return response()->json(['data'=>$result]);

    }     //log

    public function getGroupCreatedByUser($user_id)
    {
        $owner = Group::where('user_id', $user_id)->get();
        $user = User::find($user_id);
        $userName = $user ? $user->user_name : 'Unknown User';


        Log::channel('stack')->info('show groups created by user', [
            'groupsCreatedByUser'=>' '.$owner.' ',
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return[ 'username'=>$userName,
            'group'=>$owner];

    }//log


    public function getGroupUserIn($userId)
    {
        $memberGroups = Group_member::where('user_id', $userId)->get();
        $user = User::find($userId);
        $userName = $user ? $user->user_name : 'Unknown User';

        Log::channel('stack')->info('show groups user in', [
            'userName' => $userName,
            'groupsUserIn'=>' '.$memberGroups.' ',
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);

        $groupDetails = $memberGroups->map(function ($group) use ($userName) {
            return [
                'id' => $group->id,
                'user_id' => $group->user_id,
                'userName' => $userName,
                'group_id' => $group->group_id,
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
            ];
        });


        return $groupDetails;
    }//log

    public function deleteGroupById($id)
    {
        $user = Auth::user();
        $group = Group::find($id);
        if (!$group) {
            throw new \Exception('Group not found.');
        }
        if ($group->user_id !== $user->id) {
            throw new \Exception('Unauthorized access. You are not the owner of this group.');
        }

        if ($group) {
            $group->group_member()->delete();
            $group->file_group()->delete();
            $group->request()->delete();
            return $group->delete();
        }
        return false;
    }


}

<?php
// app/Services/UserService.php
namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Userservice
{
    
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function signup(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return $this->userRepository->create($data);
    }

    public function login(array $credentials)
    {
        if (Auth::attempt($credentials)) {
            return Auth::user();
        }

        return null;
    }

    public function updateUser($id, array $data)
    {
        return $this->userRepository->update($id, $data);
    }

    public function deleteUser($id)
    {
        return $this->userRepository->delete($id);
    }

    public function getUserProfile()
    {
        return Auth::user();
    }
    public function logout($user)
    {
        $user->token()->revoke();
    }

    public function getAllUsers()
    {
        return User::select('id as user_id', 'user_name', 'full_name')->get();
    }

    public function searchUserByFullName($userName)
    {
        return User::select('id as user_id', 'user_name', 'full_name')
            ->where('user_name', 'like', '%' . $userName . '%')
            ->get();
    }

}

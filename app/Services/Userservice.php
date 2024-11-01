<?php
// app/Services/UserService.php
namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
        $user= $this->userRepository->create($data);
        Log::channel('stack')->info('user sign up', [
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'timestamp' => now(),

        ]);
        return $user;
    }   //log


    public function login(array $credentials)
    {
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            Log::channel('stack')->info('User logged in successfully', [
                'user_id' => $user->id,
                'user_name'=>$user->user_name,
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
            return $user;
        }

        Log::channel('stack')->warning('Failed login attempt', [
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return null;
    }    //log


    public function updateUser($id, array $data)         //log
    {
        Log::channel('stack')->info('user updated ',[
            'user_id'=>$id]);
        return  $this->userRepository->update($id, $data);;
    }


    public function deleteUser($id)       //log
    {
        Log::channel('stack')->info('user deleted ', [
            'user_id'=>$id]);
        return  $this->userRepository->delete($id);

    }


    public function getUserProfile()
    {
        $user= Auth::user();

        Log::channel('stack')->info('logging into profile page', [
            'user_id'=>$user->id,
            'user_name'=>$user->user_name,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
        return $user;
    }    //log


    public function logout($user)
    {
        $userLoggedOut=$user->token()->revoke();

        Log::channel('stack')->info(' User logged out successfully', [
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
       return $userLoggedOut;
    }   //log


    public function getAllUsers()
    {

        $users= User::select('id as user_id', 'user_name', 'full_name')->get();
        foreach ($users as $user) {
            $userIds= $user->user_id;
            Log::channel('stack')->info('show users', [
                'user_id'=>$userIds ,
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        }
        return $users;
    }    /////performance

    public function searchUserByFullName($userName)
    {
        $users= User::select('id as user_id', 'user_name', 'full_name')
            ->where('user_name', 'like', '%' . $userName . '%')
            ->get();

      foreach ($users as $user) {
        Log::channel('stack')->info('show user by user name', [
          'user_name' => $user->user_name,
          'ip_address' => request()->ip(),
          'timestamp' => now(),
        ]);
        }
        return $users;
    }   ///////performance

}

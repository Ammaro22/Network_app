<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignupRequest;
use App\Services\Userservice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(Userservice $userService)
    {
        $this->userService = $userService;
    }

    public function signup(SignupRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->signup($request->validated());
            $accessToken = $user->createToken('authToken')->accessToken;

            return response()->json([
                'message' => 'Successfully signed up',
                'data' => $user,
                'access_token' => $accessToken,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message_ar' => 'error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('user_name', 'password');

            $user = $this->userService->login($credentials);

            if (!$user) {
                return response()->json([
                    'message' => 'Incorrect Details. Please try again',
                ], 422);
            }

            $token = $user->createToken('Personal Access Token')->accessToken;

            return response()->json([
                'message' => 'Successfully logged in',
                'data' => $user,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message_ar' => 'error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function profile(): JsonResponse
    {
        try {
            $user_data = $this->userService->getUserProfile();
            return response()->json([
                "message" => "User data",
                "data" => $user_data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message_ar' => 'error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->userService->logout($request->user());
            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message_ar' => 'error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
                $currentUser = Auth::user();
                if ($currentUser->type_id !== 1) {
                    return response()->json([
                        'message' => 'Unauthorized. You do not have permission to delete users.',
                    ], 403);
                }
            $user = $this->userService->deleteUser($id);

            if ($user === null) {
                return response()->json([
                    'message' => 'User not found.',
                ], 404);
            }

            return response()->json([
                'message' => 'User deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }  //////edit has a problem in type_id

    public function index()
    {
        $users = $this->userService->getAllUsers();
        return response()->json($users);
    }


    public function search(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string|max:255',
        ]);
        $users = $this->userService->searchUserByFullName($request->user_name);
        return response()->json($users);
    }
}



<?php


use App\Http\Controllers\FileController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('signup',[UserController::class,'signup']);
Route::post('login', [UserController::class, 'login']);


Route::group(["middleware"=>["auth:api"]],function (){
    Route::post('logout',[UserController::class,'logout']);
    Route::get('profile',[UserController::class,'profile']);
    Route::delete('delete_user/{user_id}',[UserController::class,'destroy']);
});
/*عرض المستخدمين والبحث*/
Route::get('/get_all_users', [UserController::class, 'index']);
Route::get('/search_user', [UserController::class, 'search']);

/*الملفات*/

Route::post('upload_to_group', [FileController::class, 'uploadToGroup'])->middleware('auth:api');
Route::delete('delete_file', [FileController::class, 'deleteFiles'])->middleware('auth:api');
Route::get('get_files/{group_id}', [FileController::class, 'index']);

/* طلبات اضافة ملفات لمستخدمين ضمن مجموعتهم */
Route::get('get_all_requests/{group_id}', [RequestController::class, 'index'])->middleware('auth:api');
Route::post('accept_request/{request_id}', [RequestController::class, 'accept'])->middleware('auth:api');
Route::delete('reject_request/{request_id}', [RequestController::class, 'reject'])->middleware('auth:api');

/*المجموعات*/

Route::post('create_groups', [GroupController::class, 'createGroup'])->middleware('auth:api');
Route::post('add_users_for_group/{group_id}', [GroupController::class, 'addUsers'])->middleware('auth:api');
Route::delete('remove_users_from_group/{group_id}', [GroupController::class, 'removeUser'])->middleware('auth:api');
Route::get('get_users_in_group/{group_id}', [GroupController::class, 'getUsers'])->middleware('auth:api');
Route::get('get_my_groups', [GroupController::class, 'index'])->middleware('auth:api');

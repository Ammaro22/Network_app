<?php


use App\Http\Controllers\CheckController;
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
Route::post('login', [UserController::class, 'login'])->middleware('log');

Route::group(["middleware"=>["auth:api",'log']],function (){
    Route::post('logout',[UserController::class,'logout']);
    Route::get('profile',[UserController::class,'profile']);
    Route::delete('delete_user/{id}',[UserController::class,'destroy']);

});
/*عرض المستخدمين والبحث*/
Route::get('/get_all_users', [UserController::class, 'index'])->middleware('log');
Route::post('/search_user', [UserController::class, 'search'])->middleware('log');

/*الملفات*/

Route::post('upload_to_group', [FileController::class, 'uploadToGroup'])->middleware('auth:api','notify','log');
Route::delete('delete_file', [FileController::class, 'deleteFiles'])->middleware('auth:api','log');
Route::get('get_files/{group_id}', [FileController::class, 'index'])->middleware('log');

/* طلبات اضافة ملفات لمستخدمين ضمن مجموعتهم */
Route::get('get_all_requests', [RequestController::class, 'index'])->middleware('auth:api','log');
Route::post('accept_request/{request_id}', [RequestController::class, 'accept'])->middleware('auth:api','notify','log');
Route::delete('reject_request/{request_id}', [RequestController::class, 'reject'])->middleware('auth:api','notify','log');

/*المجموعات*/

Route::post('create_groups', [GroupController::class, 'createGroup'])->middleware('auth:api','notify','log');
Route::post('add_users_for_group/{group_id}', [GroupController::class, 'addUsers'])->middleware('auth:api','notify','log');
Route::delete('remove_users_from_group/{group_id}', [GroupController::class, 'removeUser'])->middleware('auth:api','notify','log');
Route::get('get_all_groups_for_user/{user_id}', [GroupController::class, 'getgroupforUser'])->middleware('log');
Route::get('get_user_in_group/{group_id}', [GroupController::class, 'getUsers'])->middleware('auth:api');
Route::get('get_GroupCreated_By_User', [GroupController::class, 'getGroup'])->middleware('auth:api','log');
Route::get('get_GroupUserIn', [GroupController::class, 'getGroupUserIn'])->middleware('auth:api');
Route::delete('delete_groups/{group_id}', [GroupController::class, 'destroy'])->middleware('auth:api');


/*check in && check out*/
Route::post('checkin', [CheckController::class, 'checkIn'])->middleware('auth:api','notify','log');
Route::post('checkout', [CheckController::class, 'checkOut'])->middleware('auth:api','notify','log');
Route::get('get_checks', [CheckController::class, 'getGroupChecks'])->middleware('auth:api');

/*تعديل الملف وحفط نسخة قديمة*/
Route::post('update_files/{id}', [FileController::class, 'updateFile'])->middleware('auth:api','notify','log');
/*عرض الاصدارات القديمة من اسم الملف*/
Route::post('get_old_version_from_file', [FileController::class, 'showSimilarFiles']);

/*عرض اختلاف ملف*/
Route::post('get_change_from_file', [FileController::class, 'showReportFiles']);
/*compare file*/
Route::post('/compare-files', [FileController::class, 'compareFiles']);
/*send notification*/
Route::post('/fcm_token', [UserController::class, 'storeFcmToken'])->middleware('auth:api');

Route::post('/backup_file', [FileController::class, 'someOtherFunction'])->middleware('auth:api');

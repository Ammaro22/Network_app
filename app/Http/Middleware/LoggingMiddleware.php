<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @throws \Exception
     */

    public function handle(Request $request, Closure $next): Response
    {

        $this->before($request);
        try {
            $response= $next($request);

        }catch (\Exception $e){
            $this->onException($e,$request);
            throw $e;
        }

        $this->after($request,$response);

        return $response;
    }


    protected function before(Request $request)
    {


        $user = Auth::user();
        $method = $request->route()->getActionMethod();
        $service = $request->route()->getAction('controller');



        $logData=$this->getLogData($request,$method,$user,$service);

        Log::info($logData['action'],[
            'method'=>$logData['method'],
            'user_id'=> $logData['user_id'],
            'request'=>$logData['request'],
            'timestamp'=>$logData['timestamp'],
        ]);

    }


    protected function getLogData(Request $request,$method,$user,$service)
    {
        $actionMessage='Before User Action';

        switch ($service){
            case 'App/Http/Controllers/UserController':
                $actionMessages=[
                    'signup'=> 'Successfully signed up',
                    'login'=> 'User logged in successfully',
                    'profile'=> 'logging into profile page',
                    'logout'=> 'User logged out successfully',
                    'index'=> 'show users',
                    'search'=>  'show user by user name'
                ];
                $actionMessage=$actionMessages[$method]??$actionMessage;
                break;

            case 'App/Http/Controllers/GroupController':
                $actionMessages=[
                    'createGroup'=>'Group created successfully',
                    'addUsers'=>'Users added to group successfully',
                    'removeUser'=>'User removed from group successfully',
                    'getUsers'=>' show users in group',
                    'getgroupforUser'=>'Show group for user',
                    'getGroup'=>'show groups created by user',
                    'getGroupUserIn'=>'show groups user in',
                ];
                $actionMessage=$actionMessages[$method]??$actionMessage;

                break;

            case 'App/Http/Controllers/RequestController':
                $actionMessages=[
                    'accept'=>'accept request',
                    'reject'=>'reject request',

                ];
                $actionMessage=$actionMessages[$method]??$actionMessage;

                break;

            case 'App/Http/Controllers/CheckController':
                $actionMessages=[
                    'checkIn'=>'reserved file',
                    'checkOut'=>'release file',

                ];
                $actionMessage=$actionMessages[$method]??$actionMessage;

                break;
            default;
        }

        return[
            'user_id'=>$user?$user->id:'unKnown',
            'action'=>$actionMessage,
            'method'=>$method,
            'request'=>$request->except('password'),
            'timestamp'=>now(),
        ];

    }

    protected function after(Request $request,Response $response)
    {
        Log::info('After User Action',[
            'status'=>$response->getStatusCode(),
            'timestamp'=>now(),
        ]);

    }



    protected function onException(\Exception $exception,Request $request)
    {
        Log::error('Exception Message',[
            'message'=>$exception->getMessage(),
            'timestamp'=>now(),
        ]);

    }
}

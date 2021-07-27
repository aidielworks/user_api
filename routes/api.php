<?php

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Imports\UsersImport;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return request()user();
// });



//ignore this comment it just a test
//another comment to ignore 

//Login to get token 

Route::post('/login', function () {
    $login = request()->validate([
        'email' => 'required|string',
        'password' => 'required|string'
    ]);

    if (!Auth::attempt($login)) {
        return response(['message' => 'Invalid credential']);
    }

    $access_token = Auth::user()->createToken('authToken')->accessToken;

    return response(['user' => Auth::user(), compact('access_token')]);
});


Route::middleware('auth:api')->group(function () {

    //Show All User
    Route::get('/user', function () {

        if (request()->has('name')) {

            $name = request('name');
            $users = User::where('name', 'like', "%{$name}%")
                ->paginate(3);
        } else {

            $users = User::paginate(3);
        }

        return new UserCollection($users);
    });



    //Create user
    Route::post('/user', function () {
        request()->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        return User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password'))
        ]);
    });



    //Show specific user
    Route::get('/user/{user}', function (User $user) {

        return new UserResource($user);
    });



    //Update User
    Route::patch('/user/{user}', function (User $user) {
        request()->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        $success = $user->update([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password'))
        ]);

        return [
            'success' => $success
        ];
    });



    //Delete User
    Route::delete('/user/{user}', function (User $user) {
        $success = $user->delete();

        return [
            'success' => $success
        ];
    });





    //Create user via excel file
    Route::post('/user/excel/create', function (Request $request) {

        $validator = Validator::make($request->all(), [
            'file' => 'mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return [
                'message' => 'This is not excel file',
            ];
        }

        $file = $request->file('file');

        $import = new UsersImport;

        $import->import($file);

        if ($import->errors() != null && $import->failures() != null) {
            return [
                'message' => 'Certain file not insert into database',
                'error' => $import->errors(),
                'failure' => $import->failures()
            ];
        } else {
            return [
                'message' => 'File uploaded. User created in database',
            ];
        }
    });



    //update user via excel file
    Route::post('/user/excel/update', function (Request $request) {

        $validator = Validator::make($request->all(), [
            'file' => 'mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return [
                'message' => 'This is not excel file',
            ];
        }

        $file = $request->file('file');

        $users = Excel::toCollection(new UsersImport, $file);

        foreach ($users[0] as $user) {

            if (User::where('email', $user['email'])->exists()) {

                $dbuser = User::where('email', $user['email'])->get();

                if ($user['name'] != $dbuser[0]['name']) {

                    User::where('id',  $dbuser[0]['id'])
                        ->update([
                            'name' => $user['name']
                        ]);
                }
            }
        }
        return ['message' => 'File uploaded. Database updated.'];
    });



    //delete user via excel file
    Route::post('/user/excel/delete', function (Request $request) {

        $validator = Validator::make($request->all(), [
            'file' => 'mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return [
                'message' => 'This is not excel file',
            ];
        }

        $file = $request->file('file');

        $users = Excel::toCollection(new UsersImport, $file);

        foreach ($users[0] as $user) {

            if (User::where('email', $user['email'])->exists()) {

                User::where('email', $user['email'])->delete();
            }
        }

        return ['message' => 'File uploaded. Users deleted.'];
    });
});

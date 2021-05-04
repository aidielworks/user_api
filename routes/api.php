<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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

//Show All User
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
        return User::all();
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
});

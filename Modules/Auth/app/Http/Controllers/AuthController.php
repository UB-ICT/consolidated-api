<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Exception;
<<<<<<< HEAD
=======

>>>>>>> 91352151d70849ccb964628d31a63f99ac23d8ba
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        try {
            $tempDeviceName = 'Temp Device Name';

            $credentials = [
                'samaccountname' => $fields['username'],
                'password' => $fields['password'],
            ];

<<<<<<< HEAD
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
=======
            

            if (Auth::validate($credentials)) {
                $user = Auth::getLastAttempted();
>>>>>>> 91352151d70849ccb964628d31a63f99ac23d8ba

                $token = $user->createToken($tempDeviceName)->plainTextToken;

                $response = [
                    'success' => true,
                    'message' => "Authenticated Successfully.",
                    'data' => [
                        'token' => $token,
                        'name' => $user->name,
                        'users' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ]
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => "The provided credentials are incorrect.",
                    'data' => null,
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }
}

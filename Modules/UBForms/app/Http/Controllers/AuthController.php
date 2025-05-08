<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User as LaravelUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Configuration\Exceptions;
use LdapRecord\Container;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use LdapRecord\Query\Exceptions\ModelNotFoundException;
use LdapRecord\Laravel\Facades\Ldap;

/*
This is the Authentication Controller responsible for authenticating the user with LDAP. 

The function authenticateUser() takes in the users, username and password and uses 
the Auth::validate() function to validate the credentials, then it checks if the 
user already exists in the application's database. If not, then it creates the user 
in the database because sanctum needs the user inorder to create the token.

Author: SW

*/

class AuthController extends Controller
{
    public function authenticateUser(Request $request){
        $fields = $request->validate([
            'username' => 'required',      
            'password' => 'required',      
        ]);
    
        try{
            $tempDeviceName = 'Temp Device Name';
    
            $credentials = [
                'samaccountname' => $fields['username'],
                'password' => $fields['password'],
            ];

    
            if (Auth::validate($credentials)) {
              $user = Auth::getLastAttempted();

                // Save Eamil
                // $user->email = 
    
                $token = $user->createToken($tempDeviceName)->plainTextToken;
    
                $response = [
                    'success' => true,
                    'message' => "Authenticated Successfully.",
                    'data' => [                                
                      'token' => $token,
                      'name' => $user->name
                    ]                    
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => "The provided credentials are incorrect.",
                    'data' => null                    
                ];                
            }
    
        }catch(Exception $e){
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null                    
            ];               
        }
    
        return response($response, 200);
    }
}

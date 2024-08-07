<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * @group Authorization and user management
 *
 */
class userController extends Controller
{
    /**
     * Create a user
     *
     * Enpoint to create a api admin user
     * 
     * @bodyParam name string required
     * The username
     *
     * @bodyParam email email required
     * The users email
     * 
     * @bodyParam password string required
     * The admins user's 
     * 
     * @response 200 {
     *  "name": "Username",
     *  "email": "Email"
     * }
     * 
     * @response 400 {
     *  "message": "Invalid request"
     * }
     * 
     * @response 409 {
     *  "message": "User email already exist"
     * }
     */
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if($validator->fails()) {
            $message =  [
                'message' => 'Invalid request',
                'errors' => $validator->errors()
            ];
            return response()->json($message, 400);
        }

        if(User::where('email', $request->email)->first()) {
            $message =  [
                'message' => 'User email already exist'
            ];
            return response()->json($message, 409);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if(!$user) {
            $message =  [
                'message' => 'Error creating user'
            ];
            return response()->json($message, 500);
        }

        $message = ['user' => $user];
        return response()->json($message, 201);
    }

    /**
     * Create authorize token
     *
     * Enpoint to create a BearerAuth token
     * 
     * @bodyParam email email required
     * The users email
     * 
     * @bodyParam password string required
     * The admins user's password
     * @response 200 {
     *  "token": "BearerAuthToken"
     * }
     * @response 403 {
     *  "message": "Unauthorized"
     * }
     * @response 404 {
     *  "message": "User not found"
     * }
     */
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if($validator->fails()) {
            $message =  [
                'message' => 'Invalid credenials',
                'errors' => $validator->errors()
            ];
            return response()->json($message, 403);
        }

        $user = User::where('email', $request->email)->first();

        if(!$user) {
            $message =  [
                'message' => 'User not found',
                'status' => 404
            ];
            return response()->json($message, 404);
        }

        if(Hash::check($request->password, $user->password)) {
            $token = $user->createToken("token_admin");
            $message =  [
                'token' => $token->plainTextToken
            ];
            return response()->json($message, 200);
        }

        $message =  [
            'message' => 'Unauthorized',
            'status' => 403
        ];
        return response()->json($message, 403);

    }
}

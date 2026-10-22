<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Enums\UserRole;
use App\Models\User;

class AuthController extends Controller
{
    

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = new User([
            'name' => $validated['name'], 
            'email' => $validated['email'], 
            'phone' => $validated['phone'] ?? null, 
            'password' => $validated['password'], 
            'gender' => $validated['gender'] ?? null, 
            'date_of_birth' => $validated['date_of_birth'] ?? null, 
        ]);
        
        $user->role = UserRole::PATIENT;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken; 

        return response()->json([ 
            'message' => 'Registration successful.', 
            'user' => new UserResource($user),
            'token' => $token, 
            'token_type' => 'Bearer', 
        ], 201);

    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ], 200);
    }

}

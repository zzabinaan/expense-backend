<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    private const DEFAULT_CATEGORIES = [
        ['name' => 'Food', 'color' => '#f97316'],
        ['name' => 'Transport', 'color' => '#3b82f6'],
        ['name' => 'Shopping', 'color' => '#a855f7'],
        ['name' => 'Bills', 'color' => '#ef4444'],
        ['name' => 'Entertainment', 'color' => '#f59e0b'],
        ['name' => 'Health', 'color' => '#22c55e'],
        ['name' => 'Other', 'color' => '#6b7280'],
    ];

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        foreach (self::DEFAULT_CATEGORIES as $category) {
            Category::create([
                'user_id' => $user->id,
                'name' => $category['name'],
                'color' => $category['color'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->created(['user' => $user, 'token' => $token], 'Registration successful.');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->ok(['user' => $user, 'token' => $token], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->message('Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok($request->user());
    }
}

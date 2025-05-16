<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json(['user' => $user]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($request->user()->id),
            ],
            'avatar' => ['sometimes', 'image', 'max:1024'],
            'preferences' => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userService->updateProfile($request->user(), $request->all());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->userService->updatePassword(
            $request->user(),
            $request->current_password,
            $request->password
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function updatePreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'preferences' => ['required', 'array'],
            'preferences.notifications' => ['sometimes', 'array'],
            'preferences.theme' => ['sometimes', 'string', 'in:light,dark'],
            'preferences.language' => ['sometimes', 'string', 'size:2'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->userService->updatePreferences($request->user(), $request->preferences);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $user->preferences
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->userService->deleteAccount($request->user(), $request->password);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => 'Account deleted successfully']);
    }
} 
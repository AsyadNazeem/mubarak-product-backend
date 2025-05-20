<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Get list of all admin users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdmins(Request $request)
    {
        // Get current user
        $currentUser = $request->user();

        // Get all users with admin or superadmin role
        $admins = User::whereIn('role', ['admin', 'superadmin'])->get();

        // If current user is a superadmin, include password info
        if ($currentUser->role === 'superadmin') {
            // We need to transform the collection to include raw passwords
            // This is just for display purposes - in a real app you'd likely have
            // a different approach for password management
            $admins = $admins->map(function ($admin) {
                // This is a placeholder - in a real app, you wouldn't store or retrieve
                // actual passwords this way for security reasons
                $admin->password_raw = '********'; // Placeholder for actual implementation
                return $admin;
            });
        }

        return response()->json([
            'success' => true,
            'users' => $admins
        ]);
    }

    /**
     * Create a new admin user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Check if current user has superadmin privileges
        $currentUser = $request->user();
        if ($currentUser->role !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only superadmins can create new admin users.'
            ], 403);
        }

        // Validate request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,superadmin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create new admin user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin user created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Get a specific admin user
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Only return users with admin roles
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Update an admin user
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Get current user
        $currentUser = $request->user();

        // Find the user to update
        $user = User::findOrFail($id);

        // Check if current user has permission to update
        if ($currentUser->role !== 'superadmin' && $currentUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only update your own account unless you are a superadmin.'
            ], 403);
        }

        // Validate request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|required|in:admin,superadmin',
            'password' => 'sometimes|nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user fields
        $user->name = $request->name;
        $user->email = $request->email;

        // Only superadmins can change roles
        if ($currentUser->role === 'superadmin' && $request->has('role')) {
            $user->role = $request->role;
        }

        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Admin user updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete an admin user
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        // Only superadmins can delete users
        $currentUser = $request->user();
        if ($currentUser->role !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only superadmins can delete admin users.'
            ], 403);
        }

        // Prevent self-deletion
        if ($currentUser->id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete yourself'
            ], 400);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin user deleted successfully'
        ]);
    }
}

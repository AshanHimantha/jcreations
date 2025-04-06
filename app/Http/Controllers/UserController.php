<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Define available user roles
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_STAFF = 'staff';
    const ROLE_CASHIER = 'cashier';

    /**
     * Get all available roles
     */
    public static function getAvailableRoles()
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_CASHIER
        ];
    }

    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Show form for creating a new user.
     */
    public function create()
    {
        $availableRoles = self::getAvailableRoles();
        return view('users.create', compact('availableRoles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'roles' => 'array'
            ]);
            
            // Validate roles if provided
            if (isset($validated['roles'])) {
                $this->validateRoles($validated['roles']);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'roles' => $validated['roles'] ?? []
            ]);

            return response()->json($user, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Show form for editing user.
     */
    public function edit(User $user)
    {
        $availableRoles = self::getAvailableRoles();
        return view('users.edit', compact('user', 'availableRoles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        try {
            // Only validate fields that are present in the request
            $rules = [];
            
            if ($request->has('name')) {
                $rules['name'] = 'required|string|max:255';
            }
            
            if ($request->has('email')) {
                $rules['email'] = 'required|string|email|max:255|unique:users,email,'.$user->id;
            }
            
            if ($request->has('password')) {
                $rules['password'] = 'required|string|min:8|confirmed';
            }
            
            if ($request->has('roles')) {
                $rules['roles'] = 'array';
            }
            
            $validated = $request->validate($rules);
            
            // Update only the fields that were provided
            foreach ($validated as $key => $value) {
                if ($key === 'password') {
                    $user->password = Hash::make($value);
                } else if ($key === 'roles') {
                    $this->validateRoles($value);
                    $user->roles = $value;
                } else {
                    $user->$key = $value;
                }
            }
            
            $user->save();
            
            return response()->json($user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }


    protected function validateRoles(array $roles)
    {
        $validRoles = self::getAvailableRoles();
        
        foreach ($roles as $role) {
            if (!in_array($role, $validRoles)) {
                abort(422, "Invalid role: {$role}");
            }
        }
    }
}
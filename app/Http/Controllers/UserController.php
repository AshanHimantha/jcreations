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
        return view('users.index', compact('users'));
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
        ]);

        // Assign roles if provided
        if (isset($validated['roles'])) {
            $this->assignRoles($user, $validated['roles']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array'
        ]);

        // Validate roles if provided
        if (isset($validated['roles'])) {
            $this->validateRoles($validated['roles']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();
        
        // Update roles if provided
        if (isset($validated['roles'])) {
            $this->syncRoles($user, $validated['roles']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Validate that roles are in the allowed list.
     */
    protected function validateRoles(array $roles)
    {
        $validRoles = self::getAvailableRoles();
        
        foreach ($roles as $role) {
            if (!in_array($role, $validRoles)) {
                abort(422, "Invalid role: {$role}");
            }
        }
    }

    /**
     * Assign roles to a user.
     */
    public function assignRoles(User $user, array $roles)
    {
        $this->validateRoles($roles);
        $user->roles = array_merge($user->roles ?? [], $roles);
        $user->roles = array_unique($user->roles);
        $user->save();
        return back()->with('success', 'Roles assigned successfully.');
    }

    /**
     * Remove roles from a user.
     */
    public function removeRoles(User $user, array $roles)
    {
        $this->validateRoles($roles);
        $user->roles = array_diff($user->roles ?? [], $roles);
        $user->save();
        return back()->with('success', 'Roles removed successfully.');
    }

    /**
     * Sync user roles with provided array.
     */
    protected function syncRoles(User $user, array $roles)
    {
        $this->validateRoles($roles);
        $user->roles = $roles;
        $user->save();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(User $user, string $role)
    {
        return in_array($role, $user->roles ?? []);
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(User $user, array $roles)
    {
        return !empty(array_intersect($roles, $user->roles ?? []));
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(User $user)
    {
        return $this->hasRole($user, self::ROLE_ADMIN);
    }

    /**
     * Check if user is a staff member
     */
    public function isStaff(User $user)
    {
        return $this->hasRole($user, self::ROLE_STAFF);
    }

    /**
     * Check if user is a cashier
     */
    public function isCashier(User $user)
    {
        return $this->hasRole($user, self::ROLE_CASHIER);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserAdminController extends Controller
{
    private const ADMIN_EMAIL = 'kristo@tactica.is';

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || $user->email !== self::ADMIN_EMAIL) {
            abort(403, 'Only admin may manage users.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $users = User::orderBy('email')->get();
        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created');
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        if ($user->email === self::ADMIN_EMAIL) {
            return redirect()->route('admin.users.index')->withErrors(['email' => 'Cannot delete admin user.']);
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('status', 'User deleted');
    }
}


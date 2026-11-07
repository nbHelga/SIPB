<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // USER (unchanged)
    public function user(Request $request)//ubah jadi public function index(...)
    {
        $query = User::orderBy('status')->orderBy('id');

        if ($request->search) {
            $query->where('username', 'like', '%'.$request->search.'%');
        }

        if ($request->filter && isset($request->filter['status'])) {
            $query->where('status', $request->filter['status']);
        }

        $users = $query->paginate(20);

        return view('admin.user', compact('users'));
    }

    public function userJson($id)
    {
        $user = User::findOrFail($id);
        $user->makeHidden('password');
        return response()->json($user);
    }

    public function userStore(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|alpha_dash|min:4|max:30|unique:users,username',
            'password' => 'required|string|min:6|max:64',
            'status'   => 'required|in:admin,kasir',
        ], [
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan underscore.',
            'username.unique'     => 'Username sudah digunakan.',
            'password.min'        => 'Password minimal terdiri dari 6 karakter.',
        ]);

        User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.user')->with('success', 'User berhasil ditambahkan!');
    }

    public function userUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'required|string|min:4|max:30|unique:users,username,'.$id,
            'password' => 'nullable|string|min:6|max:64',
            'status' => 'required|in:kasir,admin',
        ]);

        $data = [
            'username' => $validated['username'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.user')->with('success', 'User berhasil diupdate!');
    }

    public function userDelete($id)
    {
        if ($id == Auth::id()) {
            return back()->withErrors(['Tidak dapat menghapus akun sendiri.']);
        }
        User::findOrFail($id)->delete();
        return redirect()->route('admin.user')->with('success', 'User berhasil dihapus!');
    }
}

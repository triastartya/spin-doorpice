<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $users = User::when($q, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:'.$this->allowedRoles($request)],
        ]);

        User::create($data);

        return back()->with('success', 'User ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeManage($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:'.$this->allowedRoles($request)],
        ]);

        if (! ($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'User diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeManage($request, $user);

        if ($user->id === $request->user()->id) {
            return back()->withErrors('Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'User dihapus.');
    }

    protected function allowedRoles(Request $request): string
    {
        return $request->user()->role === 'admin' ? 'admin,operator,operator_hadiah' : 'operator,operator_hadiah';
    }

    protected function authorizeManage(Request $request, User $user): void
    {
        if ($request->user()->role !== 'admin' && $user->role === 'admin') {
            abort(403);
        }
    }
}

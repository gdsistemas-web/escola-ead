<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()->hasAnyRole(['administrador', 'professor']), 403);

        $query = User::query()->with('roles', 'profile')->orderBy('name');

        if ($role = request('role')) {
            $query->role($role);
        }

        return $query->paginate(50);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('administrador'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:administrador,professor,aluno'],
        ]);

        $user = User::create($data);
        $user->assignRole($data['role']);

        return $user->load('roles');
    }

    public function show(User $user)
    {
        abort_unless(
            request()->user()->hasRole('administrador')
                || request()->user()->id === $user->id
                || (request()->user()->hasRole('professor') && $user->enrollments()->whereHas('course', fn ($query) => $query->where('teacher_id', request()->user()->id))->exists()),
            403,
        );

        return $user->load('roles', 'profile', 'enrollments.course', 'taughtCourses');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->hasRole('administrador') || $request->user()->id === $user->id, 403);

        $user->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
        ]));

        return $user;
    }

    public function destroy(User $user)
    {
        abort_unless(request()->user()->hasRole('administrador'), 403);

        $user->delete();

        return response()->noContent();
    }
}

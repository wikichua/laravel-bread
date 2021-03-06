<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('browse-users');
        if($request->ajax()){
            $users = User::latest();
            return datatables($users)->addColumn('action', function ($users) {
                if (auth()->user()->can('read-users')) {
                    $act[] = '<a href="'.route('users.show',$users->id).'" title="View User" class="btn btn-info btn-sm"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                }
                if (auth()->user()->can('edit-users')) {
                    $act[] = '<a href="'.route('users.edit',$users->id).'" title="Edit User" class="btn btn-primary btn-sm"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>';
                }
                if (auth()->user()->can('delete-users')) {
                    $act[] = '<a href="'.route('users.destroy', $users->id).'" title="Delete User" class="deleteBtn btn btn-danger btn-sm"><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                }
                return implode("\n", $act);
            })
            ->toJson();
        }
        return view('admin.users.index');
    }
    public function create()
    {
        $this->authorize('add-users');
        $roles = Role::select('id', 'name', 'label')->get();
        $roles = $roles->pluck('label', 'name');

        return view('admin.users.create', compact('roles'));
    }
    public function store(Request $request)
    {
        $this->authorize('add-users');
        $this->validate(
            $request,
            [
                'name' => 'required',
                'email' => 'required|string|max:255|email|unique:users',
                'password' => 'required',
                'roles' => 'required'
            ]
        );

        $data = $request->except('password');
        $data['password'] = bcrypt($request->password);
        $user = User::create($data);

        foreach ($request->roles as $role) {
            $user->assignRole($role);
        }

        return redirect('admin/users')->with('flash_message', 'User added!');
    }
    public function show($id)
    {
        $this->authorize('read-users');
        $user = User::findOrFail($id);

        return view('admin.users.show', compact('user'));
    }
    public function edit($id)
    {
        $this->authorize('edit-users');
        $roles = Role::select('id', 'name', 'label')->get();
        $roles = $roles->pluck('label', 'name');

        $user = User::with('roles')->select('id', 'name', 'email')->findOrFail($id);
        $user_roles = [];
        foreach ($user->roles as $role) {
            $user_roles[] = $role->name;
        }

        return view('admin.users.edit', compact('user', 'roles', 'user_roles'));
    }
    public function update(Request $request, $id)
    {
        $this->authorize('edit-users');
        $this->validate(
            $request,
            [
                'name' => 'required',
                'email' => 'required|string|max:255|email|unique:users,email,' . $id,
                'roles' => 'required'
            ]
        );

        $data = $request->except('password');
        if ($request->has('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user = User::findOrFail($id);
        $user->update($data);

        $user->roles()->detach();
        foreach ($request->roles as $role) {
            $user->assignRole($role);
        }

        return redirect('admin/users')->with('flash_message', 'User updated!');
    }
    public function destroy($id)
    {
        $this->authorize('delete-users');
        User::destroy($id);

        return ['flash_message' => 'User deleted!'];
    }
}

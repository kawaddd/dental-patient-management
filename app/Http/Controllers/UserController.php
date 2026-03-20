<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users',
            'password'              => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'         => '名前は必須です',
            'email.required'        => 'メールアドレスは必須です',
            'email.email'           => 'メールアドレスの形式が正しくありません',
            'email.unique'          => 'このメールアドレスは既に使用されています',
            'password.required'     => 'パスワードは必須です',
            'password.confirmed'    => 'パスワードが一致しません',
            'password.min'          => 'パスワードは8文字以上で設定してください',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => false,
        ]);

        return redirect()->route('users.index')->with('success', 'ユーザーを追加しました');
    }

    public function edit(User $user)
    {
        if ($user->id !== auth()->id()) {
            return redirect()->route('users.index')->with('error', '自分のアカウントのみ編集できます');
        }
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->id !== auth()->id()) {
            return redirect()->route('users.index')->with('error', '自分のアカウントのみ編集できます');
        }
        $rules = [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Password::min(8)];
        }

        $validated = $request->validate($rules, [
            'name.required'      => '名前は必須です',
            'email.required'     => 'メールアドレスは必須です',
            'email.email'        => 'メールアドレスの形式が正しくありません',
            'email.unique'       => 'このメールアドレスは既に使用されています',
            'password.confirmed' => 'パスワードが一致しません',
            'password.min'       => 'パスワードは8文字以上で設定してください',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return redirect()->route('users.index')->with('success', 'ユーザー情報を更新しました');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->is_admin) {
            return back()->with('error', 'ユーザーの削除は管理者のみ実行できます');
        }
        if ($user->id === auth()->id()) {
            return back()->with('error', '自分自身のアカウントは削除できません');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'ユーザーを削除しました');
    }
}

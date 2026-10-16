<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        // ログイン中のユーザーを取得
        $user = $request->user();

        // バリデーション済みの入力値だけを取得
        $data = $request->validated();

        // 名前とメールアドレスを更新
        $user->name = $data['name'];
        $user->email = $data['email'];

        // 新しいパスワードが指定された場合だけ更新
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        // 更新後のユーザー情報を返す
        return response()->json([
            'message' => 'プロフィールを更新しました。',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

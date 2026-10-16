<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * __construct
     */
    public function __construct(
        protected User $user,
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $users = $this->user->getUserList($search);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->user->createUser($data);

        if ($request->boolean('admin')) {
            $user->assignRole('admin');
        } else {
            $user->assignRole('employee');
        }

        return response()->json([
            'message' => 'ユーザーを登録しました。',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json([
            'message' => '未実装です。',
        ], 501);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $admin = $data['admin'] ?? null;

        unset($data['admin']);

        if ($admin === false) {
            $error = $this->validateAdminRoleChange($request, $user);

            if ($error !== null) {
                return $error;
            }
        }

        $user->updateUser($data);

        if ($admin !== null) {
            $user->syncRoles([$admin ? 'admin' : 'employee']);
        }

        return response()->json([
            'message' => 'ユーザー情報を更新しました。',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'ユーザーを削除しました。',
        ]);
    }


    /**
     * 管理者権限の安全対策
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse|null
     */
    private function validateAdminRoleChange(
        UpdateUserRequest $request,
        User $user
    ): ?JsonResponse {
        // 自分自身の管理者権限は外せない
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => '自分自身の管理者権限は変更できません。',
            ], 422);
        }

        return null;
    }

}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ユーザー一覧を取得する
     *
     * @return LengthAwarePaginator
     */
    public function getUserList(): LengthAwarePaginator
    {
        return self::query()
            ->select([
                'id',
                'name',
                'email',
                'created_at',
            ])
            ->with('roles:id,name') //Eager Loading
            ->orderBy('id')
            ->orderBy('id')
            ->paginate(20);
    }

    /**
     * ユーザーを作成する
     *
     * @param array $data
     * @return self
     */
    public function createUser(array $data): self
    {
        return self::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);
    }

    /**
     * ユーザー情報を更新する
     *
     * @param array $data
     * @return boolean
     */
    public function updateUser(array $data): bool
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make(
                $data['password']
            );
        }

        return $this->fill($updateData)->save();
    }
}

<?php

namespace App\Actions\User;

use App\Actions\Tenant\CreateTenantWorkspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnsureSystemAdminUser
{
    public function __construct(
        private readonly CreateTenantWorkspace $tenantWorkspace,
    ) {}

    /**
     * @return array{user: User, created: bool, generated_password: ?string}
     */
    public function handle(string $email, ?string $name = null, ?string $password = null): array
    {
        return DB::transaction(function () use ($email, $name, $password) {
            $user = User::query()->where('email', $email)->first();
            $created = false;
            $generatedPassword = null;
            $resolvedPassword = $password;

            if ($user === null) {
                $created = true;
                $generatedPassword = $resolvedPassword ?: Str::password(20);

                $user = User::create([
                    'name' => $name ?: $this->nameFromEmail($email),
                    'email' => $email,
                    'password' => Hash::make($generatedPassword),
                ]);

                $user->forceFill([
                    'email_verified_at' => now(),
                    'is_system_admin' => true,
                ])->save();
            } else {
                $attributes = [
                    'name' => $user->name ?: ($name ?: $this->nameFromEmail($email)),
                    'email_verified_at' => $user->email_verified_at ?: now(),
                    'is_system_admin' => true,
                ];

                if ($resolvedPassword !== null && $resolvedPassword !== '') {
                    $attributes['password'] = Hash::make($resolvedPassword);
                }

                $user->forceFill($attributes)->save();
            }

            $this->tenantWorkspace->handle($user, 'Workspace Amministrazione Sistema');

            return [
                'user' => $user->fresh(),
                'created' => $created,
                'generated_password' => $generatedPassword,
            ];
        });
    }

    private function nameFromEmail(string $email): string
    {
        $localPart = Str::of($email)->before('@')->replace(['.', '_', '-'], ' ');

        return Str::title((string) $localPart);
    }
}

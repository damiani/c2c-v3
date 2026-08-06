<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\DefaultTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private DefaultTenant $defaultTenant) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['locale'] ??= config('app.locale');

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'locale' => $input['locale'] ?? config('app.locale'),
                'password' => $input['password'],
            ]);

            $user->tenantMemberships()->create([
                'tenant_id' => $this->defaultTenant->findOrCreate()->id,
                'role' => TenantMembership::ROLE_MEMBER,
            ]);

            return $user;
        });
    }
}

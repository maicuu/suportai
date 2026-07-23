<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        // Cada novo cadastro cria seu próprio tenant (organização) e o
        // usuário nasce vinculado a ele. tenant_id é setado pela RELAÇÃO
        // ($tenant->users()->create), nunca por input do request — regra
        // de ouro do isolamento multi-tenant. DB::transaction garante que
        // tenant e usuário sejam criados juntos (tudo ou nada).
        return DB::transaction(function () use ($input): User {
            $tenant = Tenant::create([
                'name' => $input['name'],
                'slug' => Str::slug($input['name']).'-'.Str::lower(Str::random(6)),
            ]);

            return $tenant->users()->create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);
        });
    }
}

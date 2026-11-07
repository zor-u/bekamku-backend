<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterUser
{
    /**
     * @var array<string, string>
     */
    private const DUPLICATE_MESSAGES = [
        'email' => 'This email address is already registered.',
        'phone' => 'The phone has already been taken.',
    ];

    /**
     * Create the account and its first access token as one unit of work.
     *
     * @param  array{name: string, email: string, phone: string, password: string}  $data
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function handle(array $data): array
    {
        try {
            return DB::transaction(function () use ($data): array {
                $user = User::create($data);

                return [
                    'user' => $user,
                    'token' => $user->createToken('register')->plainTextToken,
                ];
            });
        } catch (UniqueConstraintViolationException $exception) {
            $field = $this->duplicateField($exception);

            throw ValidationException::withMessages([
                $field => [self::DUPLICATE_MESSAGES[$field]],
            ]);
        }
    }

    /**
     * Work out which unique column the database rejected.
     */
    private function duplicateField(UniqueConstraintViolationException $exception): string
    {
        if (in_array('email', $exception->columns, true)) {
            return 'email';
        }

        if (in_array('phone', $exception->columns, true)) {
            return 'phone';
        }

        return Str::contains((string) $exception->index, 'phone') ? 'phone' : 'email';
    }
}

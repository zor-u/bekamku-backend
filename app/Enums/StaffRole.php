<?php

namespace App\Enums;

enum StaffRole: string
{
    case Admin = 'admin';
    case Therapist = 'therapist';

    /**
     * @return array<string, string>
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Therapist => 'Therapist',
        };
    }
}

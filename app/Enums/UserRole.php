<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Editor => 'Editor',
        };
    }

    /**
     * Every current role is staff. Kept explicit so a future non-staff role
     * (e.g. a client portal) is locked out of /admin by default.
     */
    public function canAccessAdmin(): bool
    {
        return match ($this) {
            self::Admin, self::Editor => true,
        };
    }

    /**
     * Admins additionally manage users, settings, redirects and the 404 log;
     * editors handle content, media and leads.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}

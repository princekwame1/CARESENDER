<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const MEMBER = 'member';

    public const ADMIN = 'admin';

    /** Team members who keep facilities and specialists up to date and answer messages, without access to money or settings. */
    public const SECRETARIAT = 'secretariat';

    /** Team roles, as shown on /admin/team. */
    public const STAFF_ROLES = [
        self::ADMIN => 'Administrator',
        self::SECRETARIAT => 'Secretariat',
    ];

    protected $attributes = ['role' => self::MEMBER, 'username' => null, 'avatar_path' => null];

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

    /** Public URL of the profile photo, or null to show initials. */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? '/storage/'.$this->avatar_path : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    public function isSecretariat(): bool
    {
        return $this->role === self::SECRETARIAT;
    }

    /** Admins and the secretariat: they use the team dashboard and see everything on the site for free. */
    public function isStaff(): bool
    {
        return array_key_exists($this->role, self::STAFF_ROLES);
    }

    /** "Administrator", "Secretariat". */
    public function roleLabel(): string
    {
        return self::STAFF_ROLES[$this->role] ?? 'Member';
    }

    public function scopeStaff(Builder $query): void
    {
        $query->whereIn('role', array_keys(self::STAFF_ROLES));
    }

    public function searches(): HasMany
    {
        return $this->hasMany(SearchLog::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}

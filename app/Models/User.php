<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\RH\Employee;
use App\Models\RH\Interview;
use App\Models\Tiers\Contact;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_admin', 'is_employee', 'is_tiers', 'email_verified_at', 'access_atelier', 'access_technique'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, HasRoles, Notifiable, TwoFactorAuthenticatable;

    public function canAccessPanel(Panel $panel): bool
    {
        // Les administrateurs ont accès à tout
        if ($this->is_admin) {
            return true;
        }

        // Le panel technique est réservé aux techniciens ayant l'accès
        if ($panel->getId() === 'technicien' && $this->access_technique) {
            return true;
        }

        // Les salariés ont accès à leurs espaces
        if ($this->is_employee) {
            return in_array($panel->getId(), ['salarie', 'terrain', 'chantier']);
        }

        // Les tiers ont accès à leurs espaces
        if ($this->is_tiers) {
            return in_array($panel->getId(), ['customer', 'sous-traitant', 'tiers']);
        }

        return false;
    }

    public function getEmployeeIdOrFail(): int
    {
        $employeeId = Employee::where('user_id', $this->id)->value('id');
        if (! $employeeId) {
            throw new \Exception('Aucun employé lié à votre compte.');
        }

        return (int) $employeeId;
    }

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
            'is_admin' => 'boolean',
            'is_employee' => 'boolean',
            'is_tiers' => 'boolean',
            'access_atelier' => 'boolean',
            'access_technique' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function salarie(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function conductedInterviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'manager_id');
    }

    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
    }

    #[Scope]
    protected function admin(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    #[Scope]
    protected function tiers(Builder $query): Builder
    {
        return $query->where('is_tiers', true);
    }

    #[Scope]
    protected function employee(Builder $query): Builder
    {
        return $query->where('is_employee', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password',
        'role', 'department', 'position', 'is_active', 'last_login_at',
    ];

    public const MANAGEMENT_ROLES = ['admin', 'general_manager', 'assistant_manager'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function tips(): HasMany
    {
        return $this->hasMany(Tip::class, 'employee_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGeneralManager(): bool
    {
        return $this->role === 'general_manager';
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }

    public function isManagement(): bool
    {
        return in_array($this->role, self::MANAGEMENT_ROLES);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(OperationsTask::class, 'assigned_to');
    }

    public function getDepartmentLabelAttribute(): string
    {
        return match ($this->department) {
            'front_desk'    => 'Front Desk',
            'housekeeping'  => 'Housekeeping',
            'maintenance'   => 'Maintenance',
            'food_beverage' => 'Food & Beverage',
            'management'    => 'Management',
            default         => 'Other',
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'               => 'Admin',
            'general_manager'     => 'General Manager',
            'assistant_manager'   => 'Assistant Manager',
            'housekeeping_manager'=> 'Housekeeping Manager',
            'employee'            => 'Employee',
            default               => ucfirst($this->role),
        };
    }
}

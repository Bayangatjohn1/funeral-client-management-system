<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\NormalizesName;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, NormalizesName;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
protected $fillable = [
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'name',
    'email',
    'password',
    'role',
    'admin_scope',
    'branch_id',
    'is_active',
    'can_encode_any_branch',
    'contact_number',
    'position',
    'address',
];

    protected static function booted(): void
    {
        static::saving(function (self $user) {
            $user->normalizeUserNameFields();
        });
    }

    protected function normalizeUserNameFields(): void
    {
        foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'name'] as $col) {
            if (array_key_exists($col, $this->attributes)) {
                $this->attributes[$col] = static::cleanNamePart($this->attributes[$col]);
            }
        }

        $first = $this->attributes['first_name'] ?? null;
        $middle = $this->attributes['middle_name'] ?? null;
        $last = $this->attributes['last_name'] ?? null;
        $suffix = $this->attributes['suffix'] ?? null;

        if ($first && $last && (! $this->isDirty('name') || empty($this->attributes['name']))) {
            $this->attributes['name'] = static::buildFullName($first, $middle, $last, $suffix);
        }
    }
    public function branch()
    {
        // return $this->belongsTo(Branch::class);
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function branches()
    {
        return $this->belongsToMany(\App\Models\Branch::class, 'user_branches');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Per-request memoization to avoid duplicate branch scope queries.
     */
    protected ?array $cachedBranchScopeIds = null;
    protected ?\App\Models\TemporaryCrossBranchPermission $cachedActiveTemporaryPermission = null;
    protected static ?bool $cachedAdminScopeColumnExists = null;

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
            'is_active' => 'boolean',
            'can_encode_any_branch' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMainAdmin(): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        if ($this->adminScopeColumnExists() && $this->admin_scope !== null) {
            return in_array($this->admin_scope, ['main', 'all_branches'], true);
        }

        return $this->isAssignedToMainBranch();
    }

    public function isMainBranchAdmin(): bool
    {
        return $this->isMainAdmin();
    }

    public function isBranchAdmin(): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        if ($this->adminScopeColumnExists() && $this->admin_scope !== null) {
            return $this->admin_scope === 'branch';
        }

        return !$this->isAssignedToMainBranch();
    }

    public function roleLabel(): string
    {
        if ($this->isOwner()) {
            return 'Owner';
        }

        if ($this->isMainBranchAdmin()) {
            return 'Main Branch Admin';
        }

        if ($this->isBranchAdmin()) {
            return 'Branch Admin';
        }

        if ($this->role === 'staff') {
            return 'Staff';
        }

        return ucfirst((string) $this->role);
    }

    public function operationalBranchId(): ?int
    {
        if ($this->isOwner()) {
            return null;
        }

        if ($this->branch_id) {
            return (int) $this->branch_id;
        }

        if ($this->isMainBranchAdmin()) {
            return (int) \Illuminate\Support\Facades\Cache::remember(
                'main_branch_id:v1',
                now()->addSeconds(30),
                static fn () => (int) (\App\Models\Branch::where('branch_code', 'BR001')->value('id') ?? 0)
            ) ?: null;
        }

        return null;
    }

    protected function adminScopeColumnExists(): bool
    {
        if (self::$cachedAdminScopeColumnExists !== null) {
            return self::$cachedAdminScopeColumnExists;
        }

        self::$cachedAdminScopeColumnExists = \Illuminate\Support\Facades\Schema::hasColumn('users', 'admin_scope');

        return self::$cachedAdminScopeColumnExists;
    }

    protected function isAssignedToMainBranch(): bool
    {
        $mainBranchId = (int) \Illuminate\Support\Facades\Cache::remember(
            'main_branch_id:v1',
            now()->addSeconds(30),
            static fn () => (int) (\App\Models\Branch::where('branch_code', 'BR001')->value('id') ?? 0)
        );

        return $mainBranchId > 0 && (int) $this->branch_id === $mainBranchId;
    }

    public function temporaryPermissions()
    {
        return $this->hasMany(\App\Models\TemporaryCrossBranchPermission::class);
    }

    public function latestTemporaryPermission()
    {
        return $this->hasOne(\App\Models\TemporaryCrossBranchPermission::class, 'user_id')
            ->latestOfMany('granted_at');
    }

    public function activeTemporaryPermission(): ?\App\Models\TemporaryCrossBranchPermission
    {
        if ($this->cachedActiveTemporaryPermission !== null) {
            return $this->cachedActiveTemporaryPermission;
        }

        $this->cachedActiveTemporaryPermission = $this->temporaryPermissions()
            ->active()
            ->orderByDesc('granted_at')
            ->first();

        return $this->cachedActiveTemporaryPermission;
    }

    /**
     * Compatibility helper used in tests to mimic role assignment.
     * Sets the simple `role` attribute and persists the model.
     */
    public function assignRole(string $role)
    {
        $this->role = $role;
        $this->save();

        return $this;
    }

    public function canEncodeAnyBranch(): bool
    {
        return $this->isMainBranchAdmin();
    }

    public function branchScopeIds(): array
    {
        if ($this->cachedBranchScopeIds !== null) {
            return $this->cachedBranchScopeIds;
        }

        if ($this->isOwner() || $this->isMainBranchAdmin()) {
            $this->cachedBranchScopeIds = \Illuminate\Support\Facades\Cache::remember(
                'active_branch_scope_ids:v1',
                now()->addSeconds(30),
                static fn () => \App\Models\Branch::where('is_active', true)->pluck('id')->all()
            );

            return $this->cachedBranchScopeIds;
        }

        $branchIds = [];

        if ($this->isBranchAdmin() || $this->role === 'staff') {
            if ($this->branch_id) {
                $branchIds[] = (int) $this->branch_id;
            }
        }

        $this->cachedBranchScopeIds = array_values(array_unique(array_map('intval', $branchIds)));

        return $this->cachedBranchScopeIds;
    }
}

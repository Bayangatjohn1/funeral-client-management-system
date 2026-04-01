<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'branch_id',
    'is_active',
    'can_encode_any_branch',
    'contact_number',
    'position',
    'address',
];
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_encode_any_branch' => 'boolean',
        ];
    }

    public function canEncodeAnyBranch(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        if ($this->role !== 'staff') {
            return false;
        }

        if ($this->can_encode_any_branch) {
            return true;
        }

        $this->loadMissing('branch');

        return $this->branch?->branch_code === 'BR001';
    }

    public function branchScopeIds(): array
    {
        if ($this->role === 'admin' || $this->canEncodeAnyBranch()) {
            return \App\Models\Branch::where('is_active', true)->pluck('id')->all();
        }

        $branchIds = $this->branches()
            ->where('is_active', true)
            ->pluck('branches.id')
            ->all();

        if ($this->branch_id) {
            $branchIds[] = (int) $this->branch_id;
        }

        return array_values(array_unique(array_map('intval', $branchIds)));
    }
}

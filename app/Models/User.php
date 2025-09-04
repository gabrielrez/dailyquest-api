<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string $email
 * @property string $password
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Collections owned by the user.
     *
     * @return HasMany<Collection>
     */
    public function ownedCollections(): HasMany
    {
        return $this->hasMany(Collection::class, 'owner_id');
    }

    /**
     * Collections the user is a collaborator of (many-to-many).
     *
     * @return BelongsToMany<Collection>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'user_collection');
    }

    /**
     * Goals created by the user.
     *
     * @return HasMany<Goal>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'owner_id');
    }
}

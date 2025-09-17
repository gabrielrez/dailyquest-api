<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 *
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string $email
 * @property string $password
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
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

    /**
     * Profile picture URL.
     *
     * @return string|null
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        return $this->profile_picture
            ? asset('storage/' . $this->profile_picture)
            : null;
    }
}

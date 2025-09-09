<?php

namespace App\Models;

use App\Http\Enums\GoalStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Collection
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property bool $cyclic
 * @property \DateTimeInterface $deadline
 * @property bool $is_collaborative
 * @property string $status
 * @property int $owner_id
 */
class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cyclic',
        'deadline',
        'is_collaborative',
        'status',
        'owner_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The owner of the collection.
     *
     * @return BelongsTo<User>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Users collaborating in the collection.
     *
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_collection');
    }

    /**
     * Goals that belong to the collection.
     *
     * @return HasMany<Goal>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'collection_id');
    }

    /**
     * Determine if the collection belongs to the given user.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function belongsToUser(User $user, bool $owner_only = false): bool
    {
        if ($owner_only && $this->owner_id !== $user->id) {
            return false;
        }

        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Scope a query to only include collections owned by the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOwnedBy(Builder $query, int $userId)
    {
        return $query->where('owner_id', $userId);
    }

    /**
     * Scope a query to only include collections with the given status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus(Builder $query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include collections for the given user (owner or collaborator).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser(Builder $query, int $userId)
    {
        return $query->where('owner_id', $userId)
            ->orWhereHas('users', fn($q) => $q->where('user_id', $userId));
    }

    /**
     * Returns true if all goals in the collection are complete.
     */
    public function isCompleted(): bool
    {
        return $this->goals()
            ->where('status', '!=', GoalStatusEnum::DONE)
            ->doesntExist();
    }
}

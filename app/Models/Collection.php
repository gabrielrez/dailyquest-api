<?php

namespace App\Models;

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
}

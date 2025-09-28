<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Goal
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $status
 * @property int $collection_id
 * @property int $owner_id
 */
class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'collection_id',
        'assigned_to',
        'owner_id',
        'order',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The collection this goal belongs to.
     *
     * @return BelongsTo<Collection>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /**
     * The user who created the goal.
     *
     * @return BelongsTo<User>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

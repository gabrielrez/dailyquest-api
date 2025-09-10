<?php

namespace App\Models;

use App\Http\Enums\InvitationStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'email',
        'token',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * The collection the invitation belongs to.
     *
     * @return BelongsTo<Collection>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Determine if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Find a pending invitation for the given email and collection.
     */
    public static function findPending(Collection $collection, string $email): ?self
    {
        return static::where('collection_id', $collection->id)
            ->where('email', $email)
            ->where('status', InvitationStatusEnum::PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'done_at' => $this->done_at,
            'order' => $this->order,
            'collection_id' => $this->collection_id,
            'owner_id' => $this->owner_id,
            'assigned_to' => $this->assigned_to,
            'owner' => UserResource::make($this->whenLoaded('owner')),
        ];
    }
}

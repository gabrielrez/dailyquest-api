<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'cyclic' => $this->cyclic,
            'deadline' => $this->deadline,
            'is_collaborative' => $this->is_collaborative,
            'status' => $this->status,
            'completed_at' => $this->completed_at,
            'owner_id' => $this->owner_id,
            'owner' => UserResource::make($this->whenLoaded('owner')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'goals' => GoalResource::collection($this->whenLoaded('goals')),
        ];
    }
}

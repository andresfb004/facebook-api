<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'content'        => $this->content,
            'author'         => $this->user ? [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'images'         => $this->images->map(fn ($image) => asset('storage/' . $image->image_path)),
            'likes_count'    => $this->likes_count,
            'comments_count' => $this->comments_count,
            'comments'       => CommentResource::collection($this->whenLoaded('comments')),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'title_ar'     => $this->title_ar,
            'slug'         => $this->slug,
            'thumbnail'    => $this->thumbnail,
            'excerpt'      => mb_substr(strip_tags($this->body), 0, 150),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->format('Y-m-d'),
            'author'       => $this->whenLoaded('author', fn() => [
                'id'   => $this->author->id,
                'name' => $this->author->name,
            ]),
        ];
    }
}

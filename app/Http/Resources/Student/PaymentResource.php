<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'amount'     => $this->amount,
            'currency'   => $this->currency,
            'status'     => $this->status,
            'course'     => $this->whenLoaded('course', fn() => [
                'id'    => $this->course->id,
                'title' => $this->course->title,
                'slug'  => $this->course->slug,
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'paid_at'    => $this->paid_at?->format('Y-m-d H:i'),
        ];
    }
}

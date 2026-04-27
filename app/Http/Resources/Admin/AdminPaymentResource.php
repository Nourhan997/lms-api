<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'amount'            => $this->amount,
            'currency'          => $this->currency,
            'status'            => $this->status,
            'gateway'           => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'paid_at'           => $this->paid_at?->format('Y-m-d H:i'),
            'created_at'        => $this->created_at->format('Y-m-d H:i'),
            'user'              => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'course'            => $this->whenLoaded('course', fn() => [
                'id'    => $this->course->id,
                'title' => $this->course->title,
            ]),
        ];
    }
}

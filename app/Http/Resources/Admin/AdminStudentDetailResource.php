<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStudentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'is_active'           => $this->is_active,
            'preferred_language'  => $this->preferred_language,
            'placement_completed' => $this->placement_completed_at !== null,
            'placement_score'     => $this->placement_score,
            'created_at'          => $this->created_at->format('Y-m-d'),
            'enrollments'         => $this->whenLoaded('enrollments', fn () => $this->mapEnrollments()),
            'quiz_attempts'       => $this->whenLoaded('quizAttempts', fn () => $this->mapQuizAttempts()),
            'payments'            => $this->whenLoaded('payments', fn () => $this->mapPayments()),
            'certificates'        => $this->whenLoaded('certificates', fn () => $this->mapCertificates()),
        ];
    }

    private function mapEnrollments(): array
    {
        return $this->enrollments->map(fn ($e) => [
            'id'           => $e->id,
            'status'       => $e->status,
            'course'       => ['id' => $e->course?->id, 'title' => $e->course?->title],
            'enrolled_at'  => $e->enrolled_at?->format('Y-m-d'),
            'completed_at' => $e->completed_at?->format('Y-m-d'),
        ])->values()->toArray();
    }

    private function mapQuizAttempts(): array
    {
        return $this->quizAttempts->map(fn ($a) => [
            'id'           => $a->id,
            'score'        => $a->score,
            'passed'       => $a->passed,
            'completed_at' => $a->completed_at?->format('Y-m-d H:i'),
        ])->values()->toArray();
    }

    private function mapPayments(): array
    {
        return $this->payments->map(fn ($p) => [
            'id'      => $p->id,
            'amount'  => $p->amount,
            'status'  => $p->status,
            'course'  => ['id' => $p->course?->id, 'title' => $p->course?->title],
            'paid_at' => $p->paid_at?->format('Y-m-d'),
        ])->values()->toArray();
    }

    private function mapCertificates(): array
    {
        return $this->certificates->map(fn ($c) => [
            'id'              => $c->id,
            'certificate_uid' => $c->certificate_uid,
            'course'          => ['id' => $c->course?->id, 'title' => $c->course?->title],
            'issued_at'       => $c->issued_at?->format('Y-m-d'),
        ])->values()->toArray();
    }
}

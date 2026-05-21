<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(
        string $action,
        ?Model $model = null,
        array $old = [],
        array $new = [],
        ?User $user = null,
    ): void {
        AuditLog::create([
            'user_id'    => ($user ?? auth()->user())?->id,
            'action'     => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model?->id,
            'old_values' => !empty($old) ? $old : null,
            'new_values' => !empty($new) ? $new : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        return AuditLog::with('user:id,name,email')
            ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['model_type'] ?? null, fn ($q, $t) => $q->where('model_type', $t))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(50);
    }
}

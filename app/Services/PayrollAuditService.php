<?php

namespace App\Services;

use App\Models\PayrollAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PayrollAuditService
{
    public function log(
        string $action,
        Model $subject,
        ?array $before,
        ?array $after,
        User $actor,
        Request $request,
        ?string $idempotencyKey = null,
    ): PayrollAuditLog {
        return PayrollAuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => class_basename($subject),
            'auditable_id' => $subject->getKey(),
            'before' => $before,
            'after' => $after,
            'idempotency_key' => $idempotencyKey,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }
}

<?php

namespace App\Observers;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * [DASH-P2] Records privileged admin actions. Attached (in AppServiceProvider) to the security- and
 * money-relevant models. It writes a log row ONLY when the acting user is an authenticated admin on
 * the explicit WEB (session) guard — i.e. someone operating the Filament panel.
 *
 * NB: we gate on auth('web'), NOT the ambient auth()->user(). The `auth:api` middleware calls
 * Auth::shouldUse('api'), which switches the DEFAULT guard to Passport for the rest of the request —
 * so auth()->user() would resolve an API-authenticated admin (admins share the users table and can
 * log into the mobile app), and the observer would fire on API model writes (e.g. update fcm_token).
 * The web guard is cookie/session-based, so a token-only API request never authenticates it and
 * auth('web')->user() stays null there. That keeps API traffic truly un-logged and un-affected.
 */
class AdminAuditObserver
{
    /**
     * Cross-cutting secrets to strip from the audit trail on TOP of each model's own $hidden.
     * The per-model secrets (User's password/remember_token/fcm_token, …) come from $model->getHidden()
     * so this observer never has to track them and can't silently drift out of sync with the models.
     */
    private const EXTRA_SENSITIVE = [
        'password', 'remember_token', 'verification_code', 'verification_code_expires_at',
        'verification_code_attempts', 'fcm_token', 'api_token', 'access_token',
    ];

    public function created(Model $model): void
    {
        $this->log('created', $model, $this->clean($model, $model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        // Only the changed columns, sensitive ones stripped. Skip pure timestamp touches.
        $changes = $this->clean($model, $model->getChanges());
        unset($changes['updated_at']);
        if ($changes === []) {
            return;
        }
        $this->log('updated', $model, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, []);
    }

    public function restored(Model $model): void
    {
        $this->log('restored', $model, []);
    }

    private function log(string $event, Model $model, array $properties): void
    {
        // Explicit web guard: the API's auth:api flips the DEFAULT guard to 'api' mid-request, so
        // auth()->user() (ambient) would resolve an API-authenticated admin and log on API writes.
        // auth('web') is session-based and stays null for token-only API requests.
        $admin = auth('web')->user();
        if (! $admin || ! ($admin->is_admin ?? false)) {
            return; // not a panel admin acting → don't log (covers all API traffic)
        }

        // Audit logging is best-effort: a failure to write the log must NEVER roll back or break
        // the admin action it is recording (the observer runs inside the model's save/delete).
        try {
            AdminActivityLog::create([
                'admin_id' => $admin->getKey(),
                'event' => $event,
                'auditable_type' => $model::class,
                'auditable_id' => $model->getKey(),
                'description' => class_basename($model) . ' ' . $event,
                'properties' => $properties ?: null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('admin audit log write failed', ['error' => $e->getMessage(), 'event' => $event]);
        }
    }

    private function clean(Model $model, array $attributes): array
    {
        // The model's own hidden columns + the cross-cutting secrets, none of which belong in a log.
        $deny = array_merge($model->getHidden(), self::EXTRA_SENSITIVE);
        return array_diff_key($attributes, array_flip($deny));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppPushLog;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\AppConfig\AppRemoteConfigService;
use App\Services\Push\FcmPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAppPushController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $query = trim((string) $request->query('q', ''));

        return view('admin.app-push.index', [
            'query' => $query,
            'users' => $query === ''
                ? collect()
                : User::query()
                    ->where(function ($builder) use ($query): void {
                        $builder->where('name', 'like', '%'.$query.'%')
                            ->orWhere('username', 'like', '%'.$query.'%')
                            ->orWhere('email', 'like', '%'.$query.'%');
                    })
                    ->orderBy('name')
                    ->limit(20)
                    ->get(),
            'logs' => AppPushLog::query()
                ->with(['admin:id,name,username', 'targetUser:id,name,username,email'])
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function send(Request $request, FcmPushService $push, AppRemoteConfigService $remoteConfig): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:800'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'confirm_send' => ['accepted'],
        ]);

        $actionUrl = $remoteConfig->validateActionUrl($data['action_url'] ?? null);
        if (($data['action_url'] ?? null) && $actionUrl === null) {
            throw ValidationException::withMessages(['action_url' => 'Dieses Push-Ziel ist nicht erlaubt.']);
        }

        $target = User::query()->findOrFail($data['target_user_id']);
        $payload = [
            'type' => 'admin_push',
            'target' => $this->targetFromActionUrl($actionUrl),
            'source' => 'admin',
        ];

        if (! $push->isConfigured()) {
            AppPushLog::query()->create([
                'admin_user_id' => $request->user()->id,
                'target_user_id' => $target->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'action_url' => $actionUrl,
                'data_json' => $payload,
                'result_json' => ['ok' => false, 'error' => 'Firebase Cloud Messaging is not configured.'],
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            return back()->withErrors(['fcm' => 'Firebase Cloud Messaging ist serverseitig nicht konfiguriert.']);
        }

        $result = $push->sendToUser($target, $data['title'], $data['body'], $actionUrl, $payload);

        AppPushLog::query()->create([
            'admin_user_id' => $request->user()->id,
            'target_user_id' => $target->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'action_url' => $actionUrl,
            'data_json' => $payload,
            'result_json' => $result,
            'sent_count' => (int) ($result['sent'] ?? 0),
            'failed_count' => (int) ($result['failed'] ?? 0),
        ]);

        UserNotification::query()->create([
            'user_id' => $target->id,
            'actor_id' => null,
            'type' => 'admin_push',
            'title' => $data['title'],
            'body' => $data['body'],
            'action_url' => $actionUrl,
        ]);

        return redirect()
            ->route('admin.app-push.index', ['q' => $target->email])
            ->with('status', 'Push gesendet: '.$result['sent'].' erfolgreich, '.$result['failed'].' fehlgeschlagen.');
    }

    private function targetFromActionUrl(?string $actionUrl): string
    {
        if ($actionUrl === null) {
            return 'notifications';
        }

        if (str_starts_with($actionUrl, 'hntrocks://')) {
            return trim(str_replace('hntrocks://', '', $actionUrl), '/') ?: 'notifications';
        }

        $firstSegment = trim(explode('/', trim($actionUrl, '/'))[0] ?? 'notifications');

        return $firstSegment !== '' ? $firstSegment : 'notifications';
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}

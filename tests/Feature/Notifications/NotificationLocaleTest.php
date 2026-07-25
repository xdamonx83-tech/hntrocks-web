<?php

namespace Tests\Feature\Notifications;

use App\Http\Middleware\SetApiLocale;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserPushDevice;
use App\Services\Notifications\NotificationLocaleResolver;
use App\Services\Push\FcmPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class NotificationLocaleTest extends TestCase
{
    public function test_api_locale_prefers_the_explicit_app_header(): void
    {
        App::setLocale('de');
        $request = Request::create('/api/v1/notifications', 'GET');
        $request->headers->set('X-HNT-Locale', 'en');
        $request->headers->set('Accept-Language', 'de-DE,de;q=0.9');

        $response = (new SetApiLocale())->handle(
            $request,
            fn (): Response => response('ok')
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('en', App::currentLocale());
    }

    public function test_api_locale_uses_accept_language_when_app_header_is_missing(): void
    {
        App::setLocale('de');
        $request = Request::create('/api/v1/notifications', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9,de;q=0.8');

        (new SetApiLocale())->handle(
            $request,
            fn (): Response => response('ok')
        );

        $this->assertSame('en', App::currentLocale());
    }

    public function test_notification_copy_is_resolved_in_both_supported_app_languages(): void
    {
        $actor = new User([
            'name' => 'Malachi',
            'username' => 'malachi',
        ]);
        $notification = new UserNotification([
            'type' => 'feed_comment',
            'title' => 'ui.comment_new_title',
            'body' => 'ui.comment_new_body',
        ]);
        $notification->setRelation('actor', $actor);

        $messages = app(NotificationLocaleResolver::class)->messages($notification);

        $this->assertSame('Neuer Kommentar', $messages['de']['title']);
        $this->assertSame('Malachi hat deinen Beitrag kommentiert.', $messages['de']['body']);
        $this->assertSame('New comment', $messages['en']['title']);
        $this->assertSame('Malachi commented on your post.', $messages['en']['body']);
    }

    public function test_push_toast_uses_the_registered_device_locale(): void
    {
        $device = new UserPushDevice(['locale' => 'en-US']);
        $method = new ReflectionMethod(FcmPushService::class, 'messageForDevice');
        $method->setAccessible(true);

        $message = $method->invoke(
            new FcmPushService(),
            $device,
            'Deutscher Titel',
            'Deutscher Text',
            [
                'de' => [
                    'title' => 'Deutscher Titel',
                    'body' => 'Deutscher Text',
                ],
                'en' => [
                    'title' => 'English title',
                    'body' => 'English body',
                ],
            ]
        );

        $this->assertSame('English title', $message['title']);
        $this->assertSame('English body', $message['body']);
    }
}

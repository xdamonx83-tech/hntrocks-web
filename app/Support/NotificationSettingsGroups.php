<?php

namespace App\Support;

class NotificationSettingsGroups
{
    public static function all(): array
    {
        return [
            'feed_comments' => ['title' => __('ui.notification_feed_comments'), 'text' => __('ui.notification_feed_comments_text')],
            'feed_reactions' => ['title' => __('ui.notification_feed_reactions'), 'text' => __('ui.notification_feed_reactions_text')],
            'friends' => ['title' => __('ui.notification_friends'), 'text' => __('ui.notification_friends_text')],
            'teams' => ['title' => __('ui.notification_teams'), 'text' => __('ui.notification_teams_text')],
            'lfg' => ['title' => __('ui.notification_lfg'), 'text' => __('ui.notification_lfg_text')],
            'gamification' => ['title' => __('ui.notification_gamification'), 'text' => __('ui.notification_gamification_text')],
            'moments' => ['title' => __('ui.notification_moments'), 'text' => __('ui.notification_moments_text')],
            'cups' => ['title' => __('ui.notification_cups'), 'text' => __('ui.notification_cups_text')],
            'guides' => ['title' => __('guides.notifications.settings_title'), 'text' => __('guides.notifications.settings_text')],
            'referrals' => ['title' => __('ui.notification_referrals'), 'text' => __('ui.notification_referrals_text')],
        ];
    }
}

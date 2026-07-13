<?php

namespace App\Support;

class DashboardPrototypeLocalizer
{
    public function localize(string $html): string
    {
        $locale = str_replace('_', '-', app()->getLocale());
        $t = static fn (string $key, array $replace = []): string => e(__("hnt_preview.dashboard.{$key}", $replace));

        $html = str_replace('<html lang="de">', '<html lang="'.e($locale).'">', $html);

        $replacements = [
            '<span>MEIN BEREICH</span>' => '<span>'.$t('my_area').'</span>',
            'aria-label="Mein Bereich"' => 'aria-label="'.$t('my_area').'"',
            'aria-label="Einstellungen"' => 'aria-label="'.$t('settings').'"',
            '>Bearbeiten</button>' => '>'.$t('edit').'</button>',
            '<span>Freunde</span>' => '<span>'.$t('friends').'</span>',
            '<span>Profil</span>' => '<span>'.$t('profile').'</span>',
            '<span>Nachrichten</span>' => '<span>'.$t('messages').'</span>',
            '<span>Inventar</span>' => '<span>'.$t('inventory').'</span>',
            '<span>Wochenauftrag</span>' => '<span>'.$t('weekly_contract').'</span>',
            '<small>Wochenauftrag</small>' => '<small>'.$t('weekly_contract').'</small>',
            '<span>Login-Serie</span>' => '<span>'.$t('login_streak').'</span>',
            '<span>Level-Fortschritt</span>' => '<span>'.$t('level_progress').'</span>',
            '<span>Hinweise</span>' => '<span>'.$t('notifications').'</span>',
            '<span>Anfragen</span>' => '<span>'.$t('requests').'</span>',
            'aria-label="Persönliche Übersicht"' => 'aria-label="'.$t('your_area').'"',
            '<span class="hnt-section-kicker">DEIN BEREICH</span>' => '<span class="hnt-section-kicker">'.$t('your_area').'</span>',
            '<h2>Mein Fortschritt</h2>' => '<h2>'.$t('my_progress').'</h2>',
            '<button class="active">Aktiv</button>' => '<button class="active">'.$t('active').'</button>',
            '<button>Verlauf</button>' => '<button>'.$t('history').'</button>',
            '<span>Aktivität</span><span>Fortschritt</span><span>Belohnung</span><span>Status</span>' => '<span>'.$t('activity').'</span><span>'.$t('progress').'</span><span>'.$t('reward').'</span><span>'.$t('status').'</span>',
            '<strong>Wochenaufträge</strong>' => '<strong>'.$t('weekly_contracts').'</strong>',
            '<strong>Alle Aufträge erledigt</strong>' => '<strong>'.$t('all_contracts_done').'</strong>',
            '<strong>Keine Wochenaufträge</strong>' => '<strong>'.$t('no_weekly_contracts').'</strong>',
            '<small>Aktuell keine aktiven Aufträge</small>' => '<small>'.$t('no_active_contracts').'</small>',
            '<small>Starker Wochenfortschritt</small>' => '<small>'.$t('strong_weekly_progress').'</small>',
            '<small>Aktuell ist nichts offen</small>' => '<small>'.$t('nothing_open').'</small>',
            '<span>Benötigt deine Aufmerksamkeit</span>' => '<span>'.$t('attention').'</span>',
            '<span class="personal-activity-kicker">LETZTE 30 TAGE</span>' => '<span class="personal-activity-kicker">'.$t('last_30_days').'</span>',
            '<h2>Meine Aktivität</h2>' => '<h2>'.$t('my_activity').'</h2>',
            '<span>Tage aktiv</span>' => '<span>'.$t('days_active').'</span>',
            '<span>Aktionen</span>' => '<span>'.$t('actions').'</span>',
            'aria-label="Aktivitäts-Heatmap der letzten 30 Tage"' => 'aria-label="'.$t('activity_heatmap').'"',
            '<span>XP diese Woche</span>' => '<span>'.$t('xp_this_week').'</span>',
            '<span>Rocks verdient</span>' => '<span>'.$t('rocks_earned').'</span>',
            '<h2>Community Feed</h2>' => '<h2>'.$t('community_feed').'</h2>',
            '<button class="active">Für dich</button>' => '<button class="active">'.$t('for_you').'</button>',
            '<button>Folge ich</button>' => '<button>'.$t('following').'</button>',
            'aria-label="Post erstellen"' => 'aria-label="'.$t('create_post').'"',
            '<span>Teilen</span>' => '<span>'.$t('share').'</span>',
            'aria-label="Kommentare öffnen"' => 'aria-label="'.$t('open_comments').'"',
            'aria-label="Speichern"' => 'aria-label="'.$t('save').'"',
            '<h2 id="mobileProfileTitle">Mein Profil</h2>' => '<h2 id="mobileProfileTitle">'.$t('profile_title').'</h2>',
            '<h2 id="mobileStatsTitle">Stats &amp; Übersicht</h2>' => '<h2 id="mobileStatsTitle">'.$t('stats_title').'</h2>',
            '<h2 id="postComposerTitle">Post erstellen</h2>' => '<h2 id="postComposerTitle">'.$t('create_post').'</h2>',
            '<h2 id="commentsModalTitle">Kommentare</h2>' => '<h2 id="commentsModalTitle">'.$t('comments').'</h2>',
            '<strong>Diskussion</strong>' => '<strong>'.$t('discussion').'</strong>',
            '>Relevant <svg>' => '>'.$t('relevant').' <svg>',
            'placeholder="Kommentar schreiben …"' => 'placeholder="'.$t('write_comment').'"',
        ];

        $html = strtr($html, $replacements);

        $html = preg_replace_callback(
            '~<h1>Hello ([^<]+)</h1>~',
            static fn (array $match): string => '<h1>'.e(__('hnt_preview.dashboard.hello', ['name' => html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')])).'</h1>',
            $html
        ) ?: $html;

        $html = preg_replace_callback(
            '~<small>Fortschritt zu Level (\d+)</small>~',
            static fn (array $match): string => '<small>'.e(__('hnt_preview.dashboard.progress_to_level', ['level' => $match[1]])).'</small>',
            $html
        ) ?: $html;

        $html = preg_replace_callback(
            '~<small>(\d+) noch offen</small>~',
            static fn (array $match): string => '<small>'.e(__('hnt_preview.dashboard.open_remaining', ['count' => $match[1]])).'</small>',
            $html
        ) ?: $html;

        $statusMap = [
            'Keine' => $t('none'),
            'Erledigt' => $t('done'),
            'Aktiv' => $t('active'),
            'Offen' => $t('open'),
            'Fertig' => $t('finished'),
            'Läuft' => $t('running'),
        ];

        foreach ($statusMap as $source => $target) {
            $html = str_replace('<i></i>'.$source.'</span>', '<i></i>'.$target.'</span>', $html);
        }

        return $html;
    }
}

<?php

return [
    'enabled' => env('HNT_CROWNS_ENABLED', true),
    'name' => 'Crowns',
    'non_cash_notice' => 'Crowns sind ein rein virtuelles HNT.rocks-Guthaben ohne Echtgeldwert, Auszahlung, Handel oder Übertragbarkeit.',

    'rewards' => [
        'daily_login' => [
            'amount' => 10,
            'daily_limit' => 1,
            'description' => 'Täglicher Login-Bonus',
        ],
        'account_created' => [
            'amount' => 25,
            'daily_limit' => null,
            'description' => 'Account erstellt',
        ],
        'account.created' => [
            'amount' => 25,
            'daily_limit' => null,
            'description' => 'Account erstellt',
        ],
        'profile_completed' => [
            'amount' => 100,
            'daily_limit' => null,
            'description' => 'Profil vervollständigt',
        ],
        'feed_post_created' => [
            'amount' => 5,
            'daily_limit' => 3,
            'description' => 'Feed-Beitrag erstellt',
        ],
        'feed_comment_created' => [
            'amount' => 2,
            'daily_limit' => 10,
            'description' => 'Kommentar geschrieben',
        ],
        'moment_created' => [
            'amount' => 10,
            'daily_limit' => 2,
            'description' => 'Moment veröffentlicht',
        ],
        'moment_comment_created' => [
            'amount' => 2,
            'daily_limit' => 10,
            'description' => 'Moment kommentiert',
        ],
        'cup_submission_created' => [
            'amount' => 10,
            'daily_limit' => 5,
            'description' => 'Cup-Ergebnis eingereicht',
        ],
        'cup_submission_approved' => [
            'amount' => 40,
            'daily_limit' => null,
            'description' => 'Cup-Einreichung bestätigt',
        ],
        'cup_idea_submitted' => [
            'amount' => 5,
            'daily_limit' => 3,
            'description' => 'Cup-Idee eingereicht',
        ],
        'cup_idea_voted' => [
            'amount' => 1,
            'daily_limit' => 10,
            'description' => 'Cup-Idee bewertet',
        ],
        'loadout_challenge_submission_created' => [
            'amount' => 15,
            'daily_limit' => 3,
            'description' => 'Loadout-Challenge eingereicht',
        ],
        'loadout_challenge_submission_accepted' => [
            'amount' => 50,
            'daily_limit' => null,
            'description' => 'Loadout-Challenge bestätigt',
        ],
        'moment_of_week_selected' => [
            'amount' => 100,
            'daily_limit' => null,
            'description' => 'Moment der Woche ausgewählt',
        ],
        'quest_completed' => [
            'amount' => 50,
            'daily_limit' => 3,
            'description' => 'HNT-Auftrag abgeschlossen',
        ],
    ],
];

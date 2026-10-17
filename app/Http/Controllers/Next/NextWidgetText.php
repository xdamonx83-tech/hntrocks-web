<?php

namespace App\Http\Controllers\Next;

final class NextWidgetText
{
    private const COPY = [
        'de' => [
            'activity'=>'Aktivitäten','more'=>'Alle anzeigen','less'=>'Weniger anzeigen','joined'=>'ist HNT.ROCKS beigetreten',
            'posted'=>'hat einen Beitrag veröffentlicht','moment'=>'hat einen Moment geteilt','new_member'=>'Neuer Hunter in der Community',
            'new_post'=>'Neuer Community-Beitrag','new_moment'=>'Neuer Moment aus dem Bayou','empty_activity'=>'Noch keine öffentlichen Aktivitäten.',
            'weekly'=>'Moment der Woche','all_moments'=>'Alle Moments','moment_discover'=>'Moment ansehen',
            'weekly_empty'=>'Diese Woche wurde noch kein Moment ausgewählt.','clip'=>'DEIN CLIP KÖNNTE HIER STEHEN',
            'loadouts'=>'Letzte Loadouts','loadouts_empty'=>'Noch keine öffentlichen Loadouts vorhanden.',
            'loadouts_foot'=>'ÖFFENTLICHE LOADOUTS','cup'=>'Aktueller Cup','all_cups'=>'Alle Cups',
            'cup_empty'=>'Derzeit ist kein Cup angekündigt.','cup_open'=>'Wettbewerb ansehen',
            'guide_title'=>'Beliebte Guides','all_guides'=>'Alle Guides','guide_intro'=>'Entdecke Kartenwissen, Loadouts und Tipps aus der Community.',
            'lobby'=>'Ready Lobby','lobby_join'=>'Hunter finden','lobby_open'=>'Offene Lobbys',
            'lobby_empty'=>'Noch keine offenen Lobbys. Starte deine eigene.',
            'profile'=>'Profil ansehen','no_weapons'=>'Ausrüstung ansehen','entries'=>'Loadouts',
        ],
        'en' => [
            'activity'=>'Activity','more'=>'Show all','less'=>'Show less','joined'=>'joined HNT.ROCKS',
            'posted'=>'published a post','moment'=>'shared a moment','new_member'=>'New Hunter in the community',
            'new_post'=>'New community post','new_moment'=>'New Moment from the Bayou','empty_activity'=>'No public activity yet.',
            'weekly'=>'Moment of the Week','all_moments'=>'All Moments','moment_discover'=>'Watch Moment',
            'weekly_empty'=>'No Moment has been selected for this week.','clip'=>'YOUR CLIP COULD BE HERE',
            'loadouts'=>'Latest Loadouts','loadouts_empty'=>'No public loadouts yet.',
            'loadouts_foot'=>'PUBLIC LOADOUTS','cup'=>'Current Cup','all_cups'=>'All Cups',
            'cup_empty'=>'No cup is currently announced.','cup_open'=>'View competition',
            'guide_title'=>'Trending Guides','all_guides'=>'All Guides','guide_intro'=>'Explore community map knowledge, loadouts and tactics.',
            'lobby'=>'Ready Lobby','lobby_join'=>'Find Hunters','lobby_open'=>'Open lobbies',
            'lobby_empty'=>'No open lobbies yet. Start your own.',
            'profile'=>'View profile','no_weapons'=>'View equipment','entries'=>'Loadouts',
        ],
        'es' => [
            'activity'=>'Actividad','more'=>'Ver todo','less'=>'Ver menos','joined'=>'se unió a HNT.ROCKS',
            'posted'=>'publicó una entrada','moment'=>'compartió un momento','new_member'=>'Nuevo cazador en la comunidad',
            'new_post'=>'Nueva publicación','new_moment'=>'Nuevo momento del Bayou','empty_activity'=>'Aún no hay actividad pública.',
            'weekly'=>'Momento de la semana','all_moments'=>'Todos los momentos','moment_discover'=>'Ver momento',
            'weekly_empty'=>'Todavía no hay un momento elegido esta semana.','clip'=>'TU CLIP PODRÍA APARECER AQUÍ',
            'loadouts'=>'Últimos equipamientos','loadouts_empty'=>'Aún no hay equipamientos públicos.',
            'loadouts_foot'=>'EQUIPAMIENTOS PÚBLICOS','cup'=>'Copa actual','all_cups'=>'Todas las copas',
            'cup_empty'=>'No hay ninguna copa anunciada.','cup_open'=>'Ver competición',
            'guide_title'=>'Guías populares','all_guides'=>'Todas las guías','guide_intro'=>'Explora mapas, equipamientos y tácticas de la comunidad.',
            'lobby'=>'Sala preparada','lobby_join'=>'Buscar cazadores','lobby_open'=>'Salas abiertas',
            'lobby_empty'=>'Aún no hay salas abiertas. Crea una.',
            'profile'=>'Ver perfil','no_weapons'=>'Ver equipo','entries'=>'Equipamientos',
        ],
        'ru' => [
            'activity'=>'Активность','more'=>'Показать всё','less'=>'Свернуть','joined'=>'присоединился к HNT.ROCKS',
            'posted'=>'опубликовал запись','moment'=>'поделился моментом','new_member'=>'Новый охотник в сообществе',
            'new_post'=>'Новая публикация','new_moment'=>'Новый момент из Байу','empty_activity'=>'Публичных событий пока нет.',
            'weekly'=>'Момент недели','all_moments'=>'Все моменты','moment_discover'=>'Смотреть момент',
            'weekly_empty'=>'На этой неделе момент ещё не выбран.','clip'=>'ВАШ КЛИП МОЖЕТ БЫТЬ ЗДЕСЬ',
            'loadouts'=>'Новые комплекты','loadouts_empty'=>'Публичных комплектов пока нет.',
            'loadouts_foot'=>'ОТКРЫТЫЕ КОМПЛЕКТЫ','cup'=>'Текущий турнир','all_cups'=>'Все турниры',
            'cup_empty'=>'Пока нет объявленных турниров.','cup_open'=>'Открыть турнир',
            'guide_title'=>'Популярные гайды','all_guides'=>'Все руководства','guide_intro'=>'Карты, экипировка и тактика сообщества.',
            'lobby'=>'Готовая группа','lobby_join'=>'Найти охотников','lobby_open'=>'Открытые группы',
            'lobby_empty'=>'Открытых групп пока нет. Создайте свою.',
            'profile'=>'Открыть профиль','no_weapons'=>'Посмотреть снаряжение','entries'=>'Комплекты',
        ],
    ];

    public static function get(string $key): string
    {
        $locale = app()->getLocale();
        return (self::COPY[$locale] ?? self::COPY['en'])[$key] ?? self::COPY['en'][$key] ?? $key;
    }
}

<?php

namespace App\Http\Controllers\Next;

final class NextAccountText
{
    private const WORDS = [
        'de' => [
            'menu_label' => 'Hunter-Kontomenü', 'toggle_open' => 'Kontomenü öffnen',
            'toggle_close' => 'Kontomenü schließen', 'account' => 'DEIN HUNTER-ACCOUNT',
            'online' => 'Online · HNT.ROCKS', 'profile' => 'Hunter-Profil ansehen',
            'shortcuts' => 'Konto-Schnellzugriffe',
            'edit' => 'Profil bearbeiten', 'edit_desc' => 'Avatar, Bio & Hunter-Daten',
            'saved' => 'Gespeicherte Beiträge', 'saved_desc' => 'Deine gemerkten Posts',
            'friends' => 'Freunde', 'friends_desc' => 'Kontakte & Anfragen',
            'rocks' => 'Rocks & Belohnungen', 'rocks_desc' => 'Level, Badges & Shop',
            'settings' => 'Einstellungen', 'settings_desc' => 'Privatsphäre & Account',
            'logout' => 'Abmelden', 'saved_page' => 'Gespeicherte Beiträge',
            'saved_intro' => 'Beiträge, die du dir für später gemerkt hast.',
            'saved_empty_title' => 'Noch nichts gemerkt.',
            'saved_empty' => 'Gespeicherte Beiträge erscheinen hier, sobald du im Feed auf „Merken“ klickst.',
            'saved_kicker' => 'GEMERKT',
        ],
        'en' => [
            'menu_label' => 'Hunter account menu', 'toggle_open' => 'Open account menu',
            'toggle_close' => 'Close account menu', 'account' => 'YOUR HUNTER ACCOUNT',
            'online' => 'Online · HNT.ROCKS', 'profile' => 'View Hunter profile',
            'shortcuts' => 'Account shortcuts',
            'edit' => 'Edit profile', 'edit_desc' => 'Avatar, bio & Hunter details',
            'saved' => 'Saved posts', 'saved_desc' => 'Your bookmarked posts',
            'friends' => 'Friends', 'friends_desc' => 'Contacts & requests',
            'rocks' => 'Rocks & Rewards', 'rocks_desc' => 'Levels, badges & shop',
            'settings' => 'Settings', 'settings_desc' => 'Privacy & account',
            'logout' => 'Sign out', 'saved_page' => 'Saved posts',
            'saved_intro' => 'Posts you saved to revisit later.',
            'saved_empty_title' => 'Nothing saved yet.',
            'saved_empty' => 'Saved posts will appear here after you bookmark them in the feed.',
            'saved_kicker' => 'SAVED',
        ],
        'es' => [
            'menu_label' => 'Menú de cuenta Hunter', 'toggle_open' => 'Abrir menú de cuenta',
            'toggle_close' => 'Cerrar menú de cuenta', 'account' => 'TU CUENTA HUNTER',
            'online' => 'En línea · HNT.ROCKS', 'profile' => 'Ver perfil de Hunter',
            'shortcuts' => 'Accesos de la cuenta',
            'edit' => 'Editar perfil', 'edit_desc' => 'Avatar, biografía y datos',
            'saved' => 'Publicaciones guardadas', 'saved_desc' => 'Tus publicaciones guardadas',
            'friends' => 'Amigos', 'friends_desc' => 'Contactos y solicitudes',
            'rocks' => 'Rocks y recompensas', 'rocks_desc' => 'Niveles, insignias y tienda',
            'settings' => 'Configuración', 'settings_desc' => 'Privacidad y cuenta',
            'logout' => 'Cerrar sesión', 'saved_page' => 'Publicaciones guardadas',
            'saved_intro' => 'Publicaciones que has guardado para más tarde.',
            'saved_empty_title' => 'Aún no hay guardados.',
            'saved_empty' => 'Las publicaciones guardadas aparecerán aquí cuando las marques en el feed.',
            'saved_kicker' => 'GUARDADO',
        ],
        'ru' => [
            'menu_label' => 'Меню аккаунта охотника', 'toggle_open' => 'Открыть меню аккаунта',
            'toggle_close' => 'Закрыть меню аккаунта', 'account' => 'ВАШ АККАУНТ ОХОТНИКА',
            'online' => 'В сети · HNT.ROCKS', 'profile' => 'Мой профиль охотника',
            'shortcuts' => 'Быстрые действия',
            'edit' => 'Редактировать профиль', 'edit_desc' => 'Аватар, био и данные охотника',
            'saved' => 'Сохранённые посты', 'saved_desc' => 'Ваши сохранённые публикации',
            'friends' => 'Друзья', 'friends_desc' => 'Контакты и запросы',
            'rocks' => 'Rocks и награды', 'rocks_desc' => 'Уровни, значки и магазин',
            'settings' => 'Настройки', 'settings_desc' => 'Конфиденциальность и аккаунт',
            'logout' => 'Выйти', 'saved_page' => 'Сохранённые посты',
            'saved_intro' => 'Публикации, сохранённые на потом.',
            'saved_empty_title' => 'Пока ничего нет.',
            'saved_empty' => 'Сохранённые посты появятся здесь после добавления в закладки в ленте.',
            'saved_kicker' => 'СОХРАНЕНО',
        ],
    ];

    public static function get(string $key): string
    {
        $lang = app()->getLocale();
        return (self::WORDS[$lang] ?? self::WORDS['en'])[$key] ?? self::WORDS['en'][$key] ?? $key;
    }
}

<?php
namespace App\Http\Controllers\Next;

/** First-pass server-rendered translations for the HNTv3 feed preview. */
final class NextText
{
    private const WORDS = [
        'de'=>[
            'description'=>'Neuigkeiten, Fundstücke und Geschichten aus dem Bayou.',
            'create'=>'Beitrag erstellen','for_you'=>'Für dich','friends'=>'Freunde','teams'=>'Teams','media'=>'Medien',
            'mentions'=>'Erwähnungen','community'=>'AUS DER COMMUNITY','composer'=>'Was gibt’s Neues aus dem Bayou?',
            'picture'=>'Bild','moment'=>'Moment','more'=>'Weitere Beiträge laden',
            'empty_title'=>'Hier ist es gerade still.','empty'=>'Für diesen Filter gibt es keine Beiträge.',
            'reset'=>'Alle Beiträge ansehen','up_to_date'=>'DU BIST AUF DEM LAUFENDEN','goodbye'=>'Bis bald im Bayou.',
            'official'=>'OFFIZIELL','pinned'=>'ANGEHEFTET','community_story'=>'Community-Entdeckung',
            'platform_pc'=>'PC Hunter','platform_xbox'=>'Xbox Hunter','platform_playstation'=>'PlayStation Hunter','platform_crossplay'=>'Crossplay Hunter','platform_unknown'=>'Hunter',
            'post_options'=>'Beitragsoptionen','save'=>'Beitrag merken','share'=>'Link teilen','original'=>'Original öffnen',
            'feeling_title'=>'Fühlt sich :feeling',
            'happy'=>'Glücklich','excited'=>'Motiviert','focused'=>'Fokussiert','chill'=>'Entspannt','tired'=>'Müde','salty'=>'Salzig'
        ],
        'en'=>[
            'description'=>'News, discoveries and stories from the Bayou.',
            'create'=>'Create post','for_you'=>'For you','friends'=>'Friends','teams'=>'Teams','media'=>'Media',
            'mentions'=>'Mentions','community'=>'FROM THE COMMUNITY','composer'=>"What's new in the Bayou?",
            'picture'=>'Image','moment'=>'Moment','more'=>'Load more posts',
            'empty_title'=>'Quiet in the Bayou.','empty'=>'There are no posts for this filter.',
            'reset'=>'See all posts','up_to_date'=>'YOU ARE ALL CAUGHT UP','goodbye'=>'See you in the Bayou.',
            'official'=>'OFFICIAL','pinned'=>'PINNED','community_story'=>'Community discovery',
            'platform_pc'=>'PC Hunter','platform_xbox'=>'Xbox Hunter','platform_playstation'=>'PlayStation Hunter','platform_crossplay'=>'Crossplay Hunter','platform_unknown'=>'Hunter',
            'post_options'=>'Post options','save'=>'Save post','share'=>'Share link','original'=>'Open original',
            'feeling_title'=>'Feeling :feeling',
            'happy'=>'Happy','excited'=>'Motivated','focused'=>'Focused','chill'=>'Relaxed','tired'=>'Tired','salty'=>'Salty'
        ],
        'es'=>[
            'description'=>'Noticias, hallazgos e historias del Bayou.',
            'create'=>'Crear publicación','for_you'=>'Para ti','friends'=>'Amigos','teams'=>'Equipos','media'=>'Multimedia',
            'mentions'=>'Menciones','community'=>'DE LA COMUNIDAD','composer'=>'¿Qué hay de nuevo en el Bayou?',
            'picture'=>'Imagen','moment'=>'Momento','more'=>'Cargar más publicaciones',
            'empty_title'=>'Todo está tranquilo por aquí.','empty'=>'No hay publicaciones para este filtro.',
            'reset'=>'Ver todas','up_to_date'=>'ESTÁS AL DÍA','goodbye'=>'Nos vemos en el Bayou.',
            'official'=>'OFICIAL','pinned'=>'FIJADO','community_story'=>'Descubrimiento de la comunidad',
            'platform_pc'=>'Cazador PC','platform_xbox'=>'Cazador Xbox','platform_playstation'=>'Cazador PlayStation','platform_crossplay'=>'Cazador multiplataforma','platform_unknown'=>'Cazador',
            'post_options'=>'Opciones de publicación','save'=>'Guardar publicación','share'=>'Compartir enlace','original'=>'Abrir original',
            'feeling_title'=>'Se siente :feeling',
            'happy'=>'Feliz','excited'=>'Motivado','focused'=>'Concentrado','chill'=>'Relajado','tired'=>'Cansado','salty'=>'Frustrado'
        ],
        'ru'=>[
            'description'=>'Новости, находки и истории из Байу.',
            'create'=>'Создать запись','for_you'=>'Для вас','friends'=>'Друзья','teams'=>'Команды','media'=>'Медиа',
            'mentions'=>'Упоминания','community'=>'ИЗ СООБЩЕСТВА','composer'=>'Что нового в Байу?',
            'picture'=>'Фото','moment'=>'Момент','more'=>'Загрузить ещё записи',
            'empty_title'=>'Пока здесь тихо.','empty'=>'По этому фильтру записей нет.',
            'reset'=>'Все записи','up_to_date'=>'ВЫ ВСЁ ПРОСМОТРЕЛИ','goodbye'=>'Увидимся в Байу.',
            'official'=>'ОФИЦИАЛЬНЫЙ','pinned'=>'ЗАКРЕПЛЕНО','community_story'=>'Находки сообщества',
            'platform_pc'=>'Охотник ПК','platform_xbox'=>'Охотник Xbox','platform_playstation'=>'Охотник PlayStation','platform_crossplay'=>'Охотник разных платформ','platform_unknown'=>'Охотник',
            'post_options'=>'Действия с записью','save'=>'Сохранить запись','share'=>'Поделиться ссылкой','original'=>'Открыть оригинал',
            'feeling_title'=>'Настроение: :feeling',
            'happy'=>'Радость','excited'=>'Воодушевление','focused'=>'Сосредоточенность','chill'=>'Спокойствие','tired'=>'Усталость','salty'=>'Раздражение'
        ]
    ];
    public static function get(string $key, ?string $locale=null): string
    {
        $locale = $locale ?? app()->getLocale();
        $words = self::WORDS[$locale] ?? self::WORDS['en'];
        return $words[$key] ?? self::WORDS['en'][$key] ?? $key;
    }
    public static function moods(?string $locale=null): array
    {
        return collect(['happy','excited','focused','chill','tired','salty'])
            ->mapWithKeys(fn($key)=>[$key=>self::get($key,$locale)])->all();
    }
}

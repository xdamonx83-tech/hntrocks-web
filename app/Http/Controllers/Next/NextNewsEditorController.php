<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NextNewsEditorController extends Controller
{
    /**
     * HNTv3 presentation for the existing News TipTap workspace.
     * The editor remains the established authenticated React module,
     * including its News API, versioning, media upload and publish actions.
     */
    public function __invoke(Request $request, ?int $articleId = null): View
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_if($articleId !== null && $articleId < 1, 404);

        app('translation.loader')->addPath(base_path('lang'));
        $locale = $request->session()->get('hnt_next_locale');
        if (! in_array($locale, ['de','en','es','ru'], true)) {
            $locale = $request->user()?->profile?->language;
        }
        if (! in_array($locale, ['de','en','es','ru'], true)) $locale = 'de';
        app()->setLocale($locale);

        $words = [
            'de'=>[
                'heading'=>'News erstellen','edit'=>'News bearbeiten','intro'=>'Erstelle deinen Artikel im Hunter Journal. Alle Editor-Werkzeuge aus dem bisherigen News-System bleiben verfügbar.',
                'back'=>'Zur News-Übersicht','manage'=>'Artikel verwalten','editor'=>'HUNTER JOURNAL / REDAKTION',
                'tip'=>'Dein Artikel wird im Editor automatisch als Entwurf gesichert. Erst „Veröffentlichen“ macht ihn öffentlich.',
                'workspace'=>'Artikel-Editor',
            ],
            'en'=>[
                'heading'=>'Create news','edit'=>'Edit news','intro'=>'Write your Hunter Journal story. All existing news editor tools remain available.',
                'back'=>'Back to news','manage'=>'Manage articles','editor'=>'HUNTER JOURNAL / EDITORIAL',
                'tip'=>'Your story is automatically saved as a draft. It becomes public only when you select Publish.',
                'workspace'=>'Article editor',
            ],
            'es'=>[
                'heading'=>'Crear noticia','edit'=>'Editar noticia','intro'=>'Escribe tu artículo en Hunter Journal. Todas las herramientas del editor siguen disponibles.',
                'back'=>'Volver a noticias','manage'=>'Administrar artículos','editor'=>'HUNTER JOURNAL / REDACCIÓN',
                'tip'=>'Tu artículo se guarda automáticamente como borrador y solo es público al publicarlo.',
                'workspace'=>'Editor de artículos',
            ],
            'ru'=>[
                'heading'=>'Создать новость','edit'=>'Редактировать новость','intro'=>'Напишите материал для Hunter Journal. Все инструменты старого редактора сохранены.',
                'back'=>'К новостям','manage'=>'Управление статьями','editor'=>'HUNTER JOURNAL / РЕДАКЦИЯ',
                'tip'=>'Материал автоматически сохраняется как черновик и становится публичным только после публикации.',
                'workspace'=>'Редактор статей',
            ],
        ][$locale];

        return view('next.news-editor', [
            'viewer'=>$request->user(),
            'locale'=>$locale,
            'articleId'=>$articleId,
            'words'=>$words,
            'nextPageTitle'=>$articleId ? $words['edit'] : $words['heading'],
            'nextActivePage'=>'news',
            'frameUrl'=>url($articleId ? '/news/'.$articleId.'/edit?hntEditorEmbed=1' : '/news/create?hntEditorEmbed=1'),
        ]);
    }
}

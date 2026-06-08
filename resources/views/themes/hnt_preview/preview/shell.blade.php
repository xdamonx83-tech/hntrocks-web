@extends('themes.hnt_preview.layouts.app')

@section('title', 'Preview Shell · hnt.rocks')

@section('content')
<header class="feed-header hnt-preview-feed-header">
    <div>
        <p class="hnt-preview-kicker">Admin Preview · Patch 594</p>
        <h1>HNT <i class="ph ph-star logo-icon-inline" aria-hidden="true"></i> Preview Shell</h1>
        <p>Globale Layout-Hülle des neuen Templates. Diese Seite ist nur über den Admin-Preview-Bereich erreichbar.</p>
    </div>
</header>

<div class="feed-content hnt-preview-feed-content">
    <article class="post hnt-preview-status-card">
        <div class="post-header">
            <div class="post-author">
                <span class="avatar avatar-sm placeholder-avatar-1"></span>
                <div class="author-info">
                    <strong>Theme Preview</strong>
                    <span>hnt.rocks · kontrollierter Testmodus</span>
                </div>
            </div>
            <div class="post-header-actions">
                <button class="icon-btn-green" type="button" aria-label="Sicherheitsstatus"><i class="ph ph-shield-check" aria-hidden="true"></i></button>
                <span class="btn-following">isoliert</span>
            </div>
        </div>

        <div class="hnt-preview-split-grid">
            <section>
                <h2>Was dieser Patch macht</h2>
                <p>Die neue Shell stellt Sidebar, rechte Widgets, Composer-Modal, Kommentar-Modal und Grundlayout bereit. Sie ist noch keine produktive Feed-Migration.</p>
                <div class="post-tags">
                    <span class="tag">Admin only</span>
                    <span class="tag">Session Preview</span>
                    <span class="tag">Fallback bleibt</span>
                    <span class="tag">Socialite bleibt live</span>
                </div>
            </section>
            <section class="hnt-preview-mini-panel">
                <span>Aktueller Fokus</span>
                <strong>Global Shell</strong>
                <small>Danach erst Feed, Profil und weitere Seiten einzeln übertragen.</small>
            </section>
        </div>
    </article>

    <article class="post">
        <div class="post-header">
            <div class="post-author">
                <span class="avatar avatar-sm placeholder-avatar-2"></span>
                <div class="author-info">
                    <strong>HNT.rocks</strong>
                    <span>@hntrocks · Preview</span>
                </div>
            </div>
            <div class="post-header-actions">
                <button class="icon-btn-green" type="button"><i class="ph ph-shield-check" aria-hidden="true"></i></button>
                <span class="btn-following">Following</span>
            </div>
        </div>

        <div class="post-image-placeholder tone-5"></div>

        <div class="post-actions">
            <div class="action-group">
                <button class="action-btn" type="button">
                    <i class="ph ph-heart" aria-hidden="true"></i>
                    592
                </button>
                <button class="action-btn" type="button" data-post-modal-open aria-label="Kommentare öffnen">
                    <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
                    18
                </button>
                <button class="action-btn" type="button">
                    <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
                </button>
            </div>
            <button class="btn-collect" type="button">
                <i class="ph ph-crown-simple" aria-hidden="true"></i>
                Crown
            </button>
        </div>

        <p class="post-text">Das ist ein sicherer Shell-Test im neuen HNT-Look: dunkle Cards, Gold als Primary, Bayou-Grün für aktive Zustände und keine öffentlichen Auswirkungen.</p>

        <div class="post-tags">
            <span class="tag">#HNTrocks</span>
            <span class="tag">#Preview</span>
            <span class="tag">#BayouGreen</span>
            <span class="tag">#GoldPrimary</span>
        </div>
    </article>
</div>
@endsection

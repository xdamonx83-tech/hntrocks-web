<div aria-hidden="true" class="post-composer-backdrop" id="postComposerModal">
<section aria-labelledby="postComposerTitle" aria-modal="true" class="post-composer-modal" role="dialog">
<header class="post-composer-head">
<div>
<span>HNT.ROCKS</span>
<h2 id="postComposerTitle">Post erstellen</h2>
</div>
<div class="post-composer-head-actions">
<button class="composer-audience" id="composerAudience" type="button">
<svg><use href="#i-users"></use></svg>
<span>Öffentlich</span>
<svg class="composer-chevron"><use href="#i-chevron"></use></svg>
</button>
<button aria-label="Post-Composer schließen" class="post-composer-close" id="postComposerClose">
<svg><use href="#i-x"></use></svg>
</button>
</div>
</header>
<div class="post-composer-body">
<div class="composer-identity">
<div class="composer-avatar">
<img alt="Valentina" src="{{ asset('assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg') }}"/>
<i></i>
</div>
<div>
<strong>Valentina</strong>
<span>@valentina</span>
</div>
</div>
<div aria-label="Post-Typ" class="composer-types" role="tablist">
<button class="active" data-composer-type="Beitrag" type="button">Beitrag</button>
<button data-composer-type="LFG" type="button">LFG</button>
<button data-composer-type="Moment" type="button">Moment</button>
<button data-composer-type="Frage" type="button">Frage</button>
</div>
<label class="composer-textarea-shell">
<textarea id="postComposerInput" maxlength="1000" placeholder="Was gibt es Neues im Bayou?"></textarea>
<span id="postComposerCounter">0/1000</span>
</label>
<section aria-live="polite" class="composer-type-panel" id="composerTypePanel">
<div class="composer-type-copy">
<span>BEITRAG</span>
<strong>Teile Gedanken, Updates oder einen kurzen Bericht.</strong>
</div>
</section>
<div class="composer-attachment" hidden="" id="composerAttachment">
<div class="composer-attachment-preview">
<div>
<span>MEDIEN-VORSCHAU</span>
<strong>Bayou moment.jpg</strong>
<small>1920 × 1080 · 2,4 MB</small>
</div>
<button aria-label="Anhang entfernen" id="removeComposerAttachment" type="button">
<svg><use href="#i-x"></use></svg>
</button>
</div>
</div>
<div class="composer-tools">
<span>Zum Post hinzufügen</span>
<div>
<button aria-label="Medien hinzufügen" id="composerMediaButton" type="button">
<svg><use href="#i-image"></use></svg>
</button>
<button aria-label="Emoji hinzufügen" id="composerEmojiButton" type="button">
<svg><use href="#i-smile"></use></svg>
</button>
<button aria-label="LFG-Daten hinzufügen" id="composerLfgButton" type="button">
<svg><use href="#i-users"></use></svg>
</button>
</div>
</div>
</div>
<footer class="post-composer-footer">
<div class="composer-status">
<i></i>
<span>Bereit zum Veröffentlichen</span>
</div>
<div>
<button class="composer-draft" id="saveComposerDraft" type="button">Entwurf</button>
<button class="composer-publish" id="publishComposerPost" type="button">
<span>Veröffentlichen</span>
<svg><use href="#i-send"></use></svg>
</button>
</div>
</footer>
</section>
</div>

<!DOCTYPE html>

<html lang="{{ app()->getLocale() }}">
<head>
<meta name="robots" content="noindex,nofollow,noarchive"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta content="#0d0d0d" name="theme-color"/>
<title>HNT.ROCKS – Feed (Next Demo)</title>




<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500;600;700&amp;display=swap" rel="stylesheet" data-hnt-standard-font="exo"/>
@vite(['resources/css/next/hntv3.css'], 'build/next')
<script>window.HNTNextLocaleMoodLabels=@json(\App\Http\Controllers\Next\NextText::moods());</script>
<script defer src="/build/next/hntv3-js/script.js"></script>
<script defer src="/build/next/hntv3-js/navigation.js"></script>
<script defer src="/build/next/hntv3-js/account-menu.js"></script>
<script defer src="/build/next/hntv3-js/social-dropdowns.js"></script>
<script defer src="/build/next/hntv3-js/feed.js"></script>
<script type="module" src="/build/next/hntv3-js/videojs-neutral.bundle.js?v=videojs10-20261016"></script>
<script defer src="/build/next/hntv3-js/media-viewer.js"></script>
<script defer src="/build/next/hntv3-js/media-viewer-comments.js?v=avatars-20261017"></script>
<script defer src="/build/next/hntv3-js/hnt-live-adapter.js?v=viewer-20261016"></script>
<script defer src="/build/next/hntv3-js/hnt-next-writes.js?v=poll-20261017"></script>
<script defer src="/build/next/hntv3-js/like-embers.js?v=twotone-20261016"></script>
<script defer src="/build/next/hntv3-js/composer-dispatch.js"></script>
<script defer src="/build/next/hntv3-js/composer-extras.js?v=locale-20261017"></script>
<script defer src="/build/next/hntv3-js/feed-discussions.js"></script>
<script defer src="/build/next/hntv3-js/post-comments-modal.js?v=avatars-20261017"></script>
<script defer src="/build/next/hntv3-js/hnt-next-polls.js?v=poll-voters-20261017"></script>
<script defer src="/build/next/hntv3-js/post-options-menu.js?v=manage-20261017"></script>
<script defer src="/build/next/hntv3-js/hnt-post-management.js?v=close-unified-20261017"></script>
<script defer src="/build/next/hntv3-js/post-type-badges.js?v=poll-badge-20261017"></script>
<script defer src="/build/next/hntv3-js/share-modal.js?v=viewer-20261016"></script>
<script defer src="/build/next/hntv3-js/font-boot.js"></script>
<script defer src="/build/next/hntv3-js/activity-widget.js"></script>
<script defer src="/build/next/hntv3-js/latest-loadouts-widget.js"></script>
<script defer src="/build/next/hntv3-js/hunter-hud.js"></script>
<script defer src="/build/next/hntv3-js/widget-focus.js"></script>
<script defer src="/build/next/hntv3-js/mobile-header.js"></script>
<script defer src="/build/next/hntv3-js/search-experience.js"></script>
<script defer src="/build/next/hntv3-js/language-switcher.js?v=locale-20261017"></script>
<script defer src="/build/next/hntv3-js/prestige-widget.js"></script>
<script defer src="/build/next/hntv3-js/settings-modal.js"></script>
<script defer src="/build/next/hntv3-js/settings-profile-editor.js"></script>
<style>
/* Bayou Focus: show the complete source image at 100% fit, regardless of aspect ratio. */
.hnt-viewer-stage { min-width:0; min-height:0; }
.hnt-viewer-picture {
  display:flex; justify-content:center; align-items:center;
  min-width:0; min-height:0; width:100%; height:100%;
}
.hnt-viewer-picture .hnt-viewer-image {
  display:block; flex:0 1 auto;
  width:auto; height:auto; max-width:100%; max-height:100%;
  object-fit:contain; object-position:center;
}
.feed-filters a.feed-filter{text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
.feed-post .post-caption{white-space:pre-wrap;overflow-wrap:anywhere}
.feed-post .media-art video{display:block;width:100%;max-height:620px;object-fit:contain;background:#111}
.feed-post .hnt-live-permalink{display:block;padding:9px 12px;color:var(--accent);font-size:11px;text-decoration:none}
.feed-post .hnt-live-shared{margin:9px 0;padding:12px 14px;border-radius:8px;background:var(--surface-control);color:var(--subtle-text);font-size:11px}
.hnt-live-load-more{display:block;text-align:center;border:1px solid var(--panel-border);border-radius:10px;background:var(--widget);padding:15px;margin-bottom:14px;color:var(--accent);font-size:11px;font-weight:700;text-decoration:none}
.hnt-live-load-more:hover{border-color:var(--accent)}
.hnt-live-action-toast{position:fixed;right:20px;bottom:20px;z-index:2500;background:var(--widget);color:var(--title);border:1px solid var(--accent);border-radius:10px;padding:13px 18px;font-size:11px;box-shadow:0 13px 38px #000a}
/* Compact reply expand/collapse controls (comments and media viewer). */
.hnt-mv-thread-toggle,.hnt-pd-replies-toggle,.hnt-inline-thread-toggle {
  font-size:8px !important; font-weight:610 !important; line-height:1.25 !important;
  letter-spacing:0 !important; gap:5px !important;
  padding:3px 0 5px 22px !important; min-height:0 !important;
}
.hnt-mv-thread-toggle::before,.hnt-pd-replies-toggle::before,.hnt-inline-thread-toggle::before {
  width:10px !important; flex:0 0 10px;
}

/* HNTv3 real shared posts: reuse the original modal design in feed cards. */
.feed-post:has(> .hnt-live-share-embed) > .post-caption{white-space:pre-line;}
.hnt-live-share-embed{margin:12px 0 4px;}
.hnt-live-share-embed .hnt-live-share-media-link{display:block;text-decoration:none;}
.hnt-live-share-embed .hnt-share-original-media{height:clamp(165px,27vh,285px);}
.hnt-live-share-embed .hnt-share-original-media img{object-fit:contain;background:#1c1e1c;}
.hnt-live-share-embed .hnt-live-share-video video{width:100%;height:100%;display:block;object-fit:contain;background:#171817;}
.hnt-live-share-embed .hnt-share-original-caption{white-space:pre-line;overflow-wrap:anywhere;}
.hnt-live-share-embed .hnt-share-original-head{min-width:0;}
.hnt-live-share-embed .hnt-share-original-author{overflow:hidden;}
.hnt-live-share-embed .hnt-share-original-author strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.hnt-live-share-embed .hnt-share-visit{text-decoration:none;}
/* Playable feed video: the fixed 205px crop previously hid native controls. */
.feed-post .media-art.hnt-live-video {height:auto;aspect-ratio:16/9;min-height:0;max-height:none;overflow:hidden;display:flex;align-items:stretch;justify-content:center;background:#111413;}
.feed-post .media-art.hnt-live-video > video {width:100%;height:100%;max-height:none;min-height:0;object-fit:contain;display:block;position:relative;z-index:2;background:#111413;pointer-events:auto;}
.feed-post .media-art.hnt-live-video .media-art-tag {pointer-events:none;z-index:3;}
.feed-post .media-art.hnt-live-video::after {pointer-events:none;}
.hnt-next-upload-progress{margin:14px 0 4px;padding:12px 13px;background:#262f29;border:1px solid #57756180;border-radius:10px;}
.hnt-next-upload-progress[hidden]{display:none!important;}
.hnt-next-upload-progress-head{display:flex;gap:10px;justify-content:space-between;align-items:center;color:#c3d5c7;font-size:11px;line-height:1.5;font-weight:600;}
.hnt-next-upload-progress-head strong{color:#a2d8b1;font-size:11px;font-variant-numeric:tabular-nums;}
.hnt-next-upload-progress-track{height:6px;overflow:hidden;border-radius:50px;margin-top:9px;background:#1b231e;border:1px solid #62866b40;}
.hnt-next-upload-progress-track>div{width:0;height:100%;border-radius:inherit;background:linear-gradient(90deg,#568768,#a1ddb3);transition:width .14s ease-out;}
/* Video.js 10 Neutral player: compact clips instead of a full-column video. */
.feed-post .media-art.hnt-live-video{height:auto;aspect-ratio:auto;min-height:0;width:100%;max-height:none;display:flex;justify-content:center;align-items:center;overflow:hidden;border-radius:10px;background:#151a16;}
.feed-post .media-art.hnt-live-video video-player.hnt-videojs-player{display:block;position:relative;z-index:2;flex:none;width:min(100%,680px);max-width:680px;aspect-ratio:16/9;max-height:383px;background:#101412;border-radius:9px;overflow:hidden;}
.feed-post .media-art.hnt-live-video video-neutral-skin{display:block;width:100%;height:100%;aspect-ratio:16/9;--media-accent-color:#83c994;}
.feed-post .media-art.hnt-live-video video-neutral-skin video{display:block;width:100%;height:100%;max-height:none;object-fit:contain;background:#101412;}
.feed-post .media-art.hnt-live-video .media-art-tag{z-index:3;pointer-events:none;}
@media(max-width:720px){.feed-post .media-art.hnt-live-video video-player.hnt-videojs-player{width:100%;max-width:100%;max-height:unset;}}
/* HNTv3 post management modal – inherits original accents and typography. */
.hnt-post-option-separator{height:1px;background:#ffffff16;margin:7px 6px;}
.post-option-item[data-post-delete] .post-option-icon,.post-option-item[data-post-report] .post-option-icon{color:#c89791;}
.hnt-post-manage-overlay[hidden]{display:none!important;}
.hnt-post-manage-overlay{position:fixed;inset:0;z-index:3800;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(7,10,8,.78);backdrop-filter:blur(8px);}
.hnt-post-manage-panel{width:min(100%,510px);border:1px solid #68857363;border-radius:17px;box-shadow:0 25px 85px #000b;background:#303331;color:#d9e4dc;padding:22px;display:grid;gap:14px;}
.hnt-post-manage-head{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;border-bottom:1px solid #ffffff16;padding-bottom:14px;}
.hnt-post-manage-eyebrow{font-size:9px;font-weight:700;letter-spacing:1.3px;color:#87c897;}
.hnt-post-manage-heading{font-size:18px;font-weight:750;color:#ecf2ec;margin:6px 0 3px;}
.hnt-post-manage-subtitle{font-size:11px;color:#a0afa4;line-height:1.6;}
.hnt-post-manage-x{display:grid;place-items:center;flex:0 0 32px;height:32px;background:#414942;border:1px solid #ffffff16;color:#ccd9cf;border-radius:9px;cursor:pointer;font-size:20px;}
.hnt-post-manage-form{display:grid;gap:12px;}
.hnt-post-manage-label{display:grid;gap:7px;font-size:11px;color:#bed1c2;font-weight:600;}
.hnt-post-manage-form textarea,.hnt-post-manage-form select{background:#252a26;border:1px solid #60705e;color:#e7eee8;border-radius:10px;padding:12px;font:inherit;font-size:12px;line-height:1.6;max-width:100%;width:100%;box-sizing:border-box;}
.hnt-post-manage-form textarea:focus,.hnt-post-manage-form select:focus{outline:1px solid #83c994;outline-offset:1px;}
.hnt-post-manage-form textarea{min-height:140px;resize:vertical;}
.hnt-post-manage-counter{text-align:right;color:#87968b;font-size:10px;}
.hnt-post-manage-controls{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:9px;margin-top:2px;}
.hnt-post-manage-controls button{border-radius:9px;font-size:11px;font-weight:700;padding:10px 16px;cursor:pointer;border:1px solid #526455;}
.hnt-post-manage-cancel{background:#3b423d;color:#d6e0d7;}
.hnt-post-manage-primary{background:#a9d8af;color:#1d3022;}
.hnt-post-manage-primary.is-danger{background:#bd817a;color:#221613;}
.hnt-post-manage-controls button:disabled{opacity:.45;cursor:wait;}
.hnt-post-manage-message{font-size:11px;color:#dca9a1;min-height:1px;line-height:1.5;}
@media(max-width:560px){.hnt-post-manage-panel{padding:16px;}.hnt-post-manage-overlay{align-items:flex-end;padding:10px;}.hnt-post-manage-form textarea{min-height:110px;}}
.hnt-post-manage-overlay [hidden]{display:none!important;}
/* Real polls and moods; existing HNTv3 choice styling remains intact. */
.hnt-live-poll{margin:12px 0 16px;}
.hnt-live-poll .hnt-feed-poll-option:not(:disabled){cursor:pointer;}
.hnt-live-poll .hnt-feed-poll-option.is-selected{border-color:#8fcca2;background:#33483a;}
.hnt-live-poll .hnt-feed-poll-option:disabled{opacity:1;cursor:default;}
.hnt-live-poll .hnt-feed-poll-bar{pointer-events:none;}
.hnt-live-feeling{margin:10px 0;display:flex;gap:7px;align-items:center;}
.hnt-live-poll.is-voting{opacity:.75;pointer-events:none;}
.hnt-live-poll .hnt-feed-poll-foot{display:flex;align-items:center;gap:8px 14px;justify-content:space-between;flex-wrap:wrap;}
.hnt-poll-voters-trigger{display:inline-flex;align-items:center;gap:4px;background:transparent;border:0;color:#a6d7ae;font:700 10px "Exo 2",sans-serif;cursor:pointer;padding:5px 0;}
.hnt-poll-voters-trigger:hover{text-decoration:underline;color:#d2f2d8;}
.hnt-poll-voters-overlay[hidden]{display:none!important;}
.hnt-poll-voters-overlay{position:fixed;inset:0;z-index:3900;background:rgba(6,10,7,.79);backdrop-filter:blur(7px);display:flex;align-items:center;justify-content:center;padding:18px;}
.hnt-poll-voters-dialog{box-sizing:border-box;width:min(100%,540px);max-height:min(82vh,720px);display:flex;flex-direction:column;background:#2e332f;border:1px solid #677b6c88;border-radius:16px;box-shadow:0 24px 90px #000b;color:#e3eae3;overflow:hidden;font-family:"Exo 2",sans-serif;}
.hnt-poll-voters-head{display:flex;align-items:flex-start;justify-content:space-between;padding:21px 22px 15px;border-bottom:1px solid #ffffff18;gap:16px;}
.hnt-poll-voters-kicker{font-size:9px;letter-spacing:1.3px;font-weight:700;color:#91c89e;}
.hnt-poll-voters-title{margin:6px 0 4px;font-size:19px;line-height:1.3;color:#f2f4ef;overflow-wrap:anywhere;}
.hnt-poll-voters-meta{font-size:11px;color:#98a99d;}
.hnt-poll-voters-close{display:grid;place-items:center;flex:none;width:33px;height:33px;background:#414a42;border:1px solid #ffffff20;color:#e1e9e1;border-radius:9px;font-size:21px;cursor:pointer;}
.hnt-poll-voters-body{padding:16px 22px 22px;overflow-y:auto;overscroll-behavior:contain;display:grid;gap:16px;}
.hnt-poll-voters-option{background:#353e37;border:1px solid #ffffff14;border-radius:11px;overflow:hidden;}
.hnt-poll-voters-option-top{padding:11px 12px;display:flex;gap:12px;justify-content:space-between;align-items:center;font-size:12px;font-weight:650;}
.hnt-poll-voters-option-top strong{color:#a6d8b0;font-size:11px;white-space:nowrap;}
.hnt-poll-voters-progress{height:3px;background:#222b25;}
.hnt-poll-voters-progress>span{height:100%;display:block;background:#79b888;transition:width .2s;}
.hnt-poll-voters-users{display:grid;gap:0;padding:5px 12px 9px;}
.hnt-poll-voters-user{display:flex;align-items:center;gap:9px;padding:7px 0;font-size:11px;color:#dae5dd;border-bottom:1px solid #ffffff0f;}
.hnt-poll-voters-user:last-child{border-bottom:0;}
.hnt-poll-voters-user img{height:26px;width:26px;flex:none;border:1px solid #7c9c82;border-radius:50%;object-fit:cover;background:#3b463e;}
.hnt-poll-voters-empty{font-size:10px;color:#94a799;padding:8px 0;}
.hnt-poll-voters-more{justify-self:start;border:1px solid #65836d;background:#3b5241;color:#d9f1de;border-radius:8px;padding:7px 10px;margin:4px 0 7px;cursor:pointer;font-size:10px;}
.hnt-poll-voters-status{font-size:12px;color:#b4c8b9;line-height:1.6;}
@media(max-width:600px){.hnt-poll-voters-overlay{align-items:flex-end;padding:8px;}.hnt-poll-voters-dialog{max-height:90vh;}.hnt-poll-voters-head{padding:16px;}.hnt-poll-voters-body{padding:14px 16px 18px;}}

/* Close control design lock: the original HNTv3 discussion modal is the
   reference for composer, discussion, Bayou Focus and post management. */
:is(.composer-overlay .hnt-dispatch-dialog .hnt-pd-close,
.hnt-post-dialog .hnt-pd-close,
.hnt-viewer .hnt-viewer-close,
.hnt-post-manage-overlay .hnt-post-manage-x) {
  box-sizing:border-box;
  display:grid;place-items:center;flex:0 0 var(--hnt-size-close);
  width:var(--hnt-size-close);height:var(--hnt-size-close);
  min-width:var(--hnt-size-close);min-height:var(--hnt-size-close);
  padding:0;
  border:1px solid var(--control-border);
  border-radius:var(--hnt-radius-close);
  background:var(--surface-control);color:var(--control-fg);
  cursor:pointer;transform:rotate(0deg);
  transition:var(--hnt-motion-close);
}
:is(.composer-overlay .hnt-dispatch-dialog .hnt-pd-close,
.hnt-post-dialog .hnt-pd-close,
.hnt-viewer .hnt-viewer-close,
.hnt-post-manage-overlay .hnt-post-manage-x):hover {
  background:var(--control-hover-bg);
  color:var(--control-hover-fg);
  transform:rotate(90deg);
}
:is(.composer-overlay .hnt-dispatch-dialog .hnt-pd-close,
.hnt-post-dialog .hnt-pd-close,
.hnt-viewer .hnt-viewer-close,
.hnt-post-manage-overlay .hnt-post-manage-x):focus-visible {
  outline:2px solid var(--focus-ring);outline-offset:2px;
}
:is(.composer-overlay .hnt-dispatch-dialog .hnt-pd-close,
.hnt-post-dialog .hnt-pd-close,
.hnt-viewer .hnt-viewer-close,
.hnt-post-manage-overlay .hnt-post-manage-x) .ph {
  display:block;flex:none;width:16px;height:16px;fill:currentColor;
}
@media(prefers-reduced-motion:reduce){
  :is(.composer-overlay .hnt-dispatch-dialog .hnt-pd-close,
.hnt-post-dialog .hnt-pd-close,
.hnt-viewer .hnt-viewer-close,
.hnt-post-manage-overlay .hnt-post-manage-x){transition:none!important;}
  :is(.composer-overlay .hnt-dispatch-dialog .hnt-pd-close,
.hnt-post-dialog .hnt-pd-close,
.hnt-viewer .hnt-viewer-close,
.hnt-post-manage-overlay .hnt-post-manage-x):hover{transform:none;}
}

/* HNTv3 feed author identities: only account #2/hntrocks is official. */
.feed-post .post-author .hnt-pinned-pill{display:inline-flex;align-items:center;padding:2px 5px;border-radius:4px;background:#454942;color:#c4cfc4;font-size:8px;font-weight:700;letter-spacing:.35px;}
.feed-post .post-author .official-pill{white-space:nowrap;}

/* HNTv3 live comment avatars (discussion and Bayou Focus). */
.hnt-pd-comment-avatar,.hnt-mv-comment-avatar{overflow:hidden;}
.hnt-pd-comment-avatar img,.hnt-mv-comment-avatar img{display:block;width:100%;height:100%;object-fit:cover;border-radius:50%;flex:none;}

/* Compact HNTv3 mood label lives by the author, not in a full-width status bar. */
.feed-post .post-head .author-line .hnt-author-feeling.hnt-live-feeling{
  display:inline-flex;align-items:center;justify-content:center;gap:5px;
  width:max-content;max-width:100%;margin:0;padding:3px 8px;
  border:1px solid #506452;border-radius:999px;background:#3b493d;
  color:#b6dbbd;font-family:"Exo 2",sans-serif;font-size:9px;
  line-height:1.35;font-weight:650;letter-spacing:0;white-space:nowrap;
}
.feed-post .post-head .author-line .hnt-author-feeling .hnt-feed-feeling-emoji{
  font-size:11px;line-height:1;flex:none;
}
@media(max-width:520px){
  .feed-post .post-head .author-line .hnt-author-feeling.hnt-live-feeling{padding:3px 7px;font-size:9px;}
}
/* Next-specific mobile containment; matches original demo breakpoints. */
@media(max-width:990px){.feed-main .feed-layout,.feed-main .feed-layout.hnt-widgets-collapsed{grid-template-columns:minmax(0,1fr)!important}.feed-main .feed-column,.feed-main .feed-list{min-width:0;max-width:100%}}
@media(max-width:720px){.game-rail{width:68px}.feed-main{margin-left:85px;width:calc(100% - 85px);max-width:calc(100% - 85px);padding-right:9px}.feed-layout,.feed-column,.feed-post,.feed-composer{min-width:0;max-width:100%}.feed-filters{max-width:100%;overflow-x:auto;overflow-y:hidden;scrollbar-width:none;overscroll-behavior-x:contain}.feed-filters::-webkit-scrollbar{display:none}.feed-post .post-head,.feed-post .post-author,.feed-post .post-caption{min-width:0;overflow-wrap:anywhere}}
@media(max-width:520px){.feed-main .feed-page-title{min-width:0;max-width:100%}.feed-main .feed-page-title p{overflow-wrap:anywhere}.feed-composer .composer-prompt{min-width:0;text-overflow:ellipsis;overflow:hidden;white-space:nowrap}}
</style>
</head>
<body data-hnt-next-live-posts="true" data-next-viewer-name="{{ $viewer->name }}" data-next-viewer-avatar="{{ $viewer->avatarUrl() }}">
<!-- Phosphor Icons (MIT), copyright (c) 2023 Phosphor Icons. Full license: PHOSPHOR-LICENSE.txt --><svg aria-hidden="true" focusable="false" id="phosphor-sprite" style="position:absolute;width:0;height:0;overflow:hidden" xmlns="http://www.w3.org/2000/svg"><symbol id="ph-arrow-left" viewBox="0 0 256 256"><path d="M224,128a8,8,0,0,1-8,8H59.31l58.35,58.34a8,8,0,0,1-11.32,11.32l-72-72a8,8,0,0,1,0-11.32l72-72a8,8,0,0,1,11.32,11.32L59.31,120H216A8,8,0,0,1,224,128Z"/></symbol><symbol id="ph-arrow-right" viewBox="0 0 256 256"><path d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"/></symbol><symbol id="ph-game-controller" viewBox="0 0 256 256"><path d="M176,112H152a8,8,0,0,1,0-16h24a8,8,0,0,1,0,16ZM104,96H96V88a8,8,0,0,0-16,0v8H72a8,8,0,0,0,0,16h8v8a8,8,0,0,0,16,0v-8h8a8,8,0,0,0,0-16ZM241.48,200.65a36,36,0,0,1-54.94,4.81c-.12-.12-.24-.24-.35-.37L146.48,160h-37L69.81,205.09l-.35.37A36.08,36.08,0,0,1,44,216,36,36,0,0,1,8.56,173.75a.68.68,0,0,1,0-.14L24.93,89.52A59.88,59.88,0,0,1,83.89,40H172a60.08,60.08,0,0,1,59,49.25c0,.06,0,.12,0,.18l16.37,84.17a.68.68,0,0,1,0,.14A35.74,35.74,0,0,1,241.48,200.65ZM172,144a44,44,0,0,0,0-88H83.89A43.9,43.9,0,0,0,40.68,92.37l0,.13L24.3,176.59A20,20,0,0,0,58,194.3l41.92-47.59a8,8,0,0,1,6-2.71Zm59.7,32.59-8.74-45A60,60,0,0,1,172,160h-4.2L198,194.31a20.09,20.09,0,0,0,17.46,5.39,20,20,0,0,0,16.23-23.11Z"></path></symbol><symbol id="ph-crosshair" viewBox="0 0 256 256"><path d="M232,120h-8.34A96.14,96.14,0,0,0,136,32.34V24a8,8,0,0,0-16,0v8.34A96.14,96.14,0,0,0,32.34,120H24a8,8,0,0,0,0,16h8.34A96.14,96.14,0,0,0,120,223.66V232a8,8,0,0,0,16,0v-8.34A96.14,96.14,0,0,0,223.66,136H232a8,8,0,0,0,0-16Zm-96,87.6V200a8,8,0,0,0-16,0v7.6A80.15,80.15,0,0,1,48.4,136H56a8,8,0,0,0,0-16H48.4A80.15,80.15,0,0,1,120,48.4V56a8,8,0,0,0,16,0V48.4A80.15,80.15,0,0,1,207.6,120H200a8,8,0,0,0,0,16h7.6A80.15,80.15,0,0,1,136,207.6ZM128,88a40,40,0,1,0,40,40A40,40,0,0,0,128,88Zm0,64a24,24,0,1,1,24-24A24,24,0,0,1,128,152Z"></path></symbol><symbol id="ph-lightning" viewBox="0 0 256 256"><path d="M215.79,118.17a8,8,0,0,0-5-5.66L153.18,90.9l14.66-73.33a8,8,0,0,0-13.69-7l-112,120a8,8,0,0,0,3,13l57.63,21.61L88.16,238.43a8,8,0,0,0,13.69,7l112-120A8,8,0,0,0,215.79,118.17ZM109.37,214l10.47-52.38a8,8,0,0,0-5-9.06L62,132.71l84.62-90.66L136.16,94.43a8,8,0,0,0,5,9.06l52.8,19.8Z"></path></symbol><symbol id="ph-sword" viewBox="0 0 256 256"><path d="M216,32H152a8,8,0,0,0-6.34,3.12l-64,83.21L72,108.69a16,16,0,0,0-22.64,0l-12.69,12.7a16,16,0,0,0,0,22.63l20,20-28,28a16,16,0,0,0,0,22.63l12.69,12.68a16,16,0,0,0,22.62,0l28-28,20,20a16,16,0,0,0,22.64,0l12.69-12.7a16,16,0,0,0,0-22.63l-9.64-9.64,83.21-64A8,8,0,0,0,224,104V40A8,8,0,0,0,216,32ZM52.69,216,40,203.32l28-28L80.68,188Zm70.61-8L48,132.71,60.7,120,136,195.31ZM208,100.06l-81.74,62.88L115.32,152l50.34-50.34a8,8,0,0,0-11.32-11.31L104,140.68,93.07,129.74,155.94,48H208Z"></path></symbol><symbol id="ph-car" viewBox="0 0 256 256"><path d="M240,104H229.2L201.42,41.5A16,16,0,0,0,186.8,32H69.2a16,16,0,0,0-14.62,9.5L26.8,104H16a8,8,0,0,0,0,16h8v80a16,16,0,0,0,16,16H64a16,16,0,0,0,16-16V184h96v16a16,16,0,0,0,16,16h24a16,16,0,0,0,16-16V120h8a8,8,0,0,0,0-16ZM69.2,48H186.8l24.89,56H44.31ZM64,200H40V184H64Zm128,0V184h24v16Zm24-32H40V120H216ZM56,144a8,8,0,0,1,8-8H80a8,8,0,0,1,0,16H64A8,8,0,0,1,56,144Zm112,0a8,8,0,0,1,8-8h16a8,8,0,0,1,0,16H176A8,8,0,0,1,168,144Z"></path></symbol><symbol id="ph-target" viewBox="0 0 256 256"><path d="M221.87,83.16A104.1,104.1,0,1,1,195.67,49l22.67-22.68a8,8,0,0,1,11.32,11.32l-96,96a8,8,0,0,1-11.32-11.32l27.72-27.72a40,40,0,1,0,17.87,31.09,8,8,0,1,1,16-.9,56,56,0,1,1-22.38-41.65L184.3,60.39a87.88,87.88,0,1,0,23.13,29.67,8,8,0,0,1,14.44-6.9Z"></path></symbol><symbol id="ph-plus" viewBox="0 0 256 256"><path d="M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z"></path></symbol><symbol id="ph-chat-circle-dots" viewBox="0 0 256 256"><path d="M140,128a12,12,0,1,1-12-12A12,12,0,0,1,140,128ZM84,116a12,12,0,1,0,12,12A12,12,0,0,0,84,116Zm88,0a12,12,0,1,0,12,12A12,12,0,0,0,172,116Zm60,12A104,104,0,0,1,79.12,219.82L45.07,231.17a16,16,0,0,1-20.24-20.24l11.35-34.05A104,104,0,1,1,232,128Zm-16,0A88,88,0,1,0,51.81,172.06a8,8,0,0,1,.66,6.54L40,216,77.4,203.53a7.85,7.85,0,0,1,2.53-.42,8,8,0,0,1,4,1.08A88,88,0,0,0,216,128Z"></path></symbol><symbol id="ph-handbag" viewBox="0 0 256 256"><path d="M239.89,198.12l-14.26-120a16,16,0,0,0-16-14.12H176a48,48,0,0,0-96,0H46.33a16,16,0,0,0-16,14.12l-14.26,120A16,16,0,0,0,20,210.6a16.13,16.13,0,0,0,12,5.4H223.92A16.13,16.13,0,0,0,236,210.6,16,16,0,0,0,239.89,198.12ZM128,32a32,32,0,0,1,32,32H96A32,32,0,0,1,128,32ZM32,200,46.33,80H80v24a8,8,0,0,0,16,0V80h64v24a8,8,0,0,0,16,0V80h33.75l14.17,120Z"></path></symbol><symbol id="ph-clock-counter-clockwise" viewBox="0 0 256 256"><path d="M136,80v43.47l36.12,21.67a8,8,0,0,1-8.24,13.72l-40-24A8,8,0,0,1,120,128V80a8,8,0,0,1,16,0Zm-8-48A95.44,95.44,0,0,0,60.08,60.15C52.81,67.51,46.35,74.59,40,82V64a8,8,0,0,0-16,0v40a8,8,0,0,0,8,8H72a8,8,0,0,0,0-16H49c7.15-8.42,14.27-16.35,22.39-24.57a80,80,0,1,1,1.66,114.75,8,8,0,1,0-11,11.64A96,96,0,1,0,128,32Z"></path></symbol><symbol id="ph-users-three" viewBox="0 0 256 256"><path d="M244.8,150.4a8,8,0,0,1-11.2-1.6A51.6,51.6,0,0,0,192,128a8,8,0,0,1-7.37-4.89,8,8,0,0,1,0-6.22A8,8,0,0,1,192,112a24,24,0,1,0-23.24-30,8,8,0,1,1-15.5-4A40,40,0,1,1,219,117.51a67.94,67.94,0,0,1,27.43,21.68A8,8,0,0,1,244.8,150.4ZM190.92,212a8,8,0,1,1-13.84,8,57,57,0,0,0-98.16,0,8,8,0,1,1-13.84-8,72.06,72.06,0,0,1,33.74-29.92,48,48,0,1,1,58.36,0A72.06,72.06,0,0,1,190.92,212ZM128,176a32,32,0,1,0-32-32A32,32,0,0,0,128,176ZM72,120a8,8,0,0,0-8-8A24,24,0,1,1,87.24,82a8,8,0,1,0,15.5-4A40,40,0,1,0,37,117.51,67.94,67.94,0,0,0,9.6,139.19a8,8,0,1,0,12.8,9.61A51.6,51.6,0,0,1,64,128,8,8,0,0,0,72,120Z"></path></symbol><symbol id="ph-graduation-cap" viewBox="0 0 256 256"><path d="M251.76,88.94l-120-64a8,8,0,0,0-7.52,0l-120,64a8,8,0,0,0,0,14.12L32,117.87v48.42a15.91,15.91,0,0,0,4.06,10.65C49.16,191.53,78.51,216,128,216a130,130,0,0,0,48-8.76V240a8,8,0,0,0,16,0V199.51a115.63,115.63,0,0,0,27.94-22.57A15.91,15.91,0,0,0,224,166.29V117.87l27.76-14.81a8,8,0,0,0,0-14.12ZM128,200c-43.27,0-68.72-21.14-80-33.71V126.4l76.24,40.66a8,8,0,0,0,7.52,0L176,143.47v46.34C163.4,195.69,147.52,200,128,200Zm80-33.75a97.83,97.83,0,0,1-16,14.25V134.93l16-8.53ZM188,118.94l-.22-.13-56-29.87a8,8,0,0,0-7.52,14.12L171,128l-43,22.93L25,96,128,41.07,231,96Z"></path></symbol><symbol id="ph-clock" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z"></path></symbol><symbol id="ph-magnifying-glass" viewBox="0 0 256 256"><path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path></symbol><symbol id="ph-gear-six" viewBox="0 0 256 256"><path d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Zm109.94-52.79a8,8,0,0,0-3.89-5.4l-29.83-17-.12-33.62a8,8,0,0,0-2.83-6.08,111.91,111.91,0,0,0-36.72-20.67,8,8,0,0,0-6.46.59L128,41.85,97.88,25a8,8,0,0,0-6.47-.6A112.1,112.1,0,0,0,54.73,45.15a8,8,0,0,0-2.83,6.07l-.15,33.65-29.83,17a8,8,0,0,0-3.89,5.4,106.47,106.47,0,0,0,0,41.56,8,8,0,0,0,3.89,5.4l29.83,17,.12,33.62a8,8,0,0,0,2.83,6.08,111.91,111.91,0,0,0,36.72,20.67,8,8,0,0,0,6.46-.59L128,214.15,158.12,231a7.91,7.91,0,0,0,3.9,1,8.09,8.09,0,0,0,2.57-.42,112.1,112.1,0,0,0,36.68-20.73,8,8,0,0,0,2.83-6.07l.15-33.65,29.83-17a8,8,0,0,0,3.89-5.4A106.47,106.47,0,0,0,237.94,107.21Zm-15,34.91-28.57,16.25a8,8,0,0,0-3,3c-.58,1-1.19,2.06-1.81,3.06a7.94,7.94,0,0,0-1.22,4.21l-.15,32.25a95.89,95.89,0,0,1-25.37,14.3L134,199.13a8,8,0,0,0-3.91-1h-.19c-1.21,0-2.43,0-3.64,0a8.08,8.08,0,0,0-4.1,1l-28.84,16.1A96,96,0,0,1,67.88,201l-.11-32.2a8,8,0,0,0-1.22-4.22c-.62-1-1.23-2-1.8-3.06a8.09,8.09,0,0,0-3-3.06l-28.6-16.29a90.49,90.49,0,0,1,0-28.26L61.67,97.63a8,8,0,0,0,3-3c.58-1,1.19-2.06,1.81-3.06a7.94,7.94,0,0,0,1.22-4.21l.15-32.25a95.89,95.89,0,0,1,25.37-14.3L122,56.87a8,8,0,0,0,4.1,1c1.21,0,2.43,0,3.64,0a8.08,8.08,0,0,0,4.1-1l28.84-16.1A96,96,0,0,1,188.12,55l.11,32.2a8,8,0,0,0,1.22,4.22c.62,1,1.23,2,1.8,3.06a8.09,8.09,0,0,0,3,3.06l28.6,16.29A90.49,90.49,0,0,1,222.9,142.12Z"></path></symbol><symbol id="ph-file-text" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-32-80a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,136Zm0,32a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,168Z"></path></symbol><symbol id="ph-person-simple-run" viewBox="0 0 256 256"><path d="M152,88a32,32,0,1,0-32-32A32,32,0,0,0,152,88Zm0-48a16,16,0,1,1-16,16A16,16,0,0,1,152,40Zm67.31,100.68c-.61.28-7.49,3.28-19.67,3.28-13.85,0-34.55-3.88-60.69-20a169.31,169.31,0,0,1-15.41,32.34,104.29,104.29,0,0,1,31.31,15.81C173.92,186.65,184,207.35,184,232a8,8,0,0,1-16,0c0-41.7-34.69-56.71-54.14-61.85-.55.7-1.12,1.41-1.69,2.1-19.64,23.8-44.25,36.18-71.63,36.18A92.29,92.29,0,0,1,31.2,208,8,8,0,0,1,32.8,192c25.92,2.58,48.47-7.49,67-30,12.49-15.14,21-33.61,25.25-47C86.13,92.35,61.27,111.63,61,111.84A8,8,0,1,1,51,99.36c1.5-1.2,37.22-29,89.51,6.57,45.47,30.91,71.93,20.31,72.18,20.19a8,8,0,1,1,6.63,14.56Z"></path></symbol><symbol id="ph-compass" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216ZM172.42,72.84l-64,32a8.05,8.05,0,0,0-3.58,3.58l-32,64A8,8,0,0,0,80,184a8.1,8.1,0,0,0,3.58-.84l64-32a8.05,8.05,0,0,0,3.58-3.58l32-64a8,8,0,0,0-10.74-10.74ZM138,138,97.89,158.11,118,118l40.15-20.07Z"></path></symbol><symbol id="ph-seal-check" viewBox="0 0 256 256"><path d="M225.86,102.82c-3.77-3.94-7.67-8-9.14-11.57-1.36-3.27-1.44-8.69-1.52-13.94-.15-9.76-.31-20.82-8-28.51s-18.75-7.85-28.51-8c-5.25-.08-10.67-.16-13.94-1.52-3.56-1.47-7.63-5.37-11.57-9.14C146.28,23.51,138.44,16,128,16s-18.27,7.51-25.18,14.14c-3.94,3.77-8,7.67-11.57,9.14C88,40.64,82.56,40.72,77.31,40.8c-9.76.15-20.82.31-28.51,8S41,67.55,40.8,77.31c-.08,5.25-.16,10.67-1.52,13.94-1.47,3.56-5.37,7.63-9.14,11.57C23.51,109.72,16,117.56,16,128s7.51,18.27,14.14,25.18c3.77,3.94,7.67,8,9.14,11.57,1.36,3.27,1.44,8.69,1.52,13.94.15,9.76.31,20.82,8,28.51s18.75,7.85,28.51,8c5.25.08,10.67.16,13.94,1.52,3.56,1.47,7.63,5.37,11.57,9.14C109.72,232.49,117.56,240,128,240s18.27-7.51,25.18-14.14c3.94-3.77,8-7.67,11.57-9.14,3.27-1.36,8.69-1.44,13.94-1.52,9.76-.15,20.82-.31,28.51-8s7.85-18.75,8-28.51c.08-5.25.16-10.67,1.52-13.94,1.47-3.56,5.37-7.63,9.14-11.57C232.49,146.28,240,138.44,240,128S232.49,109.73,225.86,102.82Zm-11.55,39.29c-4.79,5-9.75,10.17-12.38,16.52-2.52,6.1-2.63,13.07-2.73,19.82-.1,7-.21,14.33-3.32,17.43s-10.39,3.22-17.43,3.32c-6.75.1-13.72.21-19.82,2.73-6.35,2.63-11.52,7.59-16.52,12.38S132,224,128,224s-9.15-4.92-14.11-9.69-10.17-9.75-16.52-12.38c-6.1-2.52-13.07-2.63-19.82-2.73-7-.1-14.33-.21-17.43-3.32s-3.22-10.39-3.32-17.43c-.1-6.75-.21-13.72-2.73-19.82-2.63-6.35-7.59-11.52-12.38-16.52S32,132,32,128s4.92-9.15,9.69-14.11,9.75-10.17,12.38-16.52c2.52-6.1,2.63-13.07,2.73-19.82.1-7,.21-14.33,3.32-17.43S70.51,56.9,77.55,56.8c6.75-.1,13.72-.21,19.82-2.73,6.35-2.63,11.52-7.59,16.52-12.38S124,32,128,32s9.15,4.92,14.11,9.69,10.17,9.75,16.52,12.38c6.1,2.52,13.07,2.63,19.82,2.73,7,.1,14.33.21,17.43,3.32s3.22,10.39,3.32,17.43c.1,6.75.21,13.72,2.73,19.82,2.63,6.35,7.59,11.52,12.38,16.52S224,124,224,128,219.08,137.15,214.31,142.11ZM173.66,98.34a8,8,0,0,1,0,11.32l-56,56a8,8,0,0,1-11.32,0l-24-24a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35A8,8,0,0,1,173.66,98.34Z"></path></symbol><symbol id="ph-arrow-up-right" viewBox="0 0 256 256"><path d="M200,64V168a8,8,0,0,1-16,0V83.31L69.66,197.66a8,8,0,0,1-11.32-11.32L172.69,72H88a8,8,0,0,1,0-16H192A8,8,0,0,1,200,64Z"></path></symbol><symbol id="ph-x" viewBox="0 0 256 256"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></symbol><symbol id="ph-bell" viewBox="0 0 256 256"><path d="M221.8,175.94C216.25,166.38,208,139.33,208,104a80,80,0,1,0-160,0c0,35.34-8.26,62.38-13.81,71.94A16,16,0,0,0,48,200H88.81a40,40,0,0,0,78.38,0H208a16,16,0,0,0,13.8-24.06ZM128,216a24,24,0,0,1-22.62-16h45.24A24,24,0,0,1,128,216ZM48,184c7.7-13.24,16-43.92,16-80a64,64,0,1,1,128,0c0,36.05,8.28,66.73,16,80Z"></path></symbol><symbol id="ph-user-plus" viewBox="0 0 256 256"><path d="M256,136a8,8,0,0,1-8,8H232v16a8,8,0,0,1-16,0V144H200a8,8,0,0,1,0-16h16V112a8,8,0,0,1,16,0v16h16A8,8,0,0,1,256,136Zm-57.87,58.85a8,8,0,0,1-12.26,10.3C165.75,181.19,138.09,168,108,168s-57.75,13.19-77.87,37.15a8,8,0,0,1-12.25-10.3c14.94-17.78,33.52-30.41,54.17-37.17a68,68,0,1,1,71.9,0C164.6,164.44,183.18,177.07,198.13,194.85ZM108,152a52,52,0,1,0-52-52A52.06,52.06,0,0,0,108,152Z"></path></symbol><symbol id="ph-book-open" viewBox="0 0 256 256"><path d="M232,48H160a40,40,0,0,0-32,16A40,40,0,0,0,96,48H24a8,8,0,0,0-8,8V200a8,8,0,0,0,8,8H96a24,24,0,0,1,24,24,8,8,0,0,0,16,0,24,24,0,0,1,24-24h72a8,8,0,0,0,8-8V56A8,8,0,0,0,232,48ZM96,192H32V64H96a24,24,0,0,1,24,24V200A39.81,39.81,0,0,0,96,192Zm128,0H160a39.81,39.81,0,0,0-24,8V88a24,24,0,0,1,24-24h64Z"></path></symbol><symbol id="ph-hexagon" viewBox="0 0 256 256"><path d="M223.68,66.15,135.68,18h0a15.88,15.88,0,0,0-15.36,0l-88,48.17a16,16,0,0,0-8.32,14v95.64a16,16,0,0,0,8.32,14l88,48.17a15.88,15.88,0,0,0,15.36,0l88-48.17a16,16,0,0,0,8.32-14V80.18A16,16,0,0,0,223.68,66.15ZM216,175.82,128,224,40,175.82V80.18L128,32h0l88,48.17Z"></path></symbol><symbol id="ph-house" viewBox="0 0 256 256"><path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V160h32v56a8,8,0,0,0,8,8h64a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48V120l80-80,80,80Z"></path></symbol><symbol id="ph-film-strip" viewBox="0 0 256 256"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM40,88h80v80H40Zm96-16V56h32V72Zm-16,0H88V56h32Zm0,112v16H88V184Zm16,0h32v16H136Zm0-16V88h80v80Zm80-96H184V56h32ZM72,56V72H40V56ZM40,184H72v16H40Zm176,16H184V184h32v16Z"></path></symbol><symbol id="ph-map-trifold" viewBox="0 0 256 256"><path d="M228.92,49.69a8,8,0,0,0-6.86-1.45L160.93,63.52,99.58,32.84a8,8,0,0,0-5.52-.6l-64,16A8,8,0,0,0,24,56V200a8,8,0,0,0,9.94,7.76l61.13-15.28,61.35,30.68A8.15,8.15,0,0,0,160,224a8,8,0,0,0,1.94-.24l64-16A8,8,0,0,0,232,200V56A8,8,0,0,0,228.92,49.69ZM104,52.94l48,24V203.06l-48-24ZM40,62.25l48-12v127.5l-48,12Zm176,131.5-48,12V78.25l48-12Z"></path></symbol><symbol id="ph-trophy" viewBox="0 0 256 256"><path d="M232,64H208V48a8,8,0,0,0-8-8H56a8,8,0,0,0-8,8V64H24A16,16,0,0,0,8,80V96a40,40,0,0,0,40,40h3.65A80.13,80.13,0,0,0,120,191.61V216H96a8,8,0,0,0,0,16h64a8,8,0,0,0,0-16H136V191.58c31.94-3.23,58.44-25.64,68.08-55.58H208a40,40,0,0,0,40-40V80A16,16,0,0,0,232,64ZM48,120A24,24,0,0,1,24,96V80H48v32q0,4,.39,8Zm144-8.9c0,35.52-29,64.64-64,64.9a64,64,0,0,1-64-64V56H192ZM232,96a24,24,0,0,1-24,24h-.5a81.81,81.81,0,0,0,.5-8.9V80h24Z"></path></symbol><symbol id="ph-dice-five" viewBox="0 0 256 256"><path d="M192,32H64A32,32,0,0,0,32,64V192a32,32,0,0,0,32,32H192a32,32,0,0,0,32-32V64A32,32,0,0,0,192,32Zm16,160a16,16,0,0,1-16,16H64a16,16,0,0,1-16-16V64A16,16,0,0,1,64,48H192a16,16,0,0,1,16,16ZM104,92A12,12,0,1,1,92,80,12,12,0,0,1,104,92Zm72,0a12,12,0,1,1-12-12A12,12,0,0,1,176,92Zm-72,72a12,12,0,1,1-12-12A12,12,0,0,1,104,164Zm36-36a12,12,0,1,1-12-12A12,12,0,0,1,140,128Zm36,36a12,12,0,1,1-12-12A12,12,0,0,1,176,164Z"></path></symbol><symbol id="ph-joystick" viewBox="0 0 256 256"><path d="M208,144H136V95.19a40,40,0,1,0-16,0V144H48a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V160A16,16,0,0,0,208,144ZM104,56a24,24,0,1,1,24,24A24,24,0,0,1,104,56ZM208,208H48V160H208v48Zm-40-96h32a8,8,0,0,1,0,16H168a8,8,0,0,1,0-16Z"></path></symbol><symbol id="ph-users-four" viewBox="0 0 256 256"><path d="M27.2,126.4a8,8,0,0,0,11.2-1.6,52,52,0,0,1,83.2,0,8,8,0,0,0,11.2,1.59,7.73,7.73,0,0,0,1.59-1.59h0a52,52,0,0,1,83.2,0,8,8,0,0,0,12.8-9.61A67.85,67.85,0,0,0,203,93.51a40,40,0,1,0-53.94,0,67.27,67.27,0,0,0-21,14.31,67.27,67.27,0,0,0-21-14.31,40,40,0,1,0-53.94,0A67.88,67.88,0,0,0,25.6,115.2,8,8,0,0,0,27.2,126.4ZM176,40a24,24,0,1,1-24,24A24,24,0,0,1,176,40ZM80,40A24,24,0,1,1,56,64,24,24,0,0,1,80,40ZM203,197.51a40,40,0,1,0-53.94,0,67.27,67.27,0,0,0-21,14.31,67.27,67.27,0,0,0-21-14.31,40,40,0,1,0-53.94,0A67.88,67.88,0,0,0,25.6,219.2a8,8,0,1,0,12.8,9.6,52,52,0,0,1,83.2,0,8,8,0,0,0,11.2,1.59,7.73,7.73,0,0,0,1.59-1.59h0a52,52,0,0,1,83.2,0,8,8,0,0,0,12.8-9.61A67.85,67.85,0,0,0,203,197.51ZM80,144a24,24,0,1,1-24,24A24,24,0,0,1,80,144Zm96,0a24,24,0,1,1-24,24A24,24,0,0,1,176,144Z"></path></symbol><symbol id="ph-bookmark-simple" viewBox="0 0 256 256"><path d="M184,32H72A16,16,0,0,0,56,48V224a8,8,0,0,0,12.24,6.78L128,193.43l59.77,37.35A8,8,0,0,0,200,224V48A16,16,0,0,0,184,32Zm0,177.57-51.77-32.35a8,8,0,0,0-8.48,0L72,209.57V48H184Z"></path></symbol><symbol id="ph-sign-out" viewBox="0 0 256 256"><path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"/></symbol><symbol id="ph-caret-down" viewBox="0 0 256 256"><path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"/></symbol><symbol id="ph-pencil-simple" viewBox="0 0 256 256"><path d="M227.31,73.37,182.63,28.68a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H92.69A15.86,15.86,0,0,0,104,219.31L227.31,96a16,16,0,0,0,0-22.63ZM92.69,208H48V163.31l88-88L180.69,120ZM192,108.68,147.31,64l24-24L216,84.68Z"/></symbol><symbol id="ph-shield-check" viewBox="0 0 256 256"><path d="M208,40H48A16,16,0,0,0,32,56v56c0,52.72,25.52,84.67,46.93,102.19,23.06,18.86,46,25.26,47,25.53a8,8,0,0,0,4.2,0c1-.27,23.91-6.67,47-25.53C198.48,196.67,224,164.72,224,112V56A16,16,0,0,0,208,40Zm0,72c0,37.07-13.66,67.16-40.6,89.42A129.3,129.3,0,0,1,128,223.62a128.25,128.25,0,0,1-38.92-21.81C61.82,179.51,48,149.3,48,112l0-56,160,0ZM82.34,141.66a8,8,0,0,1,11.32-11.32L112,148.69l50.34-50.35a8,8,0,0,1,11.32,11.32l-56,56a8,8,0,0,1-11.32,0Z"/></symbol><symbol id="ph-sparkle" viewBox="0 0 256 256"><path d="M197.58,129.06,146,110l-19-51.62a15.92,15.92,0,0,0-29.88,0L78,110l-51.62,19a15.92,15.92,0,0,0,0,29.88L78,178l19,51.62a15.92,15.92,0,0,0,29.88,0L146,178l51.62-19a15.92,15.92,0,0,0,0-29.88ZM137,164.22a8,8,0,0,0-4.74,4.74L112,223.85,91.78,169A8,8,0,0,0,87,164.22L32.15,144,87,123.78A8,8,0,0,0,91.78,119L112,64.15,132.22,119a8,8,0,0,0,4.74,4.74L191.85,144ZM144,40a8,8,0,0,1,8-8h16V16a8,8,0,0,1,16,0V32h16a8,8,0,0,1,0,16H184V64a8,8,0,0,1-16,0V48H152A8,8,0,0,1,144,40ZM248,88a8,8,0,0,1-8,8h-8v8a8,8,0,0,1-16,0V96h-8a8,8,0,0,1,0-16h8V72a8,8,0,0,1,16,0v8h8A8,8,0,0,1,248,88Z"/></symbol><symbol id="ph-heart" viewBox="0 0 256 256"><path d="M178,40c-20.65,0-38.73,8.88-50,23.89C116.73,48.88,98.65,40,78,40a62.07,62.07,0,0,0-62,62c0,70,103.79,126.66,108.21,129a8,8,0,0,0,7.58,0C136.21,228.66,240,172,240,102A62.07,62.07,0,0,0,178,40ZM128,214.8C109.74,204.16,32,155.69,32,102A46.06,46.06,0,0,1,78,56c19.45,0,35.78,10.36,42.6,27a8,8,0,0,0,14.8,0c6.82-16.67,23.15-27,42.6-27a46.06,46.06,0,0,1,46,46C224,155.61,146.24,204.15,128,214.8Z"/></symbol><symbol id="ph-share-fat" viewBox="0 0 256 256"><path d="M237.66,106.35l-80-80A8,8,0,0,0,144,32V72.35c-25.94,2.22-54.59,14.92-78.16,34.91-28.38,24.08-46.05,55.11-49.76,87.37a12,12,0,0,0,20.68,9.58h0c11-11.71,50.14-48.74,107.24-52V192a8,8,0,0,0,13.66,5.65l80-80A8,8,0,0,0,237.66,106.35ZM160,172.69V144a8,8,0,0,0-8-8c-28.08,0-55.43,7.33-81.29,21.8a196.17,196.17,0,0,0-36.57,26.52c5.8-23.84,20.42-46.51,42.05-64.86C99.41,99.77,127.75,88,152,88a8,8,0,0,0,8-8V51.32L220.69,112Z"/></symbol><symbol id="ph-dots-three" viewBox="0 0 256 256"><path d="M140,128a12,12,0,1,1-12-12A12,12,0,0,1,140,128Zm56-12a12,12,0,1,0,12,12A12,12,0,0,0,196,116ZM60,116a12,12,0,1,0,12,12A12,12,0,0,0,60,116Z"/></symbol><symbol id="ph-image" viewBox="0 0 256 256"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,16V158.75l-26.07-26.06a16,16,0,0,0-22.63,0l-20,20-44-44a16,16,0,0,0-22.62,0L40,149.37V56ZM40,172l52-52,80,80H40Zm176,28H194.63l-36-36,20-20L216,181.38V200ZM144,100a12,12,0,1,1,12,12A12,12,0,0,1,144,100Z"/></symbol><symbol id="ph-paper-plane-tilt" viewBox="0 0 256 256"><path d="M227.32,28.68a16,16,0,0,0-15.66-4.08l-.15,0L19.57,82.84a16,16,0,0,0-2.49,29.8L102,154l41.3,84.87A15.86,15.86,0,0,0,157.74,248q.69,0,1.38-.06a15.88,15.88,0,0,0,14-11.51l58.2-191.94c0-.05,0-.1,0-.15A16,16,0,0,0,227.32,28.68ZM157.83,231.85l-.05.14,0-.07-40.06-82.3,48-48a8,8,0,0,0-11.31-11.31l-48,48L24.08,98.25l-.07,0,.14,0L216,40Z"/></symbol><symbol id="ph-check" viewBox="0 0 256 256"><path d="M229.66,77.66l-128,128a8,8,0,0,1-11.32,0l-56-56a8,8,0,0,1,11.32-11.32L96,188.69,218.34,66.34a8,8,0,0,1,11.32,11.32Z"/></symbol><symbol id="ph-currency-eur" viewBox="0 0 256 256"><path d="M190,192.33a8,8,0,0,1-.63,11.3A80,80,0,0,1,56.4,152H40a8,8,0,0,1,0-16H56V120H40a8,8,0,0,1,0-16H56.4A80,80,0,0,1,189.34,52.37,8,8,0,0,1,178.66,64.3,64,64,0,0,0-106.14,104H136a8,8,0,0,1,0,16H72v16h48a8,8,0,0,1,0,16H72.52a64,64,0,0,0,106.14,39.71A8,8,0,0,1,190,192.33Z"/></symbol></svg>
<!-- HNT.ROCKS navigation: routes checked against HNT React menu and Laravel routes -->
<aside aria-label="HNT.ROCKS Seitenmenü" class="game-rail">
<a class="rail-brand icon-btn nav-link" href="{{ route('next.feed') }}" aria-label="HNT.ROCKS – Feed" data-tooltip="HNT.ROCKS – Feed" data-description="Zum Community-Feed"><span class="hnt-rail-wordmark" aria-hidden="true">HR:<span class="hnt-rail-backslash">\</span></span></a>
<nav aria-label="Hauptnavigation" class="rail-games">
<a class="game-button icon-btn nav-link is-active" href="{{ route('next.feed') }}" aria-label="Feed" data-tooltip="Feed" data-description="Community-Feed"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-house"></use></svg></a>
<a class="game-button icon-btn nav-link" href="https://hnt.rocks/teams" aria-label="Teams" data-tooltip="Teams" data-description="Teams und Gruppen"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg></a>
<a class="game-button icon-btn nav-link" href="https://hnt.rocks/cups" aria-label="Cups" data-tooltip="Cups" data-description="Wettbewerbe und Cups"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg></a>
<a class="game-button icon-btn nav-link" href="https://hnt.rocks/ready-lobbies" aria-label="Ready Lobbies" data-tooltip="Ready Lobbies" data-description="Mitspieler finden"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-four"></use></svg></a>
<a class="game-button icon-btn nav-link" href="https://hnt.rocks/rocks" aria-label="Rocks" data-tooltip="Rocks" data-description="Rocks, Level und Belohnungen"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-hexagon"></use></svg></a>
<button type="button" class="game-button add-game icon-btn more-toggle" id="moreMenuToggle" aria-expanded="false" aria-controls="moreMenu" aria-label="Weitere Bereiche" title="Weitere Bereiche" data-tooltip="Weitere Bereiche" data-description="Mitglieder, gespeicherte Beiträge und mehr"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-plus"></use></svg></button>
</nav>
<div class="rail-bottom">
<a class="rail-bottom-link icon-btn nav-link" href="https://hnt.rocks/messages" aria-label="Nachrichten" data-tooltip="Nachrichten" data-description="Private Nachrichten"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-chat-circle-dots"></use></svg></a>
<a class="rail-bottom-link icon-btn nav-link" href="https://hnt.rocks/settings" aria-label="Einstellungen" data-tooltip="Einstellungen" data-description="Konto und Einstellungen"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-gear-six"></use></svg></a>
</div>
</aside>
<nav id="moreMenu" class="more-menu" aria-label="Weitere HNT.ROCKS-Bereiche" hidden>
<div class="more-menu-heading">MEHR ENTDECKEN</div>
<a href="https://hnt.rocks/members" class="more-menu-item" title="Mitglieder"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg><span class="more-menu-item-copy"><strong>Mitglieder</strong><small>Hunter-Community</small></span></a>
<a href="https://hnt.rocks/saved" class="more-menu-item" title="Gespeicherte Beiträge"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-bookmark-simple"></use></svg><span class="more-menu-item-copy"><strong>Gespeicherte Beiträge</strong><small>Deine gespeicherten Posts</small></span></a>
<a href="https://hnt.rocks/hall-of-fame" class="more-menu-item" title="Hall of Fame"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg><span class="more-menu-item-copy"><strong>Hall of Fame</strong><small>Ranglisten und Gewinner</small></span></a>
<a href="https://hnt.rocks/loadout-challenges" class="more-menu-item" title="Loadout Challenges"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg><span class="more-menu-item-copy"><strong>Loadout Challenges</strong><small>Waffen-Challenges</small></span></a>
<a href="https://hnt.rocks/lfg" class="more-menu-item" title="Spielersuche"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-target"></use></svg><span class="more-menu-item-copy"><strong>Spielersuche</strong><small>Hunt-Spieler finden</small></span></a>
<a href="/profile" class="more-menu-item" title="Profil"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-four"></use></svg><span class="more-menu-item-copy"><strong>Profil</strong><small>Deine Hunter-Seite</small></span></a>
</nav>
<header aria-label="HNT.ROCKS Topbar" class="topbar">
<div class="header-left">
<a class="head-icon icon-btn nav-link is-active" href="{{ route('next.feed') }}" aria-label="Feed" data-tooltip="Feed" data-description="Community-Feed"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-house"></use></svg></a>
<a class="head-icon icon-btn nav-link" href="https://hnt.rocks/moments" aria-label="Moments" data-tooltip="Moments" data-description="Videos und Highlights"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-film-strip"></use></svg></a>
<a class="head-icon icon-btn nav-link" href="https://hnt.rocks/maps" aria-label="Maps" data-tooltip="Maps" data-description="Interaktive Hunt-Karten"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-map-trifold"></use></svg></a>
<a class="head-icon icon-btn nav-link" href="https://hnt.rocks/guides" aria-label="Guides" data-tooltip="Guides" data-description="Hunt Guides"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-graduation-cap"></use></svg></a>
<div aria-label="Hunter-Netzwerk und Erfahrungspunkte" class="user-currency hnt-hud-currency"><button type="button" class="hnt-hud-trigger hnt-hud-online-trigger" id="hunterOnlineToggle" title="Online-Hunter ansehen" aria-label="12 Hunter online – Hunter-Netzwerk öffnen" aria-expanded="false" aria-controls="hunterOnlinePanel" aria-haspopup="true"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg><span class="hnt-hud-online-led" aria-hidden="true"></span><span>12 Online</span></button><span class="currency-divider" aria-hidden="true"></span><button type="button" class="hnt-hud-trigger hnt-hud-xp-trigger" id="hunterXpToggle" title="Hunter Journal öffnen" aria-label="Hunter Journal – Erfahrungspunkte und Auszeichnungen öffnen" aria-expanded="false" aria-controls="hunterJournalPanel" aria-haspopup="true"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-lightning"></use></svg><span id="hunterHudXpValue">23.245 XP</span></button></div>
</div>
<div class="top-center-actions" aria-label="Social Navigation">
<button type="button" id="socialToggle-notifications" class="head-icon top-mid-icon icon-btn nav-link social-trigger" aria-label="Benachrichtigungen" title="Benachrichtigungen" aria-haspopup="true" aria-controls="socialPanel-notifications" aria-expanded="false" data-tooltip="Benachrichtigungen" data-description="Deine neuen Aktivitäten" data-social="notifications"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-bell"></use></svg><span aria-hidden="true" id="socialBadge-notifications" class="social-badge">3</span></button>
<button type="button" id="socialToggle-messages" class="head-icon top-mid-icon icon-btn nav-link social-trigger" aria-label="Nachrichten" title="Nachrichten" aria-haspopup="true" aria-controls="socialPanel-messages" aria-expanded="false" data-tooltip="Nachrichten" data-description="Deine privaten Nachrichten" data-social="messages"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-chat-circle-dots"></use></svg><span aria-hidden="true" id="socialBadge-messages" class="social-badge">2</span></button>
<button type="button" id="socialToggle-friends" class="head-icon top-mid-icon icon-btn nav-link social-trigger" aria-label="Freundschaftsanfragen" title="Freundschaftsanfragen" aria-haspopup="true" aria-controls="socialPanel-friends" aria-expanded="false" data-tooltip="Freundschaftsanfragen" data-description="Neue Kontakte" data-social="friends"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-user-plus"></use></svg><span aria-hidden="true" id="socialBadge-friends" class="social-badge">2</span></button>
</div>

<section class="social-popover" id="socialPanel-notifications" role="region" aria-label="Benachrichtigungen" data-panel="notifications" hidden>
  <div class="social-topline"></div>
  <div class="social-panel-head">
    <div class="social-overline"><span class="social-live-dot"></span> HUNTER-FUNK </div>
    <div class="social-heading"><div><h2>Benachrichtigungen</h2><p><span id="socialCount-notifications">3 neue Aktivitäten</span></p></div><span class="social-heading-mark"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-bell"></use></svg></span></div>
  </div>
  <div class="social-panel-tools"><span><i class="social-filter-dot"></i> NEUE AKTIVITÄTEN</span><button id="socialMarkRead" type="button">Alle gelesen</button></div>
  <div class="social-items" id="socialNotices">
    <a href="https://hnt.rocks/maps" class="social-entry is-unread" data-notice="1"><span class="social-event-icon event-map"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-map-trifold"></use></svg></span><span class="social-entry-copy"><strong>Neuer Kassenspot entdeckt</strong><span>Ein neuer Fund wurde auf der Karte bestätigt.</span><small>Maps <em>·</em> vor 8 Min.</small></span><i class="social-unread-dot"></i></a>
    <a href="https://hnt.rocks/moments" class="social-entry is-unread" data-notice="1"><span class="social-event-icon event-moment"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-film-strip"></use></svg></span><span class="social-entry-copy"><strong>Dein Moment bekommt Aufmerksamkeit</strong><span>Jemand hat auf einen Moment reagiert.</span><small>Moments <em>·</em> vor 36 Min.</small></span><i class="social-unread-dot"></i></a>
    <a href="https://hnt.rocks/cups" class="social-entry is-unread" data-notice="1"><span class="social-event-icon event-cup"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-trophy"></use></svg></span><span class="social-entry-copy"><strong>Last Blood Cup</strong><span>Es gibt Neuigkeiten zum Wettbewerb.</span><small>Cups <em>·</em> vor 2 Std.</small></span><i class="social-unread-dot"></i></a>
  </div>
  <a href="https://hnt.rocks/notifications" class="social-footer-link"><span>Alle Benachrichtigungen</span><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-up-right"></use></svg></a>
</section>
<section class="social-popover" id="socialPanel-messages" role="region" aria-label="Nachrichten" data-panel="messages" hidden>
  <div class="social-topline"></div>
  <div class="social-panel-head">
    <div class="social-overline"><span class="social-live-dot"></span> FUNKKANAL </div>
    <div class="social-heading"><div><h2>Nachrichten</h2><p><span id="socialCount-messages">2 ungelesene Chats</span></p></div><span class="social-heading-mark"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-chat-circle-dots"></use></svg></span></div>
  </div>
  <div class="social-message-tabs" role="group" aria-label="Nachrichtenfilter"><button class="social-tab is-selected" type="button" data-message-filter="all" aria-pressed="true">Alle</button><button class="social-tab" type="button" data-message-filter="unread" aria-pressed="false">Ungelesen <span id="socialUnreadMsgCount">2</span></button></div>
  <div class="social-items social-message-items" id="socialMessagesList">
    <a class="social-entry social-msg-entry is-unread" data-unread="1" href="https://hnt.rocks/messages"><span class="social-avatar avatar-amber">BN<span class="avatar-online"></span></span><span class="social-entry-copy"><strong>BayouNomad</strong><span>Kommst du heute noch für eine Runde?</span><small>vor 5 Min. <em>·</em> Ready Lobby</small></span><i class="social-unread-dot"></i></a>
    <a class="social-entry social-msg-entry is-unread" data-unread="1" href="https://hnt.rocks/messages"><span class="social-avatar avatar-violet">RH<span class="avatar-online"></span></span><span class="social-entry-copy"><strong>RavenHunter</strong><span>Ich hab dir den Guide geschickt.</span><small>vor 29 Min. <em>·</em> Privat</small></span><i class="social-unread-dot"></i></a>
    <a class="social-entry social-msg-entry" data-unread="0" href="https://hnt.rocks/messages"><span class="social-avatar avatar-teal">MS</span><span class="social-entry-copy"><strong>MapScout</strong><span>Danke für den Tipp mit den Spawns!</span><small>Gestern <em>·</em> Privat</small></span></a>
  </div>
  <div id="socialMessageEmpty" class="social-empty" hidden><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-chat-circle-dots"></use></svg><strong>Alles gelesen</strong><span>Du hast keine ungelesenen Nachrichten.</span></div>
  <a href="https://hnt.rocks/messages" class="social-footer-link"><span>Posteingang öffnen</span><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-up-right"></use></svg></a>
</section>
<section class="social-popover" id="socialPanel-friends" role="region" aria-label="Freundschaftsanfragen" data-panel="friends" hidden>
  <div class="social-topline"></div>
  <div class="social-panel-head">
    <div class="social-overline"><span class="social-live-dot"></span> HUNTER-KONTAKTE </div>
    <div class="social-heading"><div><h2>Freundschaftsanfragen</h2><p><span id="socialCount-friends">2 offene Anfragen</span></p></div><span class="social-heading-mark"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-user-plus"></use></svg></span></div>
  </div>
  <div class="social-panel-tools"><span><i class="social-filter-dot"></i> WARTEN AUF ANTWORT</span></div>
  <div class="social-items social-request-list" id="socialRequests">
    <div class="social-request" data-request="1"><span class="social-avatar avatar-violet">NW</span><div class="social-request-details"><div class="social-request-info"><strong>NightWraith</strong><small>Prestige 12 <em>·</em> PC</small></div><p>Möchte mit dir befreundet sein</p><div class="social-request-actions"><button class="social-accept" data-respond="accept" type="button"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-shield-check"></use></svg> Annehmen</button><button class="social-decline" data-respond="decline" type="button">Ablehnen</button></div><span class="social-request-status" role="status" aria-live="polite" hidden></span></div></div>
    <div class="social-request" data-request="1"><span class="social-avatar avatar-amber">SC</span><div class="social-request-details"><div class="social-request-info"><strong>SwampCrow</strong><small>Bloodline 89 <em>·</em> Xbox</small></div><p>Möchte mit dir befreundet sein</p><div class="social-request-actions"><button class="social-accept" data-respond="accept" type="button"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-shield-check"></use></svg> Annehmen</button><button class="social-decline" data-respond="decline" type="button">Ablehnen</button></div><span class="social-request-status" role="status" aria-live="polite" hidden></span></div></div>
  </div>
  <div class="social-empty" id="socialFriendEmpty" hidden><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-users-three"></use></svg><strong>Keine offenen Anfragen</strong><span>Dein Posteingang ist aufgeräumt.</span></div>
  <a href="https://hnt.rocks/profile/friends" class="social-footer-link"><span>Freunde verwalten</span><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-up-right"></use></svg></a>
</section>
<button aria-label="Suche" class="global-search" id="searchTrigger" title="HNT.ROCKS durchsuchen"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-magnifying-glass"></use></svg><span>HNT.ROCKS durchsuchen …</span></button>
<div class="hnt-language" id="hntLanguage">
  <button type="button" class="hnt-language-toggle" id="hntLanguageToggle" aria-label="Sprache wählen, aktuell Deutsch" aria-haspopup="menu" aria-controls="hntLanguageMenu" aria-expanded="false" title="Sprache wählen">
    <span class="hnt-language-code" id="hntLanguageCurrent">DE</span>
    <svg class="ph hnt-language-caret" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-caret-down"></use></svg>
  </button>
  <div class="hnt-language-menu" id="hntLanguageMenu" role="menu" aria-label="Sprache wählen" hidden>
    <p class="hnt-language-label">SPRACHE / LANGUAGE</p>
    <button type="button" role="menuitemradio" aria-checked="true" class="hnt-language-option is-current" data-lang="de"><span class="hnt-language-short">DE</span><span>Deutsch</span><svg class="ph hnt-language-check" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-check"></use></svg></button>
    <button type="button" role="menuitemradio" aria-checked="false" class="hnt-language-option" data-lang="en"><span class="hnt-language-short">EN</span><span>English</span><svg class="ph hnt-language-check" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-check"></use></svg></button>
    <button type="button" role="menuitemradio" aria-checked="false" class="hnt-language-option" data-lang="es"><span class="hnt-language-short">ES</span><span>Español</span><svg class="ph hnt-language-check" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-check"></use></svg></button>
    <button type="button" role="menuitemradio" aria-checked="false" class="hnt-language-option" data-lang="ru"><span class="hnt-language-short">RU</span><span>Русский</span><svg class="ph hnt-language-check" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-check"></use></svg></button>
  </div>
</div>
<button type="button" id="accountMenuToggle" class="top-user icon-btn account-toggle" aria-label="Kontomenü von {{ $viewer->name }} öffnen" aria-haspopup="true" aria-controls="accountMenu" aria-expanded="false" title="{{ $viewer->name }} – Mein Konto">
<img alt="{{ $viewer->name }} – Profilbild" src="/build/next/hntv3-assets/top-avatar.png"/>
<span class="account-toggle-ring" aria-hidden="true"></span>
<span class="account-toggle-caret" aria-hidden="true"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-caret-down"></use></svg></span>
</button>
<section class="account-menu" id="accountMenu" aria-label="Hunter-Kontomenü" hidden>
  <div class="account-menu-accent" aria-hidden="true"></div>
  <div class="account-menu-head">
    <div class="account-menu-eyebrow"><span class="account-menu-eyebrow-icon"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-shield-check"></use></svg></span> DEIN HUNTER-ACCOUNT <span class="account-menu-dot" aria-hidden="true"></span></div>
    <div class="account-identity">
      <div class="account-portrait"><img src="/build/next/hntv3-assets/top-avatar.png" alt=""/><i class="account-online-dot" aria-hidden="true"></i></div>
      <div class="account-identity-copy"><strong>{{ $viewer->name }}</strong><span><i aria-hidden="true" class="status-light"></i> Online · HNT.ROCKS</span></div>
    </div>
    <a class="account-profile-link" href="/profile" title="Dein Profil auf HNT.ROCKS öffnen">Hunter-Profil ansehen <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
  </div>
  <div class="account-rank">
    <div class="account-rank-title"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg> <strong>HUNTER PRESTIGE</strong></span><b>15</b></div>
    <div class="account-rank-track" role="img" aria-label="Illustrativer Prestige-Fortschritt: 64 Prozent"><span></span></div>
    <div class="account-stats"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-clock"></use></svg> <strong>1.284 h</strong> <small>Spielzeit</small></span><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-lightning"></use></svg> <strong>23.245</strong> <small>XP</small></span></div>
  </div>
  <nav class="account-menu-links" aria-label="Konto-Schnellzugriffe">
    <a href="https://hnt.rocks/profile/edit" class="account-menu-link" title="Profil bearbeiten"><span class="account-link-icon"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-pencil-simple"></use></svg></span><span class="account-link-text">Profil bearbeiten<small>Avatar, Bio &amp; Hunter-Daten</small></span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
    <a href="https://hnt.rocks/saved" class="account-menu-link" title="Gespeicherte Beiträge"><span class="account-link-icon"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-bookmark-simple"></use></svg></span><span class="account-link-text">Gespeicherte Beiträge<small>Deine gemerkten Posts</small></span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
    <a href="https://hnt.rocks/profile/friends" class="account-menu-link" title="Freunde"><span class="account-link-icon"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg></span><span class="account-link-text">Freunde<small>Kontakte &amp; Anfragen</small></span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
    <a href="https://hnt.rocks/rocks" class="account-menu-link" title="Rocks und Belohnungen"><span class="account-link-icon"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-sparkle"></use></svg></span><span class="account-link-text">Rocks &amp; Belohnungen<small>Level, Badges &amp; Shop</small></span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
    <a href="https://hnt.rocks/settings" class="account-menu-link" title="Einstellungen"><span class="account-link-icon"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-gear-six"></use></svg></span><span class="account-link-text">Einstellungen<small>Privatsphäre &amp; Account</small></span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a>
  </nav>
  <div class="account-menu-footer">
    <button type="button" id="accountLogoutDemo" class="account-signout" title="Abmelden"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-sign-out"></use></svg> <span>Abmelden</span></button>
    
  </div>
</section>
</header>

<main class="main-content feed-main" id="feedTop">
  <div class="feed-page-title"><div><div class="feed-kicker"><span class="feed-live-signal"></span> HNT.ROCKS / COMMUNITY</div><h1>Feed<span class="title-ember">.</span></h1><p>{{ \App\Http\Controllers\Next\NextText::get('description') }}</p></div>
  <div class="feed-heading-actions"><button class="btn-compose" type="button" id="headerPostBtn"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-pencil-simple"></use></svg> {{ \App\Http\Controllers\Next\NextText::get('create') }}</button></div></div>
  <div class="feed-layout">
    <div class="feed-column">
      <nav class="feed-filters" aria-label="Beiträge filtern">
<a class="feed-filter @if($feedFilter === 'all') is-selected @endif" data-next-server-filter href="{{ route('next.feed') }}" aria-current="{{ $feedFilter === 'all' ? 'page' : 'false' }}">{{ \App\Http\Controllers\Next\NextText::get('for_you') }}</a>
<a class="feed-filter @if($feedFilter === 'friends') is-selected @endif" data-next-server-filter href="{{ route('next.feed', ['filter'=>'friends']) }}" aria-current="{{ $feedFilter === 'friends' ? 'page' : 'false' }}">{{ \App\Http\Controllers\Next\NextText::get('friends') }}</a>
<a class="feed-filter @if($feedFilter === 'teams') is-selected @endif" data-next-server-filter href="{{ route('next.feed', ['filter'=>'teams']) }}" aria-current="{{ $feedFilter === 'teams' ? 'page' : 'false' }}">{{ \App\Http\Controllers\Next\NextText::get('teams') }}</a>
<a class="feed-filter @if($feedFilter === 'media') is-selected @endif" data-next-server-filter href="{{ route('next.feed', ['filter'=>'media']) }}" aria-current="{{ $feedFilter === 'media' ? 'page' : 'false' }}">{{ \App\Http\Controllers\Next\NextText::get('media') }}</a>
<a class="feed-filter @if($feedFilter === 'mentions') is-selected @endif" data-next-server-filter href="{{ route('next.feed', ['filter'=>'mentions']) }}" aria-current="{{ $feedFilter === 'mentions' ? 'page' : 'false' }}">{{ \App\Http\Controllers\Next\NextText::get('mentions') }}</a>
<span class="feed-filter-right"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-sparkle"></use></svg> {{ \App\Http\Controllers\Next\NextText::get('community') }}</span>
</nav>
      <div class="feed-composer" id="feedComposer"><img src="{{ $viewer->avatarUrl() }}" alt="Dein Hunter-Avatar"/><button id="composerStart" type="button" class="composer-prompt">{{ \App\Http\Controllers\Next\NextText::get('composer') }}</button><div class="composer-tools"><button id="composerMediaBtn" type="button" title="Medienbeitrag erstellen"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-image"></use></svg><span>{{ \App\Http\Controllers\Next\NextText::get('picture') }}</span></button><button id="composerMomentBtn" type="button" title="Moments öffnen"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-film-strip"></use></svg><span>{{ \App\Http\Controllers\Next\NextText::get('moment') }}</span></button></div></div>
      <div class="feed-list" id="feedList">
@forelse($posts as $post)
@include('next.partials.hntv3-post', ['post'=>$post, 'feedFilter'=>$feedFilter])
@empty
@endforelse
</div>
@if($hasMoreFeedPosts && $nextFeedPage <= 50)
<a class="hnt-live-load-more" id="hntNextLoadMore" href="{{ route('next.feed', array_filter(['filter'=>$feedFilter === 'all' ? null : $feedFilter, 'page'=>$nextFeedPage])) }}">{{ \App\Http\Controllers\Next\NextText::get('more') }}</a>
@endif
<div class="feed-empty" id="feedEmpty" @unless($posts->isEmpty()) hidden @endunless><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg><h3>{{ \App\Http\Controllers\Next\NextText::get('empty_title') }}</h3><p>{{ \App\Http\Controllers\Next\NextText::get('empty') }}</p><button type="button" id="resetFeed">{{ \App\Http\Controllers\Next\NextText::get('reset') }}</button></div>
      <div class="feed-bottom-note">{{ \App\Http\Controllers\Next\NextText::get('up_to_date') }} <span>·</span> <strong>{{ \App\Http\Controllers\Next\NextText::get('goodbye') }}</strong></div>
    </div>
    <aside class="feed-widgets" aria-label="Community-Widgets">
      <section class="widget hunter-widget hnt-prestige-widget" id="hntPrestigeWidget" aria-label="Dein Hunter-Level und Prestige">
        <div class="widget-top"><h2>Dein Hunter</h2><a href="/profile" aria-label="Dein Profil öffnen">Profil ansehen <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div>
        <div class="hunter-profile">
          <img class="hunter-pic" src="{{ $viewer->avatarUrl() }}" alt="Hunter Profilbild"/>
          <div class="hunter-profile-copy"><strong>{{ $viewer->name }}</strong><small><span class="online-dot"></span> Online · PC Hunter</small><span class="rank-sub" id="hntPrestigeRank">PRESTIGE 15 · LEVEL 50</span></div>
          <div class="hnt-prestige-emblem" aria-label="Prestige-Badge 15"><img class="hunter-emblem" src="/build/next/hntv3-assets/prestige-skull.png" alt=""/><span id="hntPrestigeEmblemNumber">15</span></div>
        </div>
        <div class="hunter-progress hnt-prestige-progress">
          <span id="hntPrestigeProgressLabel">LEVEL 50 / 50</span><span id="hntPrestigeProgressPercent">MAX. LEVEL</span>
          <div role="progressbar" aria-label="Hunter-Level-Fortschritt" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100" id="hntPrestigeProgressBar"><i id="hntPrestigeProgressFill"></i></div>
        </div>
        <p class="hnt-prestige-status" id="hntPrestigeStatus"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-seal-check"></use></svg><span>Level 50 erreicht. Prestige ist verfügbar.</span></p>
        <div class="hunter-mini-stats hnt-prestige-stats">
          <div><small><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg> Aktive Tage</small><strong>42</strong></div>
          <div><small><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-lightning"></use></svg> HNT Gesamt-XP</small><strong>23.245 <em>XP</em></strong></div>
        </div>
        <div class="hnt-prestige-action">
          <button type="button" id="hntPrestigeStart" class="hnt-prestige-start" aria-haspopup="dialog" aria-controls="hntPrestigeDialog">
            <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg>
            <span id="hntPrestigeStartText">Prestige 16 starten</span>
            <svg aria-hidden="true" class="ph hnt-prestige-arrow" viewBox="0 0 256 256"><use href="#ph-arrow-right"></use></svg>
          </button>
          <small id="hntPrestigeHint">Freiwillig · Level wird auf 1 zurückgesetzt</small>
        </div>
      </section>
      <section class="widget activity-widget" aria-labelledby="activityWidgetTitle">
        <div class="widget-top activity-widget-head">
          <h2 id="activityWidgetTitle">Activity</h2>
          <button class="activity-widget-toggle" type="button" aria-controls="activityWidgetMore" aria-expanded="false" data-activity-toggle>
            <span>Alle anzeigen</span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-right"></use></svg>
          </button>
        </div>
        <ul class="activity-widget-list" aria-label="Letzte Community-Aktivitäten">
          <li class="activity-widget-item">
            <span class="activity-avatar activity-avatar-fallback" aria-hidden="true"><i class="activity-avatar-head"></i><i class="activity-avatar-shoulders"></i><i class="activity-avatar-online"></i></span>
            <span class="activity-item-copy"><strong>Henry Dricker ist HNT.ROCKS beigetreten</strong><span>Neuer Hunter in der Community</span><time>vor 1 Stunde</time></span>
          </li>
          <li class="activity-widget-item">
            <span class="activity-avatar activity-avatar-fallback" aria-hidden="true"><i class="activity-avatar-head"></i><i class="activity-avatar-shoulders"></i><i class="activity-avatar-online"></i></span>
            <span class="activity-item-copy"><strong>Kasper ist HNT.ROCKS beigetreten</strong><span>Neuer Hunter in der Community</span><time>vor 3 Stunden</time></span>
          </li>
          <li class="activity-widget-item">
            <span class="activity-avatar activity-avatar-photo"><img src="/build/next/hntv3-assets/avatar-me.webp" alt="" loading="lazy"/></span>
            <span class="activity-item-copy"><strong>Krispie hat einen Beitrag veröffentlicht</strong><span>Neuer Community-Beitrag</span><time>vor 19 Stunden</time></span>
          </li>
        </ul>
        <ul class="activity-widget-list activity-widget-more" id="activityWidgetMore" aria-label="Weitere Community-Aktivitäten" hidden>
          <li class="activity-widget-item">
            <span class="activity-avatar activity-avatar-photo"><img src="/build/next/hntv3-assets/avatar-bayou.webp" alt="" loading="lazy"/></span>
            <span class="activity-item-copy"><strong>BayouNomad hat einen Moment geteilt</strong><span>Neuer Moment aus dem Bayou</span><time>vor 1 Tag</time></span>
          </li>
          <li class="activity-widget-item">
            <span class="activity-avatar activity-avatar-photo"><img src="/build/next/hntv3-assets/avatar-caldwell.webp" alt="" loading="lazy"/></span>
            <span class="activity-item-copy"><strong>CaldwellEnjoyer hat ein Loadout geteilt</strong><span>Neuer Community-Beitrag</span><time>vor 2 Tagen</time></span>
          </li>
        </ul>
      </section>

      <section class="widget hnt-weekly-moment-widget" aria-labelledby="hntWeeklyMomentTitle">
        <div class="widget-top hnt-weekly-moment-head">
          <h2 id="hntWeeklyMomentTitle"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-film-strip"></use></svg> Moment der Woche</h2>
          <a href="https://hnt.rocks/moments" aria-label="Alle Moments ansehen">Alle Moments <svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-up-right"></use></svg></a>
        </div>
        <a class="hnt-weekly-moment-preview" href="https://hnt.rocks/moments" aria-label="Moment der Woche – Moments öffnen">
          <img src="/build/next/hntv3-assets/bloodmoon-bg.webp" alt="Dunkler Bayou mit einem riesigen roten Mond" loading="lazy"/>
          <span class="hnt-weekly-moment-tag"><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-sparkle"></use></svg> COMMUNITY HIGHLIGHT</span>
          <span class="hnt-weekly-moment-play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.5a1 1 0 0 1 1.52-.85l10 6.5a1 1 0 0 1 0 1.7l-10 6.5A1 1 0 0 1 8 18.5z"/></svg></span>
          <span class="hnt-weekly-moment-preview-footer"><span>Moment entdecken</span><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-up-right"></use></svg></span>
        </a>
        <div class="hnt-weekly-moment-info">
          <div class="hnt-weekly-moment-author">
            <img src="/build/next/hntv3-assets/avatar-bayou.webp" alt="" loading="lazy"/>
            <span><strong>Mitten in der Blood-Moon-Nacht</strong><small>von BayouNomad</small></span>
          </div>
        </div>
        <div class="hnt-weekly-moment-foot"><span><svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-trophy"></use></svg> DEIN CLIP KÖNNTE HIER STEHEN</span><a href="https://hnt.rocks/moments">Moments entdecken <svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-arrow-right"></use></svg></a></div>
      </section>

      <section class="widget latest-loadouts-widget" aria-labelledby="latestLoadoutsTitle">
        <div class="widget-top latest-loadouts-header">
          <h2 id="latestLoadoutsTitle"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg> Letzte Loadouts</h2>
          <button type="button" class="latest-loadouts-all" data-loadout-all aria-label="Alle Ausrüstungen anzeigen" aria-expanded="false">
            <span>Alle anzeigen</span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-right"></use></svg>
          </button>
        </div>
        <div class="latest-loadouts-body">
          <article class="latest-loadout-feature latest-loadout-entry" data-loadout-entry>
            <button class="latest-loadout-feature-button" type="button" data-loadout-toggle aria-expanded="false" aria-controls="latestLoadoutDetails1">
              <span class="latest-loadout-overline"><span class="latest-loadout-status"><i></i> COMMUNITY BUILD</span><span>VOR 1 STD.</span></span>
              <span class="latest-loadout-feature-heading"><strong>Budget Allrounder</strong><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-caret-down"></use></svg></span>
              <span class="latest-loadout-author">CaldwellEnjoyer <span class="latest-loadout-divider">·</span> 5 Ausrüstungsteile</span>
              <span class="latest-loadout-weapons" aria-label="Winfield 1893 und Caldwell Pax">
                <span class="latest-loadout-weapon"><img src="/build/next/hntv3-assets/weapon-winfield.webp" alt="Winfield 1893" loading="lazy"/></span>
                <span class="latest-loadout-weapon"><img src="/build/next/hntv3-assets/weapon-pax.webp" alt="Caldwell Pax" loading="lazy"/></span>
              </span>
              <span class="latest-loadout-feature-bottom"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg> Winfield + Pax</span><em>BUDGET</em></span>
            </button>
            <div class="latest-loadout-details" id="latestLoadoutDetails1" hidden>
              <div class="latest-loadout-details-title">Ausrüstung <span>5 Slots</span></div>
              <div class="latest-loadout-kit">
                <span>Winfield 1893</span><span>Caldwell Pax</span><span>First Aid Kit</span><span>Combat Knife</span><span>Fire Bomb</span>
              </div>
              <button type="button" class="latest-loadout-source" data-loadout-source="post-loadout">Beitrag ansehen <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></button>
            </div>
          </article>
          <div class="latest-loadout-compact-list">
            <article class="latest-loadout-compact latest-loadout-entry" data-loadout-entry>
              <button type="button" class="latest-loadout-compact-button" data-loadout-toggle aria-expanded="false" aria-controls="latestLoadoutDetails2">
                <span class="latest-loadout-mini-art"><img src="/build/next/hntv3-assets/weapon-pax.webp" alt="" loading="lazy"/></span>
                <span class="latest-loadout-mini-copy"><strong>Pax &amp; Knife Rush</strong><small>Nahkampf-Fokus <span>·</span> vor 2 Std.</small></span>
                <svg aria-hidden="true" class="ph latest-loadout-mini-chevron" viewBox="0 0 256 256"><use href="#ph-caret-down"></use></svg>
              </button>
              <div class="latest-loadout-details latest-loadout-compact-details" id="latestLoadoutDetails2" hidden>
                <div class="latest-loadout-details-title">Ausrüstung <span>5 Slots</span></div>
                <div class="latest-loadout-kit"><span>Winfield 1893</span><span>Caldwell Pax</span><span>Combat Knife</span><span>First Aid Kit</span><span>Fire Bomb</span></div>
                <button type="button" class="latest-loadout-source" data-loadout-source="post-pax-knife">Beitrag ansehen <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></button>
              </div>
            </article>
            <article class="latest-loadout-compact latest-loadout-entry" data-loadout-entry>
              <button type="button" class="latest-loadout-compact-button" data-loadout-toggle aria-expanded="false" aria-controls="latestLoadoutDetails3">
                <span class="latest-loadout-mini-art"><img src="/build/next/hntv3-assets/weapon-winfield.webp" alt="" loading="lazy"/></span>
                <span class="latest-loadout-mini-copy"><strong>Bayou Survival Kit</strong><small>Überleben <span>·</span> vor 2 Std.</small></span>
                <svg aria-hidden="true" class="ph latest-loadout-mini-chevron" viewBox="0 0 256 256"><use href="#ph-caret-down"></use></svg>
              </button>
              <div class="latest-loadout-details latest-loadout-compact-details" id="latestLoadoutDetails3" hidden>
                <div class="latest-loadout-details-title">Ausrüstung <span>4 Slots</span></div>
                <div class="latest-loadout-kit"><span>Winfield 1893</span><span>Caldwell Pax</span><span>First Aid Kit</span><span>Fire Bomb</span></div>
                <button type="button" class="latest-loadout-source" data-loadout-source="post-survival-kit">Beitrag ansehen <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></button>
              </div>
            </article>
            <article class="latest-loadout-compact latest-loadout-entry" data-loadout-entry>
              <button type="button" class="latest-loadout-compact-button" data-loadout-toggle aria-expanded="false" aria-controls="latestLoadoutDetails4">
                <span class="latest-loadout-mini-art"><img src="/build/next/hntv3-assets/weapon-winfield.webp" alt="" loading="lazy"/></span>
                <span class="latest-loadout-mini-copy"><strong>Silent Solo</strong><small>Solo-Build <span>·</span> vor 3 Std.</small></span>
                <svg aria-hidden="true" class="ph latest-loadout-mini-chevron" viewBox="0 0 256 256"><use href="#ph-caret-down"></use></svg>
              </button>
              <div class="latest-loadout-details latest-loadout-compact-details" id="latestLoadoutDetails4" hidden>
                <div class="latest-loadout-details-title">Ausrüstung <span>4 Slots</span></div>
                <div class="latest-loadout-kit"><span>Winfield 1893</span><span>Caldwell Pax</span><span>Combat Knife</span><span>First Aid Kit</span></div>
                <button type="button" class="latest-loadout-source" data-loadout-source="post-silent-solo">Beitrag ansehen <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></button>
              </div>
            </article>
          </div>
        </div>
        <div class="latest-loadout-footer"><span><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-target"></use></svg> HUNTER LOADOUTS</span><span>4 Beiträge</span></div>
      </section>
      <section class="widget cup-widget"><div class="widget-top cup-widget-heading"><h2><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-trophy"></use></svg> Last Blood Cup</h2><a href="https://hnt.rocks/cups">Alle Cups <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div><div class="cup-art"><img src="/build/next/hntv3-assets/cup-scene.webp" alt="Hunter-Szene bei Abenddämmerung"/><div class="cup-copy"><span class="cup-kicker">AKTUELLER WETTBEWERB</span><strong>LAST <span>BLOOD</span></strong><small>Nur der Beste bleibt stehen.</small></div></div><div class="cup-info"><span>GEWINN</span><strong>100 € <small>Gaming-Guthaben</small></strong></div><a class="cup-join" href="https://hnt.rocks/cups">Zum Wettbewerb <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></section>
      <section class="widget guides-widget"><div class="widget-top"><h2>Trending Guides</h2><a href="https://hnt.rocks/guides">Alle Guides <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div><div class="guide-list"><a href="https://hnt.rocks/guides" class="guide-item"><img src="/build/next/hntv3-assets/guide-seven-sisters.webp" alt="Seven Sisters Estate Guide"/><span><strong>Seven Sisters Estate</strong><small>Kartenwissen &amp; Rotationen</small><em>MAP GUIDE · 8.4K</em></span></a><a href="https://hnt.rocks/guides" class="guide-item"><img src="/build/next/hntv3-assets/guide-loadouts.webp" alt="Loadout Guide"/><span><strong>Starke Early-Game Loadouts</strong><small>Budget-Builds für jedes Match</small><em>LOADOUTS · 6.1K</em></span></a><a href="https://hnt.rocks/guides" class="guide-item"><img src="/build/next/hntv3-assets/guide-rotations.webp" alt="Hunter-Rotationen"/><span><strong>Rotations &amp; Map Control</strong><small>Immer einen Schritt voraus</small><em>TAKTIK · 4.8K</em></span></a></div></section>
      <section class="widget lobby-widget"><div class="widget-top"><h2><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-users-three"></use></svg> Ready Lobby</h2><a href="https://hnt.rocks/ready-lobbies">Öffnen <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></div><div class="lobby-body"><div class="lobby-signal"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-crosshair"></use></svg></div><div><strong>Dein nächstes Trio wartet.</strong><p>Schließ dich einem Team an oder starte eine eigene Lobby.</p></div></div><a class="lobby-cta" href="https://hnt.rocks/ready-lobbies">Hunter finden <svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></a></section>
      <div class="widget-footer-links"><a href="https://hnt.rocks/impressum">Impressum</a><a href="https://hnt.rocks/datenschutz">Datenschutz</a></div>
    </aside>
  </div>
</main>
<div class="composer-overlay" id="composerOverlay" hidden><div class="composer-dialog" role="dialog" aria-modal="true" aria-labelledby="composerTitle"><div class="composer-dialog-head"><div><span>NEUER BEITRAG</span><h2 id="composerTitle">Teile deine Geschichte</h2></div><button type="button" id="composerClose" class="hnt-pd-close" aria-label="Schließen" title="Schließen (Esc)"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-x"></use></svg></button></div><form id="newPostForm"><div class="composer-person"><img src="{{ $viewer->avatarUrl() }}" alt=""/><span><strong>{{ $viewer->name }}</strong><small>Veröffentlichen in deinem Feed</small></span></div><textarea id="postContent" rows="5" minlength="3" maxlength="1000" required placeholder="Was hast du im Bayou erlebt?"></textarea><div class="composer-preview" id="composerPreview" hidden><img id="composerPreviewImage" alt="Ausgewähltes Bild"/><button type="button" id="removePreview">Bild entfernen</button></div><div class="composer-dialog-foot"><label class="composer-upload"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-image"></use></svg> Bild anhängen<input id="composerImage" type="file" accept="image/*"/></label><button class="btn-compose" type="submit"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-paper-plane-tilt"></use></svg> Veröffentlichen</button></div></form></div></div><div id="feedToast" class="feed-toast" role="status" aria-live="polite" hidden></div>

<div id="navTooltip" class="nav-tooltip" role="tooltip" hidden><strong></strong><small></small></div>
<div class="modal-overlay" hidden="" id="modal">
<div aria-labelledby="modalTitle" aria-modal="true" class="modal-panel" role="dialog">
<button aria-label="Close" class="modal-close" id="modalClose"><svg aria-hidden="true" class="ph" viewbox="0 0 256 256"><use href="#ph-x"></use></svg></button>
<div class="modal-symbol" id="modalSymbol"><svg aria-hidden="true" class="ph" viewbox="0 0 256 256"><use href="#ph-arrow-up-right"></use></svg></div>
<h2 id="modalTitle"></h2>
<p id="modalDescription"></p>
<button class="modal-okay" id="modalOkay">Got it</button>
</div>
</div>
<div class="search-overlay" hidden="" id="searchOverlay">
<div aria-label="Search" aria-modal="true" class="search-dialog" role="dialog">
<div class="search-field"><svg aria-hidden="true" class="ph" viewbox="0 0 256 256"><use href="#ph-magnifying-glass"></use></svg><input autocomplete="off" id="searchInput" placeholder="Search courses and communities..."/><span class="esc-hint">ESC</span></div>
<div class="search-results" id="searchResults"></div>
</div>
</div>
</body>
</html>

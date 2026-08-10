@php
    $reactIndex = public_path('app/index.html');
    abort_unless(\Illuminate\Support\Facades\File::isFile($reactIndex), 503, 'The React application bundle is unavailable.');

    $locale = app()->getLocale() === 'de' ? 'de' : 'en';
    $title = __('ui.maps_detail_meta_title', ['map' => $map['name']]);
    $description = __('ui.maps_detail_meta_description', ['map' => $map['name']]);
    $canonical = route('maps.show', $map['slug']);
    $ogTitle = __('ui.maps_detail_og_title', ['map' => $map['name']]);
    $ogDescription = __('ui.maps_detail_og_description', ['map' => $map['name']]);
    $ogImage = asset($map['image']);

    $typeLabels = collect($markerTypes)->mapWithKeys(fn ($type) => [$type => __('ui.maps_type_'.$type)]);

    $runtimeConfig = [
        'imageUrl' => $map['image_url'],
        'linesUrl' => $map['lines_url'],
        'width' => $map['width'],
        'height' => $map['height'],
        'markers' => $markers,
        'typeLabels' => $typeLabels,
        'searchEmptyText' => __('ui.maps_search_empty'),
        'measureStartText' => __('ui.maps_measure_start'),
        'measureEndText' => __('ui.maps_measure_end'),
        'measureIdleText' => __('ui.maps_measure_idle'),
        'measurePointAText' => __('ui.maps_measure_point_a'),
        'measurePointBText' => __('ui.maps_measure_point_b'),
        'measureSavedText' => __('ui.maps_measure_saved'),
        'measureDistanceText' => __('ui.maps_measure_distance'),
        'measureMarkerAText' => __('ui.maps_measure_marker_a'),
        'measureMarkerBText' => __('ui.maps_measure_marker_b'),
        'viewerIsAuthenticated' => auth()->check(),
        'cashSpotSubmissionUrl' => $map['cash_spot_submission_url'],
        'cashScreenshotText' => __('ui.maps_cash_screenshot'),
        'cashScreenshotErrorText' => __('ui.maps_cash_screenshot_error'),
        'cashSpotSelectText' => __('ui.maps_cash_spot_select'),
        'cashSpotRunningText' => __('ui.maps_cash_spot_running'),
        'cashSpotPendingText' => __('ui.maps_cash_spot_pending'),
        'cashSpotErrorText' => __('ui.maps_cash_spot_error'),
        'cashSpotDetailTitle' => __('ui.maps_cash_spot_detail_title'),
        'cashSpotEyebrowText' => __('ui.maps_cash_spot_eyebrow'),
        'cashSpotHelpfulText' => __('ui.maps_cash_spot_helpful'),
        'cashSpotUpvoteText' => __('ui.maps_cash_spot_upvote'),
        'cashSpotDownvoteText' => __('ui.maps_cash_spot_downvote'),
        'cashSpotVoteAnonymousHintText' => __('ui.maps_cash_spot_vote_anonymous_hint'),
        'cashSpotCommentsTitleText' => __('ui.maps_cash_spot_comments_title'),
        'cashSpotCommentsLoadingText' => __('ui.maps_cash_spot_comments_loading'),
        'cashSpotCommentsEmptyText' => __('ui.maps_cash_spot_comments_empty'),
        'cashSpotCommentPlaceholderText' => __('ui.maps_cash_spot_comment_placeholder'),
        'cashSpotCommentSendText' => __('ui.maps_cash_spot_comment_send'),
        'cashSpotCommentLoginText' => __('ui.maps_cash_spot_comment_login'),
        'cashSpotCommentErrorText' => __('ui.maps_cash_spot_comment_error'),
        'cashSpotCommentEditText' => __('ui.maps_cash_spot_comment_edit'),
        'cashSpotCommentDeleteText' => __('ui.maps_cash_spot_comment_delete'),
        'cashSpotCommentDeleteConfirmText' => __('ui.maps_cash_spot_comment_delete_confirm'),
        'cashSpotCommentSaveText' => __('ui.maps_cash_spot_comment_save'),
        'cashSpotCommentCancelText' => __('ui.maps_cash_spot_comment_cancel'),
        'cashSpotCommentDeletedText' => __('ui.maps_cash_spot_comment_deleted'),
        'cashSpotCommentUpdatedText' => __('ui.maps_cash_spot_comment_updated'),
        'cashSpotVoteErrorText' => __('ui.maps_cash_spot_vote_error'),
        'cashSpotFormTitleText' => __('ui.maps_cash_spot_form_title'),
        'cashSpotFormHelpText' => __('ui.maps_cash_spot_form_help'),
        'cashSpotScreenshotFieldText' => __('ui.maps_cash_spot_screenshot'),
        'cashSpotNameText' => __('ui.maps_cash_spot_name'),
        'cashSpotEmailText' => __('ui.maps_cash_spot_email'),
        'cashSpotCancelText' => __('ui.maps_cash_spot_cancel'),
        'cashSpotSendText' => __('ui.maps_cash_spot_send'),
        'disclaimerText' => __('ui.maps_disclaimer'),
        'closeText' => __('ui.maps_close'),
    ];

    $payload = [
        'map' => [
            'slug' => $map['slug'],
            'name' => $map['name'],
            'width' => $map['width'],
            'height' => $map['height'],
            'imageUrl' => $map['image_url'],
            'linesUrl' => $map['lines_url'],
        ],
        'markerTypes' => $markerTypes,
        'availableMaps' => $availableMaps,
        'imageAvailable' => $imageAvailable,
        'dataError' => $dataError,
        'runtimeConfig' => $runtimeConfig,
        'copy' => [
            'title' => 'HNT Maps',
            'back' => __('ui.maps_back'),
            'chooseMap' => __('ui.maps_choose_map'),
            'search' => __('ui.maps_search'),
            'searchPlaceholder' => __('ui.maps_search_placeholder'),
            'searchHelp' => __('ui.maps_search_help'),
            'filters' => __('ui.maps_filters'),
            'filterHelp' => __('ui.maps_filter_help'),
            'layers' => __('ui.maps_layers'),
            'layerLines' => __('ui.maps_layer_lines'),
            'measure' => __('ui.maps_measure'),
            'measureHelp' => __('ui.maps_measure_help'),
            'measureStart' => __('ui.maps_measure_start'),
            'measureReset' => __('ui.maps_measure_reset'),
            'cashSpotSubmit' => __('ui.maps_cash_spot_submit'),
            'cashSpotSubmitHelp' => __('ui.maps_cash_spot_submit_help'),
            'resetView' => __('ui.maps_reset_view'),
        ],
    ];

    $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    $payloadJson = json_encode($payload, $jsonOptions);
    $structuredData = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $title,
        'description' => $description,
        'url' => $canonical,
        'image' => $ogImage,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'HNT.ROCKS',
            'url' => url('/'),
        ],
    ], $jsonOptions);

    $html = \Illuminate\Support\Facades\File::get($reactIndex);
    $html = preg_replace('~<html\\s+lang="[^"]*"~i', '<html lang="'.e($locale).'"', $html, 1) ?? $html;
    $html = preg_replace('~<title>.*?</title>~is', '<title>'.e($title).'</title>', $html, 1) ?? $html;
    $html = preg_replace('~<meta\\s+name="description"[^>]*>~i', '', $html, 1) ?? $html;
    $html = preg_replace('~<link\\s+rel="canonical"[^>]*>~i', '', $html, 1) ?? $html;

    $head = implode("\n", [
        '<meta name="description" content="'.e($description).'">',
        '<meta name="robots" content="index,follow">',
        '<meta name="csrf-token" content="'.e(csrf_token()).'">',
        '<link rel="canonical" href="'.e($canonical).'">',
        '<meta property="og:type" content="website">',
        '<meta property="og:title" content="'.e($ogTitle).'">',
        '<meta property="og:description" content="'.e($ogDescription).'">',
        '<meta property="og:url" content="'.e($canonical).'">',
        '<meta property="og:image" content="'.e($ogImage).'">',
        '<script type="application/ld+json">'.$structuredData.'</script>',
    ]);

    abort_unless(str_contains($html, '</head>'), 503, 'The React application head could not be prepared.');
    $html = str_replace('</head>', $head."\n</head>", $html);

    $rootPattern = '~<div\\s+id="root"\\s*></div>~i';
    abort_unless(preg_match($rootPattern, $html) === 1, 503, 'The React application root could not be prepared.');

    $bootstrap = '<script id="hntMapDetailData" type="application/json">'.$payloadJson.'</script>';
    $html = preg_replace($rootPattern, $bootstrap.'<div id="root"></div>', $html, 1) ?? $html;
@endphp
{!! $html !!}

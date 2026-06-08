@extends('layouts.app')

@section('title', 'Overview')

@section('content')
@php
    $hhOverview = $overview ?? [];
    $hhAdmin = $hhOverview['admin'] ?? auth()->user();
    $hhCharts = $hhOverview['charts'] ?? [];
    $hhTopStats = $hhOverview['topStats'] ?? [];
    $hhProfileStats = $hhOverview['profileStats'] ?? [];
    $hhSliderStats = $hhOverview['sliderStats'] ?? [];
    $hhActivityItems = collect($hhOverview['activityItems'] ?? []);
    $hhSystemEvents = $hhOverview['systemEvents'] ?? [];
    $hhAnalytics = $hhOverview['analytics'] ?? [];
    $hhTopMembers = $hhOverview['topMembers'] ?? [];
    $hhTopReactors = collect($hhOverview['topReactors'] ?? []);
    $hhTopCommenters = collect($hhOverview['topCommenters'] ?? []);
    $hhFeaturedPost = $hhOverview['featuredPost'] ?? null;
    $hhPersonalActivity = collect($hhOverview['personalActivityItems'] ?? $hhOverview['activityItems'] ?? []);
    $hhGamification = $hhOverview['gamification'] ?? [];
    $hhLatestBadge = $hhGamification['latestBadge'] ?? null;
    $hhLatestQuest = $hhGamification['latestQuest'] ?? null;
    $hhXpEvents = collect($hhOverview['xpEvents'] ?? []);
    $hhGamificationCompletion = (int) ($hhGamification['completionRate'] ?? 59);
    $hhBadgesAwarded = (int) ($hhGamification['badgesAwarded'] ?? 22);
    $hhBadgesTotal = max(1, (int) ($hhGamification['badgesTotal'] ?? 46));
    $hhQuestsCompleted = (int) ($hhGamification['questsCompleted'] ?? 11);
    $hhQuestsActive = max(1, (int) ($hhGamification['questsActive'] ?? 30));
    $hhHighestLevel = (int) ($hhGamification['highestLevel'] ?? $hhAdminLevel ?? 24);
    $hhXpTotal = (int) ($hhGamification['xpTotal'] ?? 13625);
    $hhMonthlyContent = (int) ($hhAnalytics['contentMonth'] ?? array_sum($hhCharts['veMonthlyReportData2'] ?? []));
    $hhMonthlyEngagements = (int) ($hhAnalytics['engagementsMonth'] ?? array_sum($hhCharts['veMonthlyReportData1'] ?? []));
    $hhDaysInMonth = max(1, (int) now()->daysInMonth);
    $hhEngagementData = $hhCharts['engagementsData'] ?? [0, 0, 0, 0];
    $hhSessionRows = collect($hhOverview['sessionRows'] ?? []);
    $hhCampaignLinks = collect($hhOverview['campaignLinks'] ?? []);
    $hhYearlyReactionsTotal = array_sum($hhCharts['rcYearlyReportData1'] ?? []);
    $hhYearlyCommentsTotal = array_sum($hhCharts['rcYearlyReportData2'] ?? []);
    $hhYearlySecondaryLabel = $hhOverview['yearlySecondaryLabel'] ?? 'Visits';

    $hhNum = static function ($value, string $fallback = '0'): string {
        if ($value === null || $value === '') {
            return $fallback;
        }
        if (! is_numeric($value)) {
            return (string) $value;
        }
        return number_format((float) $value, 0, ',', '.');
    };

    $hhPct = static function ($value, string $fallback = '0%'): string {
        if ($value === null || $value === '') {
            return $fallback;
        }
        if (! is_numeric($value)) {
            return (string) $value;
        }
        return number_format((float) $value, 0, ',', '.') . '%';
    };

    $hhStat = static fn (array $items, int $index, string $key, mixed $fallback = null): mixed => $items[$index][$key] ?? $fallback;
    $hhStatDiff = static fn (array $items, int $index, string $key, mixed $fallback = null): mixed => $items[$index]['diff'][$key] ?? $fallback;
    $hhSliderValue = static fn (int $index, mixed $fallback = null): mixed => $hhSliderStats[$index]['value'] ?? $fallback;
    $hhSliderLabel = static fn (int $index, string $fallback): string => (string) ($hhSliderStats[$index]['label'] ?? $fallback);
    $hhUserMetric = static function ($collection, int $index, string $key, mixed $fallback = null): mixed {
        $item = $collection instanceof \Illuminate\Support\Collection ? $collection->get($index) : ($collection[$index] ?? null);
        return is_array($item) ? ($item[$key] ?? $fallback) : $fallback;
    };
    $hhFirstActivity = $hhPersonalActivity->first();

    $hhAdminName = $hhAdmin?->name ?? $hhAdmin?->username ?? 'Marina Valentine';
    $hhAdminAvatar = ($hhAdmin && method_exists($hhAdmin, 'avatarUrl')) ? $hhAdmin->avatarUrl() : asset('assets/vikinger/img/avatar/01.jpg');
    $hhAdminLevel = (int) ($hhAdmin?->level ?? 24);
@endphp
<script>
  window.hhOverviewCharts = @json($hhCharts);
</script>
<!-- SECTION BANNER -->
    <div class="section-banner">
      <!-- SECTION BANNER ICON -->
      <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/overview-icon.png') }}" alt="overview-icon">
      <!-- /SECTION BANNER ICON -->
  
      <!-- SECTION BANNER TITLE -->
      <p class="section-banner-title">Overview</p>
      <!-- /SECTION BANNER TITLE -->
  
      <!-- SECTION BANNER TEXT -->
      <p class="section-banner-text">Review your account, see stats and more!</p>
      <!-- /SECTION BANNER TEXT -->
    </div>
    <!-- /SECTION BANNER -->

    <!-- SECTION HEADER -->
    <div class="section-header">
      <!-- SECTION HEADER INFO -->
      <div class="section-header-info">
        <!-- SECTION PRETITLE -->
        <p class="section-pretitle">Overview</p>
        <!-- /SECTION PRETITLE -->
  
        <!-- SECTION TITLE -->
        <h2 class="section-title">My Profile</h2>
        <!-- /SECTION TITLE -->
      </div>
      <!-- /SECTION HEADER INFO -->
    </div>
    <!-- /SECTION HEADER -->

    <!-- GRID -->
    <div class="grid">
      <!-- GRID -->
      <div class="grid grid-3-3-3-3 centered">
        <!-- STATS BOX -->
        <div class="stats-box small stat-profile-views">
          <!-- STATS BOX VALUE WRAP -->
          <div class="stats-box-value-wrap">
            <!-- STATS BOX VALUE -->
            <p class="stats-box-value">{{ $hhNum($hhStat($hhTopStats, 0, 'value', 87365)) }}</p>
            <!-- /STATS BOX VALUE -->
      
            <!-- STATS BOX DIFF -->
            <div class="stats-box-diff">
              <!-- STATS BOX DIFF ICON -->
              <div class="stats-box-diff-icon {{ $hhStatDiff($hhTopStats, 0, 'direction', 'positive') }}">
                <!-- ICON PLUS SMALL -->
                <svg class="icon-{{ $hhStatDiff($hhTopStats, 0, 'icon', 'plus-small') }}">
                  <use xlink:href="#svg-{{ $hhStatDiff($hhTopStats, 0, 'icon', 'plus-small') }}"></use>
                </svg>
                <!-- /ICON PLUS SMALL -->
              </div>
              <!-- /STATS BOX DIFF ICON -->
      
              <!-- STATS BOX DIFF VALUE -->
              <p class="stats-box-diff-value">{{ $hhStatDiff($hhTopStats, 0, 'value', '3.2%') }}</p>
              <!-- /STATS BOX DIFF VALUE -->
            </div>
            <!-- /STATS BOX DIFF -->
          </div>
          <!-- /STATS BOX VALUE WRAP -->
      
          <!-- STATS BOX TITLE -->
          <p class="stats-box-title">{{ $hhStat($hhTopStats, 0, 'label', 'Profile Views') }}</p>
          <!-- /STATS BOX TITLE -->
      
          <!-- STATS BOX TEXT -->
          <p class="stats-box-text">{{ $hhStat($hhTopStats, 0, 'text', 'In the last month') }}</p>
          <!-- /STATS BOX TEXT -->
        </div>
        <!-- /STATS BOX -->

        <!-- STATS BOX -->
        <div class="stats-box small stat-posts-created">
          <!-- STATS BOX VALUE WRAP -->
          <div class="stats-box-value-wrap">
            <!-- STATS BOX VALUE -->
            <p class="stats-box-value">{{ $hhNum($hhStat($hhTopStats, 1, 'value', 294)) }}</p>
            <!-- /STATS BOX VALUE -->
      
            <!-- STATS BOX DIFF -->
            <div class="stats-box-diff">
              <!-- STATS BOX DIFF ICON -->
              <div class="stats-box-diff-icon {{ $hhStatDiff($hhTopStats, 1, 'direction', 'positive') }}">
                <!-- ICON PLUS SMALL -->
                <svg class="icon-{{ $hhStatDiff($hhTopStats, 1, 'icon', 'plus-small') }}">
                  <use xlink:href="#svg-{{ $hhStatDiff($hhTopStats, 1, 'icon', 'plus-small') }}"></use>
                </svg>
                <!-- /ICON PLUS SMALL -->
              </div>
              <!-- /STATS BOX DIFF ICON -->
      
              <!-- STATS BOX DIFF VALUE -->
              <p class="stats-box-diff-value">{{ $hhStatDiff($hhTopStats, 1, 'value', '0.4%') }}</p>
              <!-- /STATS BOX DIFF VALUE -->
            </div>
            <!-- /STATS BOX DIFF -->
          </div>
          <!-- /STATS BOX VALUE WRAP -->
      
          <!-- STATS BOX TITLE -->
          <p class="stats-box-title">{{ $hhStat($hhTopStats, 1, 'label', 'Posts Created') }}</p>
          <!-- /STATS BOX TITLE -->
      
          <!-- STATS BOX TEXT -->
          <p class="stats-box-text">{{ $hhStat($hhTopStats, 1, 'text', 'In the last month') }}</p>
          <!-- /STATS BOX TEXT -->
        </div>
        <!-- /STATS BOX -->

        <!-- STATS BOX -->
        <div class="stats-box small stat-reactions-received">
          <!-- STATS BOX VALUE WRAP -->
          <div class="stats-box-value-wrap">
            <!-- STATS BOX VALUE -->
            <p class="stats-box-value">{{ $hhNum($hhStat($hhTopStats, 2, 'value', 2560)) }}</p>
            <!-- /STATS BOX VALUE -->
      
            <!-- STATS BOX DIFF -->
            <div class="stats-box-diff">
              <!-- STATS BOX DIFF ICON -->
              <div class="stats-box-diff-icon {{ $hhStatDiff($hhTopStats, 2, 'direction', 'negative') }}">
                <!-- ICON MINUS SMALL -->
                <svg class="icon-{{ $hhStatDiff($hhTopStats, 2, 'icon', 'minus-small') }}">
                  <use xlink:href="#svg-{{ $hhStatDiff($hhTopStats, 2, 'icon', 'minus-small') }}"></use>
                </svg>
                <!-- /ICON MINUS SMALL -->
              </div>
              <!-- /STATS BOX DIFF ICON -->
      
              <!-- STATS BOX DIFF VALUE -->
              <p class="stats-box-diff-value">{{ $hhStatDiff($hhTopStats, 2, 'value', '1.8%') }}</p>
              <!-- /STATS BOX DIFF VALUE -->
            </div>
            <!-- /STATS BOX DIFF -->
          </div>
          <!-- /STATS BOX VALUE WRAP -->
      
          <!-- STATS BOX TITLE -->
          <p class="stats-box-title">{{ $hhStat($hhTopStats, 2, 'label', 'Reactions Received') }}</p>
          <!-- /STATS BOX TITLE -->
      
          <!-- STATS BOX TEXT -->
          <p class="stats-box-text">{{ $hhStat($hhTopStats, 2, 'text', 'In the last month') }}</p>
          <!-- /STATS BOX TEXT -->
        </div>
        <!-- /STATS BOX -->

        <!-- STATS BOX -->
        <div class="stats-box small stat-comments-received">
          <!-- STATS BOX VALUE WRAP -->
          <div class="stats-box-value-wrap">
            <!-- STATS BOX VALUE -->
            <p class="stats-box-value">{{ $hhNum($hhStat($hhTopStats, 3, 'value', 947)) }}</p>
            <!-- /STATS BOX VALUE -->
      
            <!-- STATS BOX DIFF -->
            <div class="stats-box-diff">
              <!-- STATS BOX DIFF ICON -->
              <div class="stats-box-diff-icon {{ $hhStatDiff($hhTopStats, 3, 'direction', 'positive') }}">
                <!-- ICON PLUS SMALL -->
                <svg class="icon-{{ $hhStatDiff($hhTopStats, 3, 'icon', 'plus-small') }}">
                  <use xlink:href="#svg-{{ $hhStatDiff($hhTopStats, 3, 'icon', 'plus-small') }}"></use>
                </svg>
                <!-- /ICON PLUS SMALL -->
              </div>
              <!-- /STATS BOX DIFF ICON -->
      
              <!-- STATS BOX DIFF VALUE -->
              <p class="stats-box-diff-value">{{ $hhStatDiff($hhTopStats, 3, 'value', '4.5%') }}</p>
              <!-- /STATS BOX DIFF VALUE -->
            </div>
            <!-- /STATS BOX DIFF -->
          </div>
          <!-- /STATS BOX VALUE WRAP -->
      
          <!-- STATS BOX TITLE -->
          <p class="stats-box-title">{{ $hhStat($hhTopStats, 3, 'label', 'Comments Received') }}</p>
          <!-- /STATS BOX TITLE -->
      
          <!-- STATS BOX TEXT -->
          <p class="stats-box-text">{{ $hhStat($hhTopStats, 3, 'text', 'In the last month') }}</p>
          <!-- /STATS BOX TEXT -->
        </div>
        <!-- /STATS BOX -->
      </div>
      <!-- /GRID -->

      <!-- GRID -->
      <div class="grid grid-layout-1">
        <!-- GRID COLUMN -->
        <div class="grid-sidebar">
          <!-- PROFILE STATS -->
          <div class="profile-stats fixed-height">
            <!-- PROFILE STATS COVER -->
            <div class="profile-stats-cover">
              <!-- PROFILE STATS COVER TITLE -->
              <p class="profile-stats-cover-title">Welcome Back!</p>
              <!-- /PROFILE STATS COVER TITLE -->
        
              <!-- PROFILE STATS COVER TEXT -->
              <p class="profile-stats-cover-text">{{ $hhAdminName }}</p>
              <!-- /PROFILE STATS COVER TEXT -->
            </div>
            <!-- /PROFILE STATS COVER -->
        
            <!-- PROFILE STATS INFO -->
            <div class="profile-stats-info">
              <!-- USER AVATAR -->
              <div class="user-avatar medium">
                <!-- USER AVATAR BORDER -->
                <div class="user-avatar-border">
                  <!-- HEXAGON -->
                  <div class="hexagon-120-132"></div>
                  <!-- /HEXAGON -->
                </div>
                <!-- /USER AVATAR BORDER -->
            
                <!-- USER AVATAR CONTENT -->
                <div class="user-avatar-content">
                  <!-- HEXAGON -->
                  <div class="hexagon-image-82-90" data-src="{{ $hhAdminAvatar }}"></div>
                  <!-- /HEXAGON -->
                </div>
                <!-- /USER AVATAR CONTENT -->
            
                <!-- USER AVATAR PROGRESS -->
                <div class="user-avatar-progress">
                  <!-- HEXAGON -->
                  <div class="hexagon-progress-100-110"></div>
                  <!-- /HEXAGON -->
                </div>
                <!-- /USER AVATAR PROGRESS -->
            
                <!-- USER AVATAR PROGRESS BORDER -->
                <div class="user-avatar-progress-border">
                  <!-- HEXAGON -->
                  <div class="hexagon-border-100-110"></div>
                  <!-- /HEXAGON -->
                </div>
                <!-- /USER AVATAR PROGRESS BORDER -->
            
                <!-- USER AVATAR BADGE -->
                <div class="user-avatar-badge">
                  <!-- USER AVATAR BADGE BORDER -->
                  <div class="user-avatar-badge-border">
                    <!-- HEXAGON -->
                    <div class="hexagon-32-36"></div>
                    <!-- /HEXAGON -->
                  </div>
                  <!-- /USER AVATAR BADGE BORDER -->
            
                  <!-- USER AVATAR BADGE CONTENT -->
                  <div class="user-avatar-badge-content">
                    <!-- HEXAGON -->
                    <div class="hexagon-dark-26-28"></div>
                    <!-- /HEXAGON -->
                  </div>
                  <!-- /USER AVATAR BADGE CONTENT -->
            
                  <!-- USER AVATAR BADGE TEXT -->
                  <p class="user-avatar-badge-text">{{ $hhAdminLevel }}</p>
                  <!-- /USER AVATAR BADGE TEXT -->
                </div>
                <!-- /USER AVATAR BADGE -->
              </div>
              <!-- /USER AVATAR -->
        
              <!-- FEATURED STAT LIST -->
              <div class="featured-stat-list">
                <!-- FEATURED STAT -->
                <div class="featured-stat">
                  <!-- FEATURED STAT ICON -->
                  <svg class="featured-stat-icon icon-status">
                    <use xlink:href="#svg-status"></use>
                  </svg>
                  <!-- /FEATURED STAT ICON -->
        
                  <!-- FEATURED STAT TITLE -->
                  <p class="featured-stat-title">{{ $hhProfileStats['postsAvg'] ?? '28.4' }}</p>
                  <!-- /FEATURED STAT TITLE -->
        
                  <!-- FEATURED STAT SUBTITLE -->
                  <p class="featured-stat-subtitle">Posts</p>
                  <!-- /FEATURED STAT SUBTITLE -->
        
                  <!-- FEATURED STAT TEXT -->
                  <p class="featured-stat-text">Avg Month</p>
                  <!-- /FEATURED STAT TEXT -->
                </div>
                <!-- /FEATURED STAT -->
        
                <!-- FEATURED STAT -->
                <div class="featured-stat">
                  <!-- FEATURED STAT ICON -->
                  <svg class="featured-stat-icon icon-comment">
                    <use xlink:href="#svg-comment"></use>
                  </svg>
                  <!-- /FEATURED STAT ICON -->
        
                  <!-- FEATURED STAT TITLE -->
                  <p class="featured-stat-title">{{ $hhProfileStats['commentsAvg'] ?? '69.7' }}</p>
                  <!-- /FEATURED STAT TITLE -->
        
                  <!-- FEATURED STAT SUBTITLE -->
                  <p class="featured-stat-subtitle">Comments</p>
                  <!-- /FEATURED STAT SUBTITLE -->
        
                  <!-- FEATURED STAT TEXT -->
                  <p class="featured-stat-text">Avg Month</p>
                  <!-- /FEATURED STAT TEXT -->
                </div>
                <!-- /FEATURED STAT -->
              </div>
              <!-- /FEATURED STAT LIST -->
        
              <!-- FEATURED STAT LIST -->
              <div class="featured-stat-list">
                <!-- FEATURED STAT -->
                <div class="featured-stat">
                  <!-- PROGRESS ARC WRAP -->
                  <div class="progress-arc-wrap small">
                    <!-- PROGRESS ARC -->
                    <div class="progress-arc">
                      <canvas id="posts-engagement-chart"></canvas>
                    </div>
                    <!-- PROGRESS ARC -->
              
                    <!-- PROGRESS ARC INFO -->
                    <div class="progress-arc-info">
                      <!-- PROGRESS ARC TITLE -->
                      <p class="progress-arc-title">{{ $hhPct($hhProfileStats['contentEngagementRate'] ?? 87) }}</p>
                      <!-- /PROGRESS ARC TITLE -->
                    </div>
                    <!-- /PROGRESS ARC INFO -->
                  </div>
                  <!-- /PROGRESS ARC WRAP -->
        
                  <!-- FEATURED STAT SUBTITLE -->
                  <p class="featured-stat-subtitle">Posts</p>
                  <!-- /FEATURED STAT SUBTITLE -->
        
                  <!-- FEATURED STAT TEXT -->
                  <p class="featured-stat-text">Engagement</p>
                  <!-- /FEATURED STAT TEXT -->
                </div>
                <!-- /FEATURED STAT -->
        
                <!-- FEATURED STAT -->
                <div class="featured-stat">
                  <!-- PROGRESS ARC WRAP -->
                  <div class="progress-arc-wrap small">
                    <!-- PROGRESS ARC -->
                    <div class="progress-arc">
                      <canvas id="posts-shared-chart"></canvas>
                    </div>
                    <!-- PROGRESS ARC -->
        
                    <!-- PROGRESS ARC INFO -->
                    <div class="progress-arc-info">
                      <!-- PROGRESS ARC TITLE -->
                      <p class="progress-arc-title">{{ $hhPct($hhProfileStats['returningUsersRate'] ?? 42) }}</p>
                      <!-- /PROGRESS ARC TITLE -->
                    </div>
                    <!-- /PROGRESS ARC INFO -->
                  </div>
                  <!-- /PROGRESS ARC WRAP -->
        
                  <!-- FEATURED STAT SUBTITLE -->
                  <p class="featured-stat-subtitle">Posts</p>
                  <!-- /FEATURED STAT SUBTITLE -->
        
                  <!-- FEATURED STAT TEXT -->
                  <p class="featured-stat-text">Shared</p>
                  <!-- /FEATURED STAT TEXT -->
                </div>
                <!-- /FEATURED STAT -->
              </div>
              <!-- /FEATURED STAT LIST -->
            </div>
            <!-- /PROFILE STATS INFO -->
          </div>
          <!-- /PROFILE STATS -->
        </div>
        <!-- /GRID COLUMN -->

        <!-- GRID COLUMN -->
        <div class="grid-header">
          <!-- SLIDER LINE -->
          <div class="slider-line small">
            <!-- SLIDER CONTROLS -->
            <div id="user-stats-slider-controls" class="slider-controls">
              <!-- SLIDER CONTROL -->
              <div class="slider-control left">
                <!-- SLIDER CONTROL ICON -->
                <svg class="slider-control-icon icon-small-arrow">
                  <use xlink:href="#svg-small-arrow"></use>
                </svg>
                <!-- /SLIDER CONTROL ICON -->
              </div>
              <!-- /SLIDER CONTROL -->
        
              <!-- SLIDER CONTROL -->
              <div class="slider-control right">
                <!-- SLIDER CONTROL ICON -->
                <svg class="slider-control-icon icon-small-arrow">
                  <use xlink:href="#svg-small-arrow"></use>
                </svg>
                <!-- /SLIDER CONTROL ICON -->
              </div>
              <!-- /SLIDER CONTROL -->
            </div>
            <!-- /SLIDER CONTROLS -->
        
            <!-- SLIDER SLIDES -->
            <div id="user-stats-slider" class="slider-slides with-separator">
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(0, '930')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(0, 'posts') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
        
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(1, '82')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(1, 'friends') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
        
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(2, '5.7k')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(2, 'visits') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
        
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(3, '13')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(3, 'badges') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
        
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(4, '74')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(4, 'photos') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
        
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
              <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(5, '10.6k')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(5, 'reactions (r)') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
        
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(6, '8.4k')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(6, 'comments (r)') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
        
              <!-- SLIDER SLIDE -->
              <div class="slider-slide">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhSliderValue(7, '2.3k')) }}</p>
                  <!-- /USER STAT TITLE -->
        
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">{{ $hhSliderLabel(7, 'shares (r)') }}</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /SLIDER SLIDE -->
            </div>
            <!-- /SLIDER SLIDES -->
          </div>
          <!-- /SLIDER LINE -->
        </div>
        <!-- /GRID COLUMN -->

        <!-- GRID COLUMN -->
        <div class="grid-content">
          <!-- WIDGET BOX -->
          <div class="widget-box no-padding">
            <!-- WIDGET BOX TITLE -->
            <p class="widget-box-title">Profile Activity</p>
            <!-- /WIDGET BOX TITLE -->
        
            <!-- WIDGET BOX CONTENT -->
            <div class="widget-box-content padded-for-scroll" data-simplebar>
              <!-- USER STATUS LIST -->
              <div class="user-status-list scroll-content">
                @forelse($hhActivityItems->take(8) as $item)
                  <!-- USER STATUS -->
                  <div class="user-status notification">
                    <!-- USER STATUS AVATAR -->
                    <a class="user-status-avatar" href="{{ $item['url'] ?? 'javascript:void(0)' }}">
                      <!-- USER AVATAR -->
                      <div class="user-avatar small no-outline">
                        <!-- USER AVATAR CONTENT -->
                        <div class="user-avatar-content">
                          <!-- HEXAGON -->
                          <div class="hexagon-image-30-32" data-src="{{ $item['avatar'] ?? asset('assets/vikinger/img/avatar/01.jpg') }}"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR CONTENT -->

                        <!-- USER AVATAR PROGRESS -->
                        <div class="user-avatar-progress">
                          <!-- HEXAGON -->
                          <div class="hexagon-progress-40-44"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR PROGRESS -->

                        <!-- USER AVATAR PROGRESS BORDER -->
                        <div class="user-avatar-progress-border">
                          <!-- HEXAGON -->
                          <div class="hexagon-border-40-44"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR PROGRESS BORDER -->
                      </div>
                      <!-- /USER AVATAR -->
                    </a>
                    <!-- /USER STATUS AVATAR -->

                    <!-- USER STATUS TITLE -->
                    <p class="user-status-title"><a class="bold" href="{{ $item['url'] ?? 'javascript:void(0)' }}">{{ $item['name'] ?? 'hnt.rocks' }}</a> {{ $item['action'] ?? 'created' }} <a class="highlighted" href="{{ $item['url'] ?? 'javascript:void(0)' }}">{{ $item['title'] ?? 'activity' }}</a></p>
                    <!-- /USER STATUS TITLE -->

                    <!-- USER STATUS TIMESTAMP -->
                    <p class="user-status-timestamp small-space">{{ $item['time'] ?? '' }}</p>
                    <!-- /USER STATUS TIMESTAMP -->

                    <!-- USER STATUS ICON -->
                    <div class="user-status-icon">
                      <svg class="icon-{{ $item['icon'] ?? 'status' }}">
                        <use xlink:href="#svg-{{ $item['icon'] ?? 'status' }}"></use>
                      </svg>
                    </div>
                    <!-- /USER STATUS ICON -->
                  </div>
                  <!-- /USER STATUS -->
                @empty
                  <!-- USER STATUS -->
                  <div class="user-status notification">
                    <a class="user-status-avatar" href="javascript:void(0)">
                      <div class="user-avatar small no-outline">
                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ asset('assets/vikinger/img/avatar/01.jpg') }}"></div></div>
                        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                      </div>
                    </a>
                    <p class="user-status-title"><a class="bold" href="javascript:void(0)">hnt.rocks</a> has no recent <a class="highlighted" href="javascript:void(0)">activity</a></p>
                    <p class="user-status-timestamp small-space">now</p>
                    <div class="user-status-icon"><svg class="icon-status"><use xlink:href="#svg-status"></use></svg></div>
                  </div>
                  <!-- /USER STATUS -->
                @endforelse
              </div>
              <!-- /USER STATUS LIST -->
            </div>
            <!-- WIDGET BOX CONTENT -->
          </div>
          <!-- /WIDGET BOX -->
        </div>
        <!-- /GRID COLUMN -->

        <!-- GRID COLUMN -->
        <div class="grid-column grid-content-sidebar">
          <!-- STATS DECORATION -->
          <div class="stats-decoration v2 big secondary">
            <!-- STATS DECORATION TITLE -->
            <p class="stats-decoration-title">{{ $hhNum($hhAnalytics['engagementsMonth'] ?? 33) }}</p>
            <!-- /STATS DECORATION TITLE -->
        
            <!-- STATS DECORATION SUBTITLE -->
            <p class="stats-decoration-subtitle">Engagements</p>
            <!-- /STATS DECORATION SUBTITLE -->
        
            <!-- STATS DECORATION TEXT -->
            <p class="stats-decoration-text">This month</p>
            <!-- /STATS DECORATION TEXT -->
        
            <!-- PERCENTAGE DIFF -->
            <div class="percentage-diff">
              <!-- PERCENTAGE DIFF ICON WRAP -->
              <div class="percentage-diff-icon-wrap positive">
                <!-- PERCENTAGE DIFF ICON -->
                <svg class="percentage-diff-icon icon-plus-small">
                  <use xlink:href="#svg-plus-small"></use>
                </svg>
                <!-- /PERCENTAGE DIFF ICON -->
              </div>
              <!-- /PERCENTAGE DIFF ICON WRAP -->
        
              <!-- PERCENTAGE DIFF TEXT -->
              <p class="percentage-diff-text">5.3%</p>
              <!-- /PERCENTAGE DIFF TEXT -->
            </div>
            <!-- /PERCENTAGE DIFF -->
          </div>
          <!-- /STATS DECORATION -->
        
          <!-- STATS DECORATION -->
          <div class="stats-decoration v2 big primary">
            <!-- STATS DECORATION TITLE -->
            <p class="stats-decoration-title">{{ $hhNum($hhAnalytics['activeUsersMonth'] ?? 126) }}</p>
            <!-- /STATS DECORATION TITLE -->
        
            <!-- STATS DECORATION SUBTITLE -->
            <p class="stats-decoration-subtitle">Active Users</p>
            <!-- /STATS DECORATION SUBTITLE -->
        
            <!-- STATS DECORATION TEXT -->
            <p class="stats-decoration-text">This month</p>
            <!-- /STATS DECORATION TEXT -->
        
            <!-- PERCENTAGE DIFF -->
            <div class="percentage-diff">
              <!-- PERCENTAGE DIFF ICON WRAP -->
              <div class="percentage-diff-icon-wrap negative">
                <!-- PERCENTAGE DIFF ICON -->
                <svg class="percentage-diff-icon icon-minus-small">
                  <use xlink:href="#svg-minus-small"></use>
                </svg>
                <!-- /PERCENTAGE DIFF ICON -->
              </div>
              <!-- /PERCENTAGE DIFF ICON WRAP -->
        
              <!-- PERCENTAGE DIFF TEXT -->
              <p class="percentage-diff-text">4.7%</p>
              <!-- /PERCENTAGE DIFF TEXT -->
            </div>
            <!-- /PERCENTAGE DIFF -->
          </div>
          <!-- /STATS DECORATION -->
        </div>
        <!-- /GRID COLUMN -->
      </div>
      <!-- /GRID -->

      <!-- SLIDER LINE -->
      <div class="slider-line medium">
        <!-- SLIDER CONTROLS -->
        <div id="reaction-stats-slider-controls" class="slider-controls">
          <!-- SLIDER CONTROL -->
          <div class="slider-control left">
            <!-- SLIDER CONTROL ICON -->
            <svg class="slider-control-icon icon-small-arrow">
              <use xlink:href="#svg-small-arrow"></use>
            </svg>
            <!-- /SLIDER CONTROL ICON -->
          </div>
          <!-- /SLIDER CONTROL -->
    
          <!-- SLIDER CONTROL -->
          <div class="slider-control right">
            <!-- SLIDER CONTROL ICON -->
            <svg class="slider-control-icon icon-small-arrow">
              <use xlink:href="#svg-small-arrow"></use>
            </svg>
            <!-- /SLIDER CONTROL ICON -->
          </div>
          <!-- /SLIDER CONTROL -->
        </div>
        <!-- /SLIDER CONTROLS -->
    
        <!-- SLIDER SLIDES -->
        <div id="reaction-stats-slider" class="slider-slides with-separator">
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/like.png') }}" alt="reaction-like">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][0] ?? '12.642') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][0] ?? 'Likes' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/love.png') }}" alt="reaction-love">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][1] ?? '8.913') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][1] ?? 'Loves' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/dislike.png') }}" alt="reaction-dislike">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][2] ?? '945') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][2] ?? 'Dislikes' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/happy.png') }}" alt="reaction-happy">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][3] ?? '7.034') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][3] ?? 'Happy' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/funny.png') }}" alt="reaction-funny">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][4] ?? '2.356') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][4] ?? 'Funny' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/wow.png') }}" alt="reaction-wow">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][5] ?? '5.944') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][5] ?? 'Wow' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/angry.png') }}" alt="reaction-angry">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][6] ?? '1.706') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][6] ?? 'Angry' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- REACTION STAT -->
            <div class="reaction-stat">
              <!-- REACTION STAT IMAGE -->
              <img class="reaction-stat-image" src="{{ asset('assets/vikinger/img/reaction/sad.png') }}" alt="reaction-sad">
              <!-- /REACTION STAT IMAGE -->
    
              <!-- REACTION STAT TITLE -->
              <p class="reaction-stat-title">{{ $hhNum($hhCharts['engagementsData'][7] ?? '801') }}</p>
              <!-- /REACTION STAT TITLE -->
    
              <!-- REACTION STAT TEXT -->
              <p class="reaction-stat-text">{{ $hhCharts['engagementsLabels'][7] ?? 'Sad' }}</p>
              <!-- /REACTION STAT TEXT -->
            </div>
            <!-- /REACTION STAT -->
          </div>
          <!-- /SLIDER SLIDE -->
        </div>
        <!-- SLIDER SLIDES -->
      </div>
      <!-- /SLIDER LINE -->

      <!-- GRID -->
      <div class="grid grid-3-3-3-3 centered">
        @php
          $hhPhaseBBoxes = [
            ['collection' => $hhTopReactors, 'index' => 0, 'class' => 'reactioner', 'title' => 'Top Reactor', 'subtitle' => 'Reactions', 'fallbackAvatar' => asset('assets/vikinger/img/avatar/02.jpg'), 'fallbackName' => 'Destroy Dex', 'fallbackCount' => 94, 'fallbackLevel' => 13, 'fallbackPeriod' => 'of last month'],
            ['collection' => $hhTopReactors, 'index' => 1, 'class' => 'reactioner', 'title' => 'Top Reactor', 'subtitle' => 'Reactions', 'fallbackAvatar' => asset('assets/vikinger/img/avatar/03.jpg'), 'fallbackName' => 'Nick Grissom', 'fallbackCount' => 1569, 'fallbackLevel' => 16, 'fallbackPeriod' => 'of all time'],
            ['collection' => $hhTopCommenters, 'index' => 0, 'class' => 'commenter', 'title' => 'Top Commenter', 'subtitle' => 'Comments', 'fallbackAvatar' => asset('assets/vikinger/img/avatar/05.jpg'), 'fallbackName' => 'Neko Bebop', 'fallbackCount' => 47, 'fallbackLevel' => 12, 'fallbackPeriod' => 'of last month'],
            ['collection' => $hhTopCommenters, 'index' => 1, 'class' => 'commenter', 'title' => 'Top Commenter', 'subtitle' => 'Comments', 'fallbackAvatar' => asset('assets/vikinger/img/avatar/02.jpg'), 'fallbackName' => 'Destroy Dex', 'fallbackCount' => 803, 'fallbackLevel' => 13, 'fallbackPeriod' => 'of all time'],
          ];
        @endphp
        @foreach($hhPhaseBBoxes as $hhPhaseBBox)
          <!-- FEATURED STAT BOX -->
          <div class="featured-stat-box {{ $hhPhaseBBox['class'] }}">
            <!-- FEATURED STAT BOX COVER -->
            <div class="featured-stat-box-cover">
              <!-- FEATURED STAT BOX COVER TITLE -->
              <p class="featured-stat-box-cover-title">{{ $hhPhaseBBox['title'] }}</p>
              <!-- /FEATURED STAT BOX COVER TITLE -->
        
              <!-- FEATURED STAT BOX COVER TEXT -->
              <p class="featured-stat-box-cover-text">{{ $hhUserMetric($hhPhaseBBox['collection'], $hhPhaseBBox['index'], 'period', $hhPhaseBBox['fallbackPeriod']) }}</p>
              <!-- /FEATURED STAT BOX COVER TEXT -->
            </div>
            <!-- /FEATURED STAT BOX COVER -->
        
            <!-- FEATURED STAT BOX INFO -->
            <div class="featured-stat-box-info">
              <!-- USER AVATAR -->
              <div class="user-avatar small">
                <!-- USER AVATAR BORDER -->
                <div class="user-avatar-border"><div class="hexagon-50-56"></div></div>
                <!-- /USER AVATAR BORDER -->
            
                <!-- USER AVATAR CONTENT -->
                <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $hhUserMetric($hhPhaseBBox['collection'], $hhPhaseBBox['index'], 'avatar', $hhPhaseBBox['fallbackAvatar']) }}"></div></div>
                <!-- /USER AVATAR CONTENT -->
            
                <!-- USER AVATAR PROGRESS -->
                <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                <!-- /USER AVATAR PROGRESS -->
            
                <!-- USER AVATAR PROGRESS BORDER -->
                <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                <!-- /USER AVATAR PROGRESS BORDER -->
            
                <!-- USER AVATAR BADGE -->
                <div class="user-avatar-badge">
                  <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                  <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                  <p class="user-avatar-badge-text">{{ $hhNum($hhUserMetric($hhPhaseBBox['collection'], $hhPhaseBBox['index'], 'level', $hhPhaseBBox['fallbackLevel'])) }}</p>
                </div>
                <!-- /USER AVATAR BADGE -->
              </div>
              <!-- /USER AVATAR -->
        
              <!-- FEATURED STAT BOX TITLE -->
              <p class="featured-stat-box-title">{{ $hhNum($hhUserMetric($hhPhaseBBox['collection'], $hhPhaseBBox['index'], 'count', $hhPhaseBBox['fallbackCount'])) }}</p>
              <!-- /FEATURED STAT BOX TITLE -->
        
              <!-- FEATURED STAT BOX SUBTITLE -->
              <p class="featured-stat-box-subtitle">{{ $hhPhaseBBox['subtitle'] }}</p>
              <!-- /FEATURED STAT BOX SUBTITLE -->
        
              <!-- FEATURED STAT BOX TEXT -->
              <p class="featured-stat-box-text">{{ $hhUserMetric($hhPhaseBBox['collection'], $hhPhaseBBox['index'], 'name', $hhPhaseBBox['fallbackName']) }}</p>
              <!-- /FEATURED STAT BOX TEXT -->
            </div>
            <!-- /FEATURED STAT BOX INFO -->
          </div>
          <!-- /FEATURED STAT BOX -->
        @endforeach
      </div>
      <!-- /GRID -->

      <!-- GRID -->
      <div class="grid grid-half change-on-desktop">
        <!-- WIDGET BOX -->
        <div class="widget-box no-padding">
          <!-- WIDGET BOX SETTINGS -->
          <div class="widget-box-settings">
            <!-- POST SETTINGS WRAP -->
            <div class="post-settings-wrap">
              <!-- POST SETTINGS -->
              <div class="post-settings widget-box-post-settings-dropdown-trigger">
                <!-- POST SETTINGS ICON -->
                <svg class="post-settings-icon icon-more-dots">
                  <use xlink:href="#svg-more-dots"></use>
                </svg>
                <!-- /POST SETTINGS ICON -->
              </div>
              <!-- /POST SETTINGS -->
      
              <!-- SIMPLE DROPDOWN -->
              <div class="simple-dropdown widget-box-post-settings-dropdown">
                <!-- SIMPLE DROPDOWN LINK -->
                <p class="simple-dropdown-link">Edit Post</p>
                <!-- /SIMPLE DROPDOWN LINK -->
      
                <!-- SIMPLE DROPDOWN LINK -->
                <p class="simple-dropdown-link">Delete Post</p>
                <!-- /SIMPLE DROPDOWN LINK -->
      
                <!-- SIMPLE DROPDOWN LINK -->
                <p class="simple-dropdown-link">Make it Featured</p>
                <!-- /SIMPLE DROPDOWN LINK -->
      
                <!-- SIMPLE DROPDOWN LINK -->
                <p class="simple-dropdown-link">Report Post</p>
                <!-- /SIMPLE DROPDOWN LINK -->
      
                <!-- SIMPLE DROPDOWN LINK -->
                <p class="simple-dropdown-link">Report Author</p>
                <!-- /SIMPLE DROPDOWN LINK -->
              </div>
              <!-- /SIMPLE DROPDOWN -->
            </div>
            <!-- /POST SETTINGS WRAP -->
          </div>
          <!-- /WIDGET BOX SETTINGS -->
          
          <!-- WIDGET BOX STATUS -->
          <div class="widget-box-status">
            <!-- TEXT STICKER -->
            <p class="text-sticker medium round">
              <!-- TEXT STICKER ICON -->
              <svg class="text-sticker-icon icon-trophy">
                <use xlink:href="#svg-trophy"></use>
              </svg>
              <!-- TEXT STICKER ICON -->

              <!-- TEXT STICKER CONTENT -->
              <span class="text-sticker-content">Most Popular Post</span>
              <!-- /TEXT STICKER CONTENT -->
            </p>
            <!-- /TEXT STICKER -->

            <!-- WIDGET BOX STATUS CONTENT -->
            <div class="widget-box-status-content">
              <!-- USER STATUS -->
              <div class="user-status">
                <!-- USER STATUS AVATAR -->
                <a class="user-status-avatar" href="javascript:void(0)">
                  <!-- USER AVATAR -->
                  <div class="user-avatar small no-outline">
                    <!-- USER AVATAR CONTENT -->
                    <div class="user-avatar-content">
                      <!-- HEXAGON -->
                      <div class="hexagon-image-30-32" data-src="{{ $hhFeaturedPost['avatar'] ?? $hhAdminAvatar }}"></div>
                      <!-- /HEXAGON -->
                    </div>
                    <!-- /USER AVATAR CONTENT -->
                
                    <!-- USER AVATAR PROGRESS -->
                    <div class="user-avatar-progress">
                      <!-- HEXAGON -->
                      <div class="hexagon-progress-40-44"></div>
                      <!-- /HEXAGON -->
                    </div>
                    <!-- /USER AVATAR PROGRESS -->
                
                    <!-- USER AVATAR PROGRESS BORDER -->
                    <div class="user-avatar-progress-border">
                      <!-- HEXAGON -->
                      <div class="hexagon-border-40-44"></div>
                      <!-- /HEXAGON -->
                    </div>
                    <!-- /USER AVATAR PROGRESS BORDER -->
                
                    <!-- USER AVATAR BADGE -->
                    <div class="user-avatar-badge">
                      <!-- USER AVATAR BADGE BORDER -->
                      <div class="user-avatar-badge-border">
                        <!-- HEXAGON -->
                        <div class="hexagon-22-24"></div>
                        <!-- /HEXAGON -->
                      </div>
                      <!-- /USER AVATAR BADGE BORDER -->
                
                      <!-- USER AVATAR BADGE CONTENT -->
                      <div class="user-avatar-badge-content">
                        <!-- HEXAGON -->
                        <div class="hexagon-dark-16-18"></div>
                        <!-- /HEXAGON -->
                      </div>
                      <!-- /USER AVATAR BADGE CONTENT -->
                
                      <!-- USER AVATAR BADGE TEXT -->
                      <p class="user-avatar-badge-text">{{ $hhNum($hhFeaturedPost['level'] ?? $hhAdminLevel) }}</p>
                      <!-- /USER AVATAR BADGE TEXT -->
                    </div>
                    <!-- /USER AVATAR BADGE -->
                  </div>
                  <!-- /USER AVATAR -->
                </a>
                <!-- /USER STATUS AVATAR -->
            
                <!-- USER STATUS TITLE -->
                <p class="user-status-title medium"><a class="bold" href="{{ $hhFeaturedPost['url'] ?? 'javascript:void(0)' }}">{{ $hhFeaturedPost['author'] ?? $hhAdminName }}</a></p>
                <!-- /USER STATUS TITLE -->
            
                <!-- USER STATUS TEXT -->
                <p class="user-status-text small">{{ $hhFeaturedPost['time'] ?? '17 hours ago' }}</p>
                <!-- /USER STATUS TEXT -->
              </div>
              <!-- /USER STATUS -->
      
              <!-- WIDGET BOX STATUS TEXT -->
              <p class="widget-box-status-text">{{ $hhFeaturedPost['excerpt'] ?? 'Tomorow I\'ll be livestreaming along with @DestroyDex on my Youtube channel. We are gonna do a spedrun of Super Mochi Bros 3!' }}</p>
              <!-- /WIDGET BOX STATUS TEXT -->
      
              <!-- VIDEO STATUS -->
              <a class="video-status" href="{{ $hhFeaturedPost['url'] ?? 'https://www.youtube.com/' }}">
                <!-- VIDEO STATUS IMAGE -->
                <img class="video-status-image" src="{{ (!empty($hhFeaturedPost['media_url']) && empty($hhFeaturedPost['media_is_video'])) ? $hhFeaturedPost['media_url'] : asset('assets/vikinger/img/cover/50.jpg') }}" alt="featured-post">
                <!-- /VIDEO STATUS IMAGE -->
      
                <!-- VIDEO STATUS INFO -->
                <div class="video-status-info">
                  <!-- VIDEO STATUS META -->
                  <p class="video-status-meta">{{ $hhFeaturedPost ? 'hnt.rocks' : 'youtube.com' }}</p>
                  <!-- /VIDEO STATUS META -->
      
                  <!-- VIDEO STATUS TITLE -->
                  <p class="video-status-title"><span class="bold">{{ $hhFeaturedPost ? 'Beliebter Beitrag' : 'GameHuntress' }}</span> <span class="highlighted">{{ $hhFeaturedPost['visibility'] ?? 'Youtube' }}</span></p>
                  <!-- /VIDEO STATUS TITLE -->
      
                  <!-- VIDEO STATUS TEXT -->
                  <p class="video-status-text">{{ $hhFeaturedPost['body'] ?? 'Watch the GameHuntress play all the greatest games.' }}</p>
                  <!-- /VIDEO STATUS TEXT -->
                </div>
                <!-- /VIDEO STATUS INFO -->
              </a>
              <!-- /VIDEO STATUS -->
      
              <!-- TAG LIST -->
              <div class="tag-list">
                <!-- TAG ITEM -->
                <a class="tag-item secondary" href="{{ $hhFeaturedPost['url'] ?? 'javascript:void(0)' }}">Feed</a>
                <!-- /TAG ITEM -->
      
                <!-- TAG ITEM -->
                <a class="tag-item secondary" href="{{ $hhFeaturedPost['url'] ?? 'javascript:void(0)' }}">{{ $hhFeaturedPost['visibility'] ?? 'Youtube' }}</a>
                <!-- /TAG ITEM -->
      
                <!-- TAG ITEM -->
                <a class="tag-item secondary" href="{{ $hhFeaturedPost['url'] ?? 'javascript:void(0)' }}">hnt.rocks</a>
                <!-- /TAG ITEM -->
      
                <!-- TAG ITEM -->
                <a class="tag-item secondary" href="javascript:void(0)">Retro</a>
                <!-- /TAG ITEM -->
              </div>
              <!-- /TAG LIST -->
      
              <!-- CONTENT ACTIONS -->
              <div class="content-actions">
                <!-- CONTENT ACTION -->
                <div class="content-action">
                  <!-- META LINE -->
                  <div class="meta-line">
                    <!-- META LINE LIST -->
                    <div class="meta-line-list reaction-item-list">
                      <!-- REACTION ITEM -->
                      <div class="reaction-item">
                        <!-- REACTION IMAGE -->
                        <img class="reaction-image reaction-item-dropdown-trigger" src="{{ asset('assets/vikinger/img/reaction/happy.png') }}" alt="reaction-happy">
                        <!-- /REACTION IMAGE -->
            
                        <!-- SIMPLE DROPDOWN -->
                        <div class="simple-dropdown padded reaction-item-dropdown">
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text"><img class="reaction" src="{{ asset('assets/vikinger/img/reaction/happy.png') }}" alt="reaction-happy"> <span class="bold">Happy</span></p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
                        
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Matt Parker</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Destroy Dex</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">The Green Goo</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
                        </div>
                        <!-- /SIMPLE DROPDOWN -->
                      </div>
                      <!-- /REACTION ITEM -->
      
                      <!-- REACTION ITEM -->
                      <div class="reaction-item">
                        <!-- REACTION IMAGE -->
                        <img class="reaction-image reaction-item-dropdown-trigger" src="{{ asset('assets/vikinger/img/reaction/love.png') }}" alt="reaction-love">
                        <!-- /REACTION IMAGE -->
            
                        <!-- SIMPLE DROPDOWN -->
                        <div class="simple-dropdown padded reaction-item-dropdown">
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text"><img class="reaction" src="{{ asset('assets/vikinger/img/reaction/love.png') }}" alt="reaction-love"> <span class="bold">Love</span></p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
                        
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Sandra Strange</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Jane Rodgers</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
                        </div>
                        <!-- /SIMPLE DROPDOWN -->
                      </div>
                      <!-- /REACTION ITEM -->
      
                      <!-- REACTION ITEM -->
                      <div class="reaction-item">
                        <!-- REACTION IMAGE -->
                        <img class="reaction-image reaction-item-dropdown-trigger" src="{{ asset('assets/vikinger/img/reaction/funny.png') }}" alt="reaction-funny">
                        <!-- /REACTION IMAGE -->
            
                        <!-- SIMPLE DROPDOWN -->
                        <div class="simple-dropdown padded reaction-item-dropdown">
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text"><img class="reaction" src="{{ asset('assets/vikinger/img/reaction/funny.png') }}" alt="reaction-funny"> <span class="bold">Funny</span></p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
                        
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Neko Bebop</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Nick Grissom</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Sarah Diamond</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Jett Spiegel</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text">Marcus Jhonson</p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
      
                          <!-- SIMPLE DROPDOWN TEXT -->
                          <p class="simple-dropdown-text"><span class="bold">and 12 more...</span></p>
                          <!-- /SIMPLE DROPDOWN TEXT -->
                        </div>
                        <!-- /SIMPLE DROPDOWN -->
                      </div>
                      <!-- /REACTION ITEM -->
                    </div>
                    <!-- /META LINE LIST -->
            
                    <!-- META LINE TEXT -->
                    <p class="meta-line-text">22</p>
                    <!-- /META LINE TEXT -->
                  </div>
                  <!-- /META LINE -->
            
                  <!-- META LINE -->
                  <div class="meta-line">
                    <!-- META LINE LIST -->
                    <div class="meta-line-list user-avatar-list">
                      <!-- USER AVATAR -->
                      <div class="user-avatar micro no-stats">
                        <!-- USER AVATAR BORDER -->
                        <div class="user-avatar-border">
                          <!-- HEXAGON -->
                          <div class="hexagon-22-24"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR BORDER -->
                    
                        <!-- USER AVATAR CONTENT -->
                        <div class="user-avatar-content">
                          <!-- HEXAGON -->
                          <div class="hexagon-image-18-20" data-src="{{ asset('assets/vikinger/img/avatar/09.jpg') }}"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR CONTENT -->
                      </div>
                      <!-- /USER AVATAR -->
            
                      <!-- USER AVATAR -->
                      <div class="user-avatar micro no-stats">
                        <!-- USER AVATAR BORDER -->
                        <div class="user-avatar-border">
                          <!-- HEXAGON -->
                          <div class="hexagon-22-24"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR BORDER -->
                    
                        <!-- USER AVATAR CONTENT -->
                        <div class="user-avatar-content">
                          <!-- HEXAGON -->
                          <div class="hexagon-image-18-20" data-src="{{ asset('assets/vikinger/img/avatar/08.jpg') }}"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR CONTENT -->
                      </div>
                      <!-- /USER AVATAR -->
            
                      <!-- USER AVATAR -->
                      <div class="user-avatar micro no-stats">
                        <!-- USER AVATAR BORDER -->
                        <div class="user-avatar-border">
                          <!-- HEXAGON -->
                          <div class="hexagon-22-24"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR BORDER -->
                    
                        <!-- USER AVATAR CONTENT -->
                        <div class="user-avatar-content">
                          <!-- HEXAGON -->
                          <div class="hexagon-image-18-20" data-src="{{ asset('assets/vikinger/img/avatar/12.jpg') }}"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR CONTENT -->
                      </div>
                      <!-- /USER AVATAR -->
            
                      <!-- USER AVATAR -->
                      <div class="user-avatar micro no-stats">
                        <!-- USER AVATAR BORDER -->
                        <div class="user-avatar-border">
                          <!-- HEXAGON -->
                          <div class="hexagon-22-24"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR BORDER -->
                    
                        <!-- USER AVATAR CONTENT -->
                        <div class="user-avatar-content">
                          <!-- HEXAGON -->
                          <div class="hexagon-image-18-20" data-src="{{ asset('assets/vikinger/img/avatar/16.jpg') }}"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR CONTENT -->
                      </div>
                      <!-- /USER AVATAR -->
            
                      <!-- USER AVATAR -->
                      <div class="user-avatar micro no-stats">
                        <!-- USER AVATAR BORDER -->
                        <div class="user-avatar-border">
                          <!-- HEXAGON -->
                          <div class="hexagon-22-24"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR BORDER -->
                    
                        <!-- USER AVATAR CONTENT -->
                        <div class="user-avatar-content">
                          <!-- HEXAGON -->
                          <div class="hexagon-image-18-20" data-src="{{ asset('assets/vikinger/img/avatar/06.jpg') }}"></div>
                          <!-- /HEXAGON -->
                        </div>
                        <!-- /USER AVATAR CONTENT -->
                      </div>
                      <!-- /USER AVATAR -->
                    </div>
                    <!-- /META LINE LIST -->
            
                    <!-- META LINE TEXT -->
                    <p class="meta-line-text">{{ $hhNum($hhFeaturedPost['reactions_count'] ?? 30) }} Reaktionen</p>
                    <!-- /META LINE TEXT -->
                  </div>
                  <!-- /META LINE -->
                </div>
                <!-- /CONTENT ACTION -->
            
                <!-- CONTENT ACTION -->
                <div class="content-action">
                  <!-- META LINE -->
                  <div class="meta-line">
                    <!-- META LINE LINK -->
                    <p class="meta-line-link">{{ $hhNum($hhFeaturedPost['comments_count'] ?? 12) }} Comments</p>
                    <!-- /META LINE LINK -->
                  </div>
                  <!-- /META LINE -->
            
                  <!-- META LINE -->
                  <div class="meta-line">
                    <!-- META LINE TEXT -->
                    <p class="meta-line-text">{{ $hhNum($hhFeaturedPost['shares_count'] ?? 0) }} Shares</p>
                    <!-- /META LINE TEXT -->
                  </div>
                  <!-- /META LINE -->
                </div>
                <!-- /CONTENT ACTION -->
              </div>
              <!-- /CONTENT ACTIONS -->
            </div>
            <!-- /WIDGET BOX STATUS CONTENT -->
          </div>
          <!-- /WIDGET BOX STATUS -->
      
          <!-- POST OPTIONS -->
          <div class="post-options">
            <!-- POST OPTION WRAP -->
            <div class="post-option-wrap">
              <!-- POST OPTION -->
              <div class="post-option reaction-options-dropdown-trigger">
                <!-- POST OPTION ICON -->
                <svg class="post-option-icon icon-thumbs-up">
                  <use xlink:href="#svg-thumbs-up"></use>
                </svg>
                <!-- /POST OPTION ICON -->
      
                <!-- POST OPTION TEXT -->
                <p class="post-option-text">React!</p>
                <!-- /POST OPTION TEXT -->
              </div>
              <!-- /POST OPTION -->
      
              <!-- REACTION OPTIONS -->
              <div class="reaction-options reaction-options-dropdown">
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Like">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/like.png') }}" alt="reaction-like">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
      
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Love">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/love.png') }}" alt="reaction-love">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
      
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Dislike">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/dislike.png') }}" alt="reaction-dislike">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
      
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Happy">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/happy.png') }}" alt="reaction-happy">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
      
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Funny">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/funny.png') }}" alt="reaction-funny">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
      
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Wow">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/wow.png') }}" alt="reaction-wow">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
      
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Angry">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/angry.png') }}" alt="reaction-angry">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
      
                <!-- REACTION OPTION -->
                <div class="reaction-option text-tooltip-tft" data-title="Sad">
                  <!-- REACTION OPTION IMAGE -->
                  <img class="reaction-option-image" src="{{ asset('assets/vikinger/img/reaction/sad.png') }}" alt="reaction-sad">
                  <!-- /REACTION OPTION IMAGE -->
                </div>
                <!-- /REACTION OPTION -->
              </div>
              <!-- /REACTION OPTIONS -->
            </div>
            <!-- /POST OPTION WRAP -->
      
            <!-- POST OPTION -->
            <div class="post-option">
              <!-- POST OPTION ICON -->
              <svg class="post-option-icon icon-comment">
                <use xlink:href="#svg-comment"></use>
              </svg>
              <!-- /POST OPTION ICON -->
      
              <!-- POST OPTION TEXT -->
              <p class="post-option-text">Comment</p>
              <!-- /POST OPTION TEXT -->
            </div>
            <!-- /POST OPTION -->
      
            <!-- POST OPTION -->
            <div class="post-option">
              <!-- POST OPTION ICON -->
              <svg class="post-option-icon icon-share">
                <use xlink:href="#svg-share"></use>
              </svg>
              <!-- /POST OPTION ICON -->
      
              <!-- POST OPTION TEXT -->
              <p class="post-option-text">Share</p>
              <!-- /POST OPTION TEXT -->
            </div>
            <!-- /POST OPTION -->
          </div>
          <!-- /POST OPTIONS -->
        </div>
        <!-- /WIDGET BOX -->

        <!-- WIDGET BOX -->
        <div class="widget-box no-padding">
          <!-- WIDGET BOX TITLE -->
          <p class="widget-box-title">Personal Activity</p>
          <!-- /WIDGET BOX TITLE -->
      
          <!-- WIDGET BOX CONTENT -->
          <div class="widget-box-content padded-for-scroll medium" data-simplebar>
            <!-- USER STATUS LIST -->
            <div class="user-status-list scroll-content">
              @forelse($hhPersonalActivity->take(10) as $hhPersonalItem)
                <!-- USER STATUS -->
                <div class="user-status">
                  <!-- USER STATUS ACTIVITY -->
                  <div class="user-status-activity {{ ($hhPersonalItem['icon'] ?? 'status') === 'comment' ? 'activity-comment' : (($hhPersonalItem['icon'] ?? 'status') === 'members' ? 'activity-update' : 'activity-reaction') }}">
                    <!-- USER STATUS ACTIVITY ICON -->
                    <svg class="user-status-activity-icon icon-{{ $hhPersonalItem['icon'] ?? 'status' }}">
                      <use xlink:href="#svg-{{ $hhPersonalItem['icon'] ?? 'status' }}"></use>
                    </svg>
                    <!-- /USER STATUS ACTIVITY ICON -->
                  </div>
                  <!-- /USER STATUS ACTIVITY -->
              
                  <!-- USER STATUS TITLE -->
                  <p class="user-status-title"><a class="bold" href="{{ $hhPersonalItem['url'] ?? 'javascript:void(0)' }}">{{ $hhPersonalItem['name'] ?? 'System' }}</a> {{ $hhPersonalItem['action'] ?? 'hat eine Aktivität erstellt' }} <a class="highlighted" href="{{ $hhPersonalItem['url'] ?? 'javascript:void(0)' }}">{{ $hhPersonalItem['title'] ?? 'hnt.rocks' }}</a></p>
                  <!-- /USER STATUS TITLE -->
              
                  <!-- USER STATUS TIMESTAMP -->
                  <p class="user-status-timestamp small-space">{{ $hhPersonalItem['time'] ?? '-' }}</p>
                  <!-- /USER STATUS TIMESTAMP -->
                </div>
                <!-- /USER STATUS -->
              @empty

                <!-- USER STATUS -->
                <div class="user-status">
                  <div class="user-status-activity activity-update">
                    <svg class="user-status-activity-icon icon-members"><use xlink:href="#svg-members"></use></svg>
                  </div>
                  <p class="user-status-title"><a class="bold" href="javascript:void(0)">Marina Valentine</a> updated her <a class="highlighted" href="javascript:void(0)">profile picture</a></p>
                  <p class="user-status-timestamp small-space">3 minutes ago</p>
                </div>
                <!-- /USER STATUS -->
              @endforelse
            </div>
            <!-- /USER STATUS LIST -->
          </div>
          <!-- WIDGET BOX CONTENT -->
        </div>
        <!-- /WIDGET BOX -->
      </div>
      <!-- /GRID -->

      <!-- SLIDER LINE -->
      <div class="slider-line">
        <!-- SLIDER CONTROLS -->
        <div id="stat-block-slider-controls" class="slider-controls">
          <!-- SLIDER CONTROL -->
          <div class="slider-control left">
            <!-- SLIDER CONTROL ICON -->
            <svg class="slider-control-icon icon-small-arrow">
              <use xlink:href="#svg-small-arrow"></use>
            </svg>
            <!-- /SLIDER CONTROL ICON -->
          </div>
          <!-- /SLIDER CONTROL -->
    
          <!-- SLIDER CONTROL -->
          <div class="slider-control right">
            <!-- SLIDER CONTROL ICON -->
            <svg class="slider-control-icon icon-small-arrow">
              <use xlink:href="#svg-small-arrow"></use>
            </svg>
            <!-- /SLIDER CONTROL ICON -->
          </div>
          <!-- /SLIDER CONTROL -->
        </div>
        <!-- /SLIDER CONTROLS -->
    
        <!-- SLIDER SLIDES -->
        <div id="stat-block-slider" class="slider-slides">
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- STAT BLOCK -->
            <div class="stat-block">
              <!-- STAT BLOCK DECORATION -->
              <div class="stat-block-decoration">
                <!-- STAT BLOCK DECORATION ICON -->
                <svg class="stat-block-decoration-icon icon-friend">
                  <use xlink:href="#svg-friend"></use>
                </svg>
                <!-- /STAT BLOCK DECORATION ICON -->
              </div>
              <!-- /STAT BLOCK DECORATION -->
    
              <!-- STAT BLOCK INFO -->
              <div class="stat-block-info">
                <!-- STAT BLOCK TITLE -->
                <p class="stat-block-title">Last friend added</p>
                <!-- /STAT BLOCK TITLE -->
    
                <!-- STAT BLOCK TEXT -->
                <p class="stat-block-text">5 Days Ago</p>
                <!-- /STAT BLOCK TEXT -->
              </div>
              <!-- /STAT BLOCK INFO -->
            </div>
            <!-- /STAT BLOCK -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- STAT BLOCK -->
            <div class="stat-block">
              <!-- STAT BLOCK DECORATION -->
              <div class="stat-block-decoration">
                <!-- STAT BLOCK DECORATION ICON -->
                <svg class="stat-block-decoration-icon icon-status">
                  <use xlink:href="#svg-status"></use>
                </svg>
                <!-- /STAT BLOCK DECORATION ICON -->
              </div>
              <!-- /STAT BLOCK DECORATION -->
    
              <!-- STAT BLOCK INFO -->
              <div class="stat-block-info">
                <!-- STAT BLOCK TITLE -->
                <p class="stat-block-title">Last post update</p>
                <!-- /STAT BLOCK TITLE -->
    
                <!-- STAT BLOCK TEXT -->
                <p class="stat-block-text">1 Day Ago</p>
                <!-- /STAT BLOCK TEXT -->
              </div>
              <!-- /STAT BLOCK INFO -->
            </div>
            <!-- /STAT BLOCK -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- STAT BLOCK -->
            <div class="stat-block">
              <!-- STAT BLOCK DECORATION -->
              <div class="stat-block-decoration">
                <!-- STAT BLOCK DECORATION ICON -->
                <svg class="stat-block-decoration-icon icon-comment">
                  <use xlink:href="#svg-comment"></use>
                </svg>
                <!-- /STAT BLOCK DECORATION ICON -->
              </div>
              <!-- /STAT BLOCK DECORATION -->
    
              <!-- STAT BLOCK INFO -->
              <div class="stat-block-info">
                <!-- STAT BLOCK TITLE -->
                <p class="stat-block-title">Most commented post</p>
                <!-- /STAT BLOCK TITLE -->
    
                <!-- STAT BLOCK TEXT -->
                <p class="stat-block-text">56 Comments</p>
                <!-- /STAT BLOCK TEXT -->
              </div>
              <!-- /STAT BLOCK INFO -->
            </div>
            <!-- /STAT BLOCK -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- STAT BLOCK -->
            <div class="stat-block">
              <!-- STAT BLOCK DECORATION -->
              <div class="stat-block-decoration">
                <!-- STAT BLOCK DECORATION ICON -->
                <svg class="stat-block-decoration-icon icon-thumbs-up">
                  <use xlink:href="#svg-thumbs-up"></use>
                </svg>
                <!-- /STAT BLOCK DECORATION ICON -->
              </div>
              <!-- /STAT BLOCK DECORATION -->
    
              <!-- STAT BLOCK INFO -->
              <div class="stat-block-info">
                <!-- STAT BLOCK TITLE -->
                <p class="stat-block-title">Most liked post</p>
                <!-- /STAT BLOCK TITLE -->
    
                <!-- STAT BLOCK TEXT -->
                <p class="stat-block-text">904 Likes</p>
                <!-- /STAT BLOCK TEXT -->
              </div>
              <!-- /STAT BLOCK INFO -->
            </div>
            <!-- /STAT BLOCK -->
          </div>
          <!-- /SLIDER SLIDE -->
    
          <!-- SLIDER SLIDE -->
          <div class="slider-slide">
            <!-- STAT BLOCK -->
            <div class="stat-block">
              <!-- STAT BLOCK DECORATION -->
              <div class="stat-block-decoration">
                <!-- STAT BLOCK DECORATION ICON -->
                <svg class="stat-block-decoration-icon icon-share">
                  <use xlink:href="#svg-share"></use>
                </svg>
                <!-- /STAT BLOCK DECORATION ICON -->
              </div>
              <!-- /STAT BLOCK DECORATION -->
    
              <!-- STAT BLOCK INFO -->
              <div class="stat-block-info">
                <!-- STAT BLOCK TITLE -->
                <p class="stat-block-title">Most shared post</p>
                <!-- /STAT BLOCK TITLE -->
    
                <!-- STAT BLOCK TEXT -->
                <p class="stat-block-text">156 Shares</p>
                <!-- /STAT BLOCK TEXT -->
              </div>
              <!-- /STAT BLOCK INFO -->
            </div>
            <!-- /STAT BLOCK -->
          </div>
          <!-- /SLIDER SLIDE -->
        </div>
        <!-- /SLIDER SLIDES -->
      </div>
      <!-- /SLIDER LINE -->
    </div>
    <!-- /GRID -->

    <!-- SECTION HEADER -->
    <div class="section-header">
      <!-- SECTION HEADER INFO -->
      <div class="section-header-info">
        <!-- SECTION PRETITLE -->
        <p class="section-pretitle">Overview</p>
        <!-- /SECTION PRETITLE -->
  
        <!-- SECTION TITLE -->
        <h2 class="section-title">Gamification</h2>
        <!-- /SECTION TITLE -->
      </div>
      <!-- /SECTION HEADER INFO -->
    </div>
    <!-- /SECTION HEADER -->

    <!-- GRID -->
    <div class="grid">
      <!-- GRID -->
      <div class="grid grid-half change-on-desktop">
        <!-- ACHIEVEMENT BOX -->
        <div class="achievement-box secondary">
          <!-- ACHIEVEMENT BOX INFO WRAP -->
          <div class="achievement-box-info-wrap">
            <!-- ACHIEVEMENT BOX IMAGE -->
            <img class="achievement-box-image" src="{{ asset('assets/vikinger/img/badge/caffeinated-b.png') }}" alt="badge-caffeinated-b">
            <!-- /ACHIEVEMENT BOX IMAGE -->
      
            <!-- ACHIEVEMENT BOX INFO -->
            <div class="achievement-box-info">
              <!-- ACHIEVEMENT BOX TITLE -->
              <p class="achievement-box-title">Last Badge Unlocked</p>
              <!-- /ACHIEVEMENT BOX TITLE -->
      
              <!-- ACHIEVEMENT BOX TEXT -->
              <p class="achievement-box-text">{!! $hhLatestBadge ? '<span class="bold">' . e($hhLatestBadge['title'] ?? 'Badge') . '</span> ' . e($hhLatestBadge['date'] ?? '') : '<span class="bold">Noch kein Badge</span> vergeben' !!}</p>
              <!-- /ACHIEVEMENT BOX TEXT -->
            </div>
            <!-- /ACHIEVEMENT BOX INFO -->
          </div>
          <!-- /ACHIEVEMENT BOX INFO WRAP -->
      
          <!-- BUTTON -->
          <a class="button white-solid" href="javascript:void(0)">Browse All</a>
          <!-- /BUTTON -->
        </div>
        <!-- /ACHIEVEMENT BOX -->
      
        <!-- ACHIEVEMENT BOX -->
        <div class="achievement-box primary">
          <!-- ACHIEVEMENT BOX INFO WRAP -->
          <div class="achievement-box-info-wrap">
            <!-- ACHIEVEMENT BOX IMAGE -->
            <img class="achievement-box-image" src="{{ asset('assets/vikinger/img/quest/completedq-l.png') }}" alt="quest-completedq-l">
            <!-- /ACHIEVEMENT BOX IMAGE -->
      
            <!-- ACHIEVEMENT BOX INFO -->
            <div class="achievement-box-info">
              <!-- ACHIEVEMENT BOX TITLE -->
              <p class="achievement-box-title">Last Completed Quest</p>
              <!-- /ACHIEVEMENT BOX TITLE -->
      
              <!-- ACHIEVEMENT BOX TEXT -->
              <p class="achievement-box-text">{!! $hhLatestQuest ? '<span class="bold">' . e($hhLatestQuest['title'] ?? 'Quest') . '</span> ' . e($hhLatestQuest['date'] ?? '') : '<span class="bold">Noch keine Quest</span> abgeschlossen' !!}</p>
              <!-- /ACHIEVEMENT BOX TEXT -->
            </div>
            <!-- /ACHIEVEMENT BOX INFO -->
          </div>
          <!-- /ACHIEVEMENT BOX INFO WRAP -->
      
          <!-- BUTTON -->
          <a class="button white-solid" href="javascript:void(0)">Browse All</a>
          <!-- /BUTTON -->
        </div>
        <!-- /ACHIEVEMENT BOX -->
      </div>
      <!-- /GRID -->

      <!-- GRID -->
      <div class="grid grid-3-9">
        <!-- GRID COLUMN -->
        <div class="grid-column">
          <!-- WIDGET BOX -->
          <div class="widget-box">
            <!-- PROGRESS ARC SUMMARY -->
            <div class="progress-arc-summary">
              <!-- PROGRESS ARC WRAP -->
              <div class="progress-arc-wrap">
                <!-- PROGRESS ARC -->
                <div class="progress-arc">
                  <canvas id="profile-completion-chart"></canvas>
                </div>
                <!-- PROGRESS ARC -->
          
                <!-- PROGRESS ARC INFO -->
                <div class="progress-arc-info">
                  <!-- PROGRESS ARC TITLE -->
                  <p class="progress-arc-title">{{ $hhGamificationCompletion }}%</p>
                  <!-- /PROGRESS ARC TITLE -->
                </div>
                <!-- /PROGRESS ARC INFO -->
              </div>
              <!-- /PROGRESS ARC WRAP -->
          
              <!-- PROGRESS ARC SUMMARY INFO -->
              <div class="progress-arc-summary-info">
                <!-- PROGRESS ARC SUMMARY TITLE -->
                <p class="progress-arc-summary-title">Gamification Progress</p>
                <!-- /PROGRESS ARC SUMMARY TITLE -->
          
                <!-- PROGRESS ARC SUMMARY TITLE -->
                <p class="progress-arc-summary-subtitle">hnt.rocks gesamt</p>
                <!-- /PROGRESS ARC SUMMARY TITLE -->
          
                <!-- PROGRESS ARC SUMMARY TITLE -->
                <p class="progress-arc-summary-text">Fortschritt aus vergebenen Badges, aktiven Quests und abgeschlossenen Quest-Fortschritten.</p>
                <!-- /PROGRESS ARC SUMMARY TITLE -->
              </div>
              <!-- /PROGRESS ARC SUMMARY INFO -->
            </div>
            <!-- /PROGRESS ARC SUMMARY -->

            <!-- ACHIEVEMENT STATUS LIST -->
            <div class="achievement-status-list">
              <!-- ACHIEVEMENT STATUS -->
              <div class="achievement-status">
                <!-- ACHIEVEMENT STATUS PROGRESS -->
                <p class="achievement-status-progress">{{ $hhNum($hhQuestsCompleted) }}/{{ $hhNum($hhQuestsActive) }}</p>
                <!-- /ACHIEVEMENT STATUS PROGRESS -->

                <!-- ACHIEVEMENT STATUS INFO -->
                <div class="achievement-status-info">
                  <!-- ACHIEVEMENT STATUS TITLE -->
                  <p class="achievement-status-title">Quests</p>
                  <!-- /ACHIEVEMENT STATUS TITLE -->
                  
                  <!-- ACHIEVEMENT STATUS TEXT -->
                  <p class="achievement-status-text">Completed</p>
                  <!-- /ACHIEVEMENT STATUS TEXT -->
                </div>
                <!-- /ACHIEVEMENT STATUS INFO -->

                <!-- ACHIEVEMENT STATUS IMAGE -->
                <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/completedq-s.png') }}" alt="bdage-completedq-s">
                <!-- /ACHIEVEMENT STATUS IMAGE -->
              </div>
              <!-- /ACHIEVEMENT STATUS -->

              <!-- ACHIEVEMENT STATUS -->
              <div class="achievement-status">
                <!-- ACHIEVEMENT STATUS PROGRESS -->
                <p class="achievement-status-progress">{{ $hhNum($hhBadgesAwarded) }}/{{ $hhNum($hhBadgesTotal) }}</p>
                <!-- /ACHIEVEMENT STATUS PROGRESS -->

                <!-- ACHIEVEMENT STATUS INFO -->
                <div class="achievement-status-info">
                  <!-- ACHIEVEMENT STATUS TITLE -->
                  <p class="achievement-status-title">Badges</p>
                  <!-- /ACHIEVEMENT STATUS TITLE -->
                  
                  <!-- ACHIEVEMENT STATUS TEXT -->
                  <p class="achievement-status-text">Unlocked</p>
                  <!-- /ACHIEVEMENT STATUS TEXT -->
                </div>
                <!-- /ACHIEVEMENT STATUS INFO -->

                <!-- ACHIEVEMENT STATUS IMAGE -->
                <img class="achievement-status-image" src="{{ asset('assets/vikinger/img/badge/unlocked-badge.png') }}" alt="bdage-unlocked-badge">
                <!-- /ACHIEVEMENT STATUS IMAGE -->
              </div>
              <!-- /ACHIEVEMENT STATUS -->
            </div>
            <!-- /ACHIEVEMENT STATUS LIST -->
          </div>
          <!-- /WIDGET BOX -->
        </div>
        <!-- /GRID COLUMN -->

        <!-- GRID COLUMN -->
        <div class="grid-column">
          <!-- LEVEL PROGRESS BOX -->
          <div class="level-progress-box">
            <!-- LEVEL PROGRESS BADGE -->
            <div class="level-progress-badge">
              <!-- LEVEL PROGRESS BADGE TITLE -->
              <p class="level-progress-badge-title">Level</p>
              <!-- /LEVEL PROGRESS BADGE TITLE -->
        
              <!-- LEVEL PROGRESS BADGE TEXT -->
              <p class="level-progress-badge-text">{{ $hhNum($hhHighestLevel) }}</p>
              <!-- /LEVEL PROGRESS BADGE TEXT -->
            </div>
            <!-- /LEVEL PROGRESS BADGE -->
        
            <!-- PROGRESS STAT -->
            <div class="progress-stat">
              <!-- BAR PROGRESS WRAP -->
              <div class="bar-progress-wrap big">
                <!-- BAR PROGRESS INFO -->
                <p class="bar-progress-info start negative progress-with-text"><span class="bar-progress-text"></span><span class="light">höchstes Nutzer-Level</span></p>
                <!-- /BAR PROGRESS INFO -->
          
                <!-- PROGRESS STAT INFO -->
                <p class="progress-stat-info">{{ $hhNum($hhXpTotal) }} total exp points received</p>
                <!-- /PROGRESS STAT INFO -->
              </div>
              <!-- /BAR PROGRESS WRAP -->
          
              <!-- PROGRESS STAT BAR -->
              <div id="exp-to-next-level" class="progress-stat-bar"></div>
              <!-- /PROGRESS STAT BAR -->
            </div>
            <!-- /PROGRESS STAT -->
          </div>
          <!-- /LEVEL PROGRESS BOX -->

          <!-- WIDGET BOX -->
          <div class="widget-box no-padding">
            <!-- WIDGET BOX TITLE -->
            <p class="widget-box-title">Experience History</p>
            <!-- /WIDGET BOX TITLE -->
        
            <!-- WIDGET BOX CONTENT -->
            <div class="widget-box-content small-margin-top padded-for-scroll small" data-simplebar>
              <!-- EXP LINE LIST -->
              <div class="exp-line-list scroll-content">
@forelse($hhXpEvents as $event)
                  <!-- EXP LINE -->
                  <div class="exp-line">
                    <!-- EXP LINE ICON -->
                    <svg class="exp-line-icon icon-{{ str_contains(strtolower((string) ($event['action'] ?? '')), 'quest') ? 'quests' : 'badges' }}">
                      <use xlink:href="#svg-{{ str_contains(strtolower((string) ($event['action'] ?? '')), 'quest') ? 'quests' : 'badges' }}"></use>
                    </svg>
                    <!-- /EXP LINE ICON -->

                    <!-- TEXT STICKER -->
                    <p class="text-sticker small-text">
                      <!-- TEXT STICKER ICON -->
                      <svg class="text-sticker-icon icon-plus-small">
                        <use xlink:href="#svg-plus-small"></use>
                      </svg>
                      <!-- TEXT STICKER ICON -->
                      {{ (($event['points'] ?? 0) > 0 ? '+' : '') . $hhNum($event['points'] ?? 0) }} EXP
                    </p>
                    <!-- /TEXT STICKER -->

                    <!-- EXP LINE TEXT -->
                    <p class="exp-line-text">{{ $event['action'] ?? 'XP Event' }} <span class="bold">{{ $event['user'] ?? $event['members'] ?? 'System' }}</span></p>
                    <!-- /EXP LINE TEXT -->

                    <!-- EXP LINE TIMESTAMP -->
                    <p class="exp-line-timestamp">{{ $event['date'] ?? '-' }}</p>
                    <!-- /EXP LINE TIMESTAMP -->
                  </div>
                  <!-- /EXP LINE -->
                @empty
                  <!-- EXP LINE -->
                  <div class="exp-line">
                    <!-- EXP LINE ICON -->
                    <svg class="exp-line-icon icon-badges">
                      <use xlink:href="#svg-badges"></use>
                    </svg>
                    <!-- /EXP LINE ICON -->

                    <!-- TEXT STICKER -->
                    <p class="text-sticker small-text">0 EXP</p>
                    <!-- /TEXT STICKER -->

                    <!-- EXP LINE TEXT -->
                    <p class="exp-line-text">Noch keine XP-Events vorhanden.</p>
                    <!-- /EXP LINE TEXT -->

                    <!-- EXP LINE TIMESTAMP -->
                    <p class="exp-line-timestamp">-</p>
                    <!-- /EXP LINE TIMESTAMP -->
                  </div>
                  <!-- /EXP LINE -->
                @endforelse
              </div>
              <!-- /EXP LINE LIST -->
            </div>
            <!-- WIDGET BOX CONTENT -->
          </div>
          <!-- /WIDGET BOX -->
        </div>
        <!-- /GRID COLUMN -->
      </div>
      <!-- /GRID -->
    </div>
    <!-- /GRID -->

    <!-- SECTION HEADER -->
    <div class="section-header">
      <!-- SECTION HEADER INFO -->
      <div class="section-header-info">
        <!-- SECTION PRETITLE -->
        <p class="section-pretitle">Overview</p>
        <!-- /SECTION PRETITLE -->
  
        <!-- SECTION TITLE -->
        <h2 class="section-title">Account Analytics</h2>
        <!-- /SECTION TITLE -->
      </div>
      <!-- /SECTION HEADER INFO -->
    </div>
    <!-- /SECTION HEADER -->

    <!-- GRID -->
    <div class="grid">
      <!-- GRID -->
      <div class="grid grid-3-3-3-3 centered">
        <!-- ACCOUNT STAT BOX -->
        <div class="account-stat-box account-stat-active-users">
          <!-- ACCOUNT STAT BOX ICON WRAP -->
          <div class="account-stat-box-icon-wrap">
            <!-- ACCOUNT STAT BOX ICON -->
            <svg class="account-stat-box-icon icon-friend">
              <use xlink:href="#svg-friend"></use>
            </svg>
            <!-- /ACCOUNT STAT BOX ICON -->
          </div>
          <!-- /ACCOUNT STAT BOX ICON WRAP -->
      
          <!-- ACCOUNT STAT BOX TITLE -->
          <p class="account-stat-box-title">{{ $hhNum($hhAnalytics['activeUsersNow'] ?? 0) }}</p>
          <!-- /ACCOUNT STAT BOX TITLE -->
      
          <!-- ACCOUNT STAT BOX SUBTITLE -->
          <p class="account-stat-box-subtitle">Unique Visitors</p>
          <!-- /ACCOUNT STAT BOX SUBTITLE -->
      
          <!-- ACCOUNT STAT BOX TEXT -->
          <p class="account-stat-box-text">Aktive Laravel-Sessions der letzten 5 Minuten.</p>
          <!-- /ACCOUNT STAT BOX TEXT -->
        </div>
        <!-- /ACCOUNT STAT BOX -->

        <!-- ACCOUNT STAT BOX -->
        <div class="account-stat-box account-stat-visits">
          <!-- PERCENTAGE DIFF -->
          <div class="percentage-diff">
            <!-- PERCENTAGE DIFF ICON WRAP -->
            <div class="percentage-diff-icon-wrap positive">
              <!-- PERCENTAGE DIFF ICON -->
              <svg class="percentage-diff-icon icon-plus-small">
                <use xlink:href="#svg-plus-small"></use>
              </svg>
              <!-- /PERCENTAGE DIFF ICON -->
            </div>
            <!-- /PERCENTAGE DIFF ICON WRAP -->
      
            <!-- PERCENTAGE DIFF TEXT -->
            <p class="percentage-diff-text">{{ $hhStatDiff($hhTopStats, 0, 'value', '0%') }}</p>
            <!-- /PERCENTAGE DIFF TEXT -->
          </div>
          <!-- /PERCENTAGE DIFF -->

          <!-- ACCOUNT STAT BOX ICON WRAP -->
          <div class="account-stat-box-icon-wrap">
            <!-- ACCOUNT STAT BOX ICON -->
            <svg class="account-stat-box-icon icon-members">
              <use xlink:href="#svg-members"></use>
            </svg>
            <!-- /ACCOUNT STAT BOX ICON -->
          </div>
          <!-- /ACCOUNT STAT BOX ICON WRAP -->
      
          <!-- ACCOUNT STAT BOX TITLE -->
          <p class="account-stat-box-title">{{ $hhNum($hhAnalytics['visitsMonth'] ?? $hhAnalytics['newUsersMonth'] ?? 0) }}</p>
          <!-- /ACCOUNT STAT BOX TITLE -->
      
          <!-- ACCOUNT STAT BOX SUBTITLE -->
          <p class="account-stat-box-subtitle">Visits Month</p>
          <!-- /ACCOUNT STAT BOX SUBTITLE -->
      
          <!-- ACCOUNT STAT BOX TEXT -->
          <p class="account-stat-box-text">Getrackte Seitenaufrufe im aktuellen Monat.</p>
          <!-- /ACCOUNT STAT BOX TEXT -->
        </div>
        <!-- /ACCOUNT STAT BOX -->

        <!-- ACCOUNT STAT BOX -->
        <div class="account-stat-box account-stat-session-duration">
          <!-- PERCENTAGE DIFF -->
          <div class="percentage-diff">
            <!-- PERCENTAGE DIFF ICON WRAP -->
            <div class="percentage-diff-icon-wrap negative">
              <!-- PERCENTAGE DIFF ICON -->
              <svg class="percentage-diff-icon icon-minus-small">
                <use xlink:href="#svg-minus-small"></use>
              </svg>
              <!-- /PERCENTAGE DIFF ICON -->
            </div>
            <!-- /PERCENTAGE DIFF ICON WRAP -->
      
            <!-- PERCENTAGE DIFF TEXT -->
            <p class="percentage-diff-text">{{ $hhStatDiff($hhTopStats, 1, 'value', '0%') }}</p>
            <!-- /PERCENTAGE DIFF TEXT -->
          </div>
          <!-- /PERCENTAGE DIFF -->

          <!-- ACCOUNT STAT BOX ICON WRAP -->
          <div class="account-stat-box-icon-wrap">
            <!-- ACCOUNT STAT BOX ICON -->
            <svg class="account-stat-box-icon icon-clock">
              <use xlink:href="#svg-clock"></use>
            </svg>
            <!-- /ACCOUNT STAT BOX ICON -->
          </div>
          <!-- /ACCOUNT STAT BOX ICON WRAP -->
      
          <!-- ACCOUNT STAT BOX TITLE -->
          <p class="account-stat-box-title">{{ $hhNum($hhAnalytics['uniqueVisitorsMonth'] ?? $hhAnalytics['activeUsersMonth'] ?? 0) }}</p>
          <!-- /ACCOUNT STAT BOX TITLE -->
      
          <!-- ACCOUNT STAT BOX SUBTITLE -->
          <p class="account-stat-box-subtitle">Unique Visitors</p>
          <!-- /ACCOUNT STAT BOX SUBTITLE -->
      
          <!-- ACCOUNT STAT BOX TEXT -->
          <p class="account-stat-box-text">Eindeutige Browser-Besucher über First-Party-Cookie.</p>
          <!-- /ACCOUNT STAT BOX TEXT -->
        </div>
        <!-- /ACCOUNT STAT BOX -->

        <!-- ACCOUNT STAT BOX -->
        <div class="account-stat-box account-stat-returning-visitors">
          <!-- PERCENTAGE DIFF -->
          <div class="percentage-diff">
            <!-- PERCENTAGE DIFF ICON WRAP -->
            <div class="percentage-diff-icon-wrap positive">
              <!-- PERCENTAGE DIFF ICON -->
              <svg class="percentage-diff-icon icon-plus-small">
                <use xlink:href="#svg-plus-small"></use>
              </svg>
              <!-- /PERCENTAGE DIFF ICON -->
            </div>
            <!-- /PERCENTAGE DIFF ICON WRAP -->
      
            <!-- PERCENTAGE DIFF TEXT -->
            <p class="percentage-diff-text">{{ $hhPct($hhAnalytics['returningUsersRate'] ?? 0) }}</p>
            <!-- /PERCENTAGE DIFF TEXT -->
          </div>
          <!-- /PERCENTAGE DIFF -->

          <!-- ACCOUNT STAT BOX ICON WRAP -->
          <div class="account-stat-box-icon-wrap">
            <!-- ACCOUNT STAT BOX ICON -->
            <svg class="account-stat-box-icon icon-return">
              <use xlink:href="#svg-return"></use>
            </svg>
            <!-- /ACCOUNT STAT BOX ICON -->
          </div>
          <!-- /ACCOUNT STAT BOX ICON WRAP -->
      
          <!-- ACCOUNT STAT BOX TITLE -->
          <p class="account-stat-box-title">{{ $hhPct($hhAnalytics['returningUsersRate'] ?? 0) }}</p>
          <!-- /ACCOUNT STAT BOX TITLE -->
      
          <!-- ACCOUNT STAT BOX SUBTITLE -->
          <p class="account-stat-box-subtitle">Returning Visitors</p>
          <!-- /ACCOUNT STAT BOX SUBTITLE -->
      
          <!-- ACCOUNT STAT BOX TEXT -->
          <p class="account-stat-box-text">Aktive Bestandsnutzer im Verhältnis zu aktiven Monatsnutzern.</p>
          <!-- /ACCOUNT STAT BOX TEXT -->
        </div>
        <!-- /ACCOUNT STAT BOX -->
      </div>
      <!-- /GRID -->

      @if($hhCampaignLinks->isNotEmpty())
        <!-- WIDGET BOX -->
        <div class="widget-box hh-campaign-links-widget">
          <!-- WIDGET BOX ACTIONS -->
          <div class="widget-box-actions">
            <!-- WIDGET BOX ACTION -->
            <div class="widget-box-action">
              <!-- WIDGET BOX TITLE -->
              <p class="widget-box-title">Campaign Links</p>
              <!-- /WIDGET BOX TITLE -->
            </div>
            <!-- /WIDGET BOX ACTION -->
          </div>
          <!-- /WIDGET BOX ACTIONS -->

          <!-- WIDGET BOX CONTENT -->
          <div class="widget-box-content">
            <p class="widget-box-text">Nutze für Facebook den kurzen Link. Gezählt werden reine Kampagnen-Klicks ohne IP, User-Agent, Cookies oder personenbezogene Einzelprofile.</p>

            <!-- TABLE -->
            <div class="table hh-campaign-links-table">
              <!-- TABLE HEADER -->
              <div class="table-header">
                <div class="table-header-column">
                  <p class="table-header-title">Campaign</p>
                </div>
                <div class="table-header-column centered padded">
                  <p class="table-header-title">Month</p>
                </div>
                <div class="table-header-column centered padded">
                  <p class="table-header-title">Total</p>
                </div>
                <div class="table-header-column padded-left">
                  <p class="table-header-title">Short Link</p>
                </div>
                <div class="table-header-column padded-left">
                  <p class="table-header-title">Last Click</p>
                </div>
              </div>
              <!-- /TABLE HEADER -->

              <!-- TABLE BODY -->
              <div class="table-body">
                @foreach($hhCampaignLinks as $campaignLink)
                  <div class="table-row tiny">
                    <div class="table-column">
                      <p class="table-title">{{ $campaignLink['label'] ?? $campaignLink['slug'] }}</p>
                      <p class="table-text">{{ $campaignLink['utm_source'] ?? 'source' }} / {{ $campaignLink['utm_medium'] ?? 'medium' }}</p>
                    </div>
                    <div class="table-column centered padded">
                      <p class="table-title">{{ $hhNum($campaignLink['clicks_month'] ?? 0) }}</p>
                    </div>
                    <div class="table-column centered padded">
                      <p class="table-title">{{ $hhNum($campaignLink['clicks_total'] ?? 0) }}</p>
                    </div>
                    <div class="table-column padded-left">
                      <a class="table-title" href="{{ $campaignLink['short_url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">{{ $campaignLink['short_url'] ?? '-' }}</a>
                      <p class="table-text">→ {{ $campaignLink['target_url'] ?? '-' }}</p>
                    </div>
                    <div class="table-column padded-left">
                      <p class="table-title">{{ $campaignLink['last_clicked_at'] ?? 'noch nie' }}</p>
                    </div>
                  </div>
                @endforeach
              </div>
              <!-- /TABLE BODY -->
            </div>
            <!-- /TABLE -->
          </div>
          <!-- /WIDGET BOX CONTENT -->
        </div>
        <!-- /WIDGET BOX -->
      @endif

      <!-- WIDGET BOX -->
      <div class="widget-box">
        <!-- WIDGET BOX ACTIONS -->
        <div class="widget-box-actions">
          <!-- WIDGET BOX ACTION -->
          <div class="widget-box-action">
            <!-- WIDGET BOX TITLE -->
            <p class="widget-box-title">Monthly Report</p>
            <!-- /WIDGET BOX TITLE -->
          </div>
          <!-- /WIDGET BOX ACTION -->
    
          <!-- WIDGET BOX ACTION -->
          <div class="widget-box-action">
            <!-- REFERENCE ITEM LIST -->
            <div class="reference-item-list">
              <!-- REFERENCE ITEM -->
              <div class="reference-item">
                <!-- REFERENCE BULLET -->
                <div class="reference-bullet primary"></div>
                <!-- REFERENCE BULLET -->
    
                <!-- REFERENCE ITEM TEXT -->
                <p class="reference-item-text">Visits</p>
                <!-- /REFERENCE ITEM TEXT -->
              </div>
              <!-- /REFERENCE ITEM -->
    
              <!-- REFERENCE ITEM -->
              <div class="reference-item">
                <!-- REFERENCE BULLET -->
                <div class="reference-bullet secondary"></div>
                <!-- REFERENCE BULLET -->
    
                <!-- REFERENCE ITEM TEXT -->
                <p class="reference-item-text">Engagements</p>
                <!-- /REFERENCE ITEM TEXT -->
              </div>
              <!-- /REFERENCE ITEM -->
            </div>
            <!-- /REFERENCE ITEM LIST -->
    
            <!-- FORM SELECT -->
            <div class="form-select v2">
              <select id="ve-monthly-report-date" name="ve_monthly_report_date">
                <option value="0">{{ $hhOverview['monthLabel'] ?? now()->translatedFormat('F Y') }}</option>
                <option value="1">{{ now()->copy()->subMonth()->translatedFormat('F Y') }}</option>
              </select>
              <!-- FORM SELECT ICON -->
              <svg class="form-select-icon icon-small-arrow">
                <use xlink:href="#svg-small-arrow"></use>
              </svg>
              <!-- /FORM SELECT ICON -->
            </div>
            <!-- /FORM SELECT -->
          </div>
          <!-- /WIDGET BOX ACTION -->
        </div>
        <!-- /WIDGET BOX ACTIONS -->
    
        <!-- WIDGET BOX CONTENT -->
        <div class="widget-box-content">
          <!-- CHART WRAP -->
          <div class="chart-wrap">
            <!-- CHART -->
            <div class="chart">
              <canvas id="ve-monthly-report-chart"></canvas>
            </div>
            <!-- /CHART -->
          </div>
          <!-- /CHART WRAP -->
        </div>
        <!-- WIDGET BOX CONTENT -->

        <!-- WIDGET BOX FOOTER -->
        <div class="widget-box-footer">
          <!-- CHART INFO -->
          <div class="chart-info">
            <!-- PROGRESS ARC WRAP -->
            <div class="progress-arc-wrap tiny">
              <!-- PROGRESS ARC -->
              <div class="progress-arc">
                <canvas id="ve-monthly-report-ratio-chart"></canvas>
              </div>
              <!-- PROGRESS ARC -->
          
              <!-- PROGRESS ARC INFO -->
              <div class="progress-arc-info">
                <!-- PROGRESS ARC TITLE -->
                <p class="progress-arc-title">Ratio</p>
                <!-- /PROGRESS ARC TITLE -->
              </div>
              <!-- /PROGRESS ARC INFO -->
            </div>
            <!-- /PROGRESS ARC WRAP -->

            <!-- USER STATS -->
            <div class="user-stats">
              <!-- USER STAT -->
              <div class="user-stat big">
                <!-- USER STAT TITLE -->
                <p class="user-stat-title">{{ $hhNum($hhMonthlyContent) }}</p>
                <!-- /USER STAT TITLE -->
        
                <!-- USER STAT TEXT -->
                <p class="user-stat-text">t. content</p>
                <!-- /USER STAT TEXT -->
              </div>
              <!-- /USER STAT -->
        
              <!-- USER STAT -->
              <div class="user-stat big">
                <!-- USER STAT TITLE -->
                <p class="user-stat-title">{{ $hhNum($hhMonthlyEngagements) }}</p>
                <!-- /USER STAT TITLE -->
        
                <!-- USER STAT TEXT -->
                <p class="user-stat-text">t. engagements</p>
                <!-- /USER STAT TEXT -->
              </div>
              <!-- /USER STAT -->
        
              <!-- USER STAT -->
              <div class="user-stat big">
                <!-- USER STAT TITLE -->
                <p class="user-stat-title">{{ number_format($hhMonthlyContent / $hhDaysInMonth, 1, ',', '.') }}</p>
                <!-- /USER STAT TITLE -->
        
                <!-- USER STAT TEXT -->
                <p class="user-stat-text">avg content</p>
                <!-- /USER STAT TEXT -->
              </div>
              <!-- /USER STAT -->
      
              <!-- USER STAT -->
              <div class="user-stat big">
                <!-- USER STAT TITLE -->
                <p class="user-stat-title">{{ number_format($hhMonthlyEngagements / $hhDaysInMonth, 1, ',', '.') }}</p>
                <!-- /USER STAT TITLE -->
        
                <!-- USER STAT TEXT -->
                <p class="user-stat-text">avg engagements</p>
                <!-- /USER STAT TEXT -->
              </div>
              <!-- /USER STAT -->

              <!-- USER STAT -->
              <div class="user-stat big">
                <!-- USER STAT TITLE -->
                <p class="user-stat-title">
                  <!-- USER STAT TITLE ICON -->
                  <svg class="user-stat-title-icon positive icon-plus-small">
                    <use xlink:href="#svg-plus-small"></use>
                  </svg>
                  <!-- /USER STAT TITLE ICON -->
                  {{ $hhStatDiff($hhTopStats, 1, 'value', '0%') }}
                </p>
                <!-- /USER STAT TITLE -->
        
                <!-- USER STAT TEXT -->
                <p class="user-stat-text">content / prev. month</p>
                <!-- /USER STAT TEXT -->
              </div>
              <!-- /USER STAT -->

              <!-- USER STAT -->
              <div class="user-stat big">
                <!-- USER STAT TITLE -->
                <p class="user-stat-title">
                  <!-- USER STAT TITLE ICON -->
                  <svg class="user-stat-title-icon negative icon-minus-small">
                    <use xlink:href="#svg-minus-small"></use>
                  </svg>
                  <!-- /USER STAT TITLE ICON -->
                  {{ $hhStatDiff($hhTopStats, 2, 'value', '0%') }}
                </p>
                <!-- /USER STAT TITLE -->
        
                <!-- USER STAT TEXT -->
                <p class="user-stat-text">engagements / prev. month</p>
                <!-- /USER STAT TEXT -->
              </div>
              <!-- /USER STAT -->
            </div>
            <!-- /USER STATS -->
          </div>
          <!-- /CHART INFO -->
        </div>
        <!-- /WIDGET BOX FOOTER -->
      </div>
      <!-- /WIDGET BOX -->

      <!-- GRID -->
      <div class="grid grid-9-3 stretched">
        <!-- GRID COLUMN -->
        <div class="grid-column">
          <!-- WIDGET BOX -->
          <div class="widget-box">
            <!-- WIDGET BOX TITLE -->
            <p class="widget-box-title">Top Members Activity</p>
            <!-- /WIDGET BOX TITLE -->
        
            <!-- WIDGET BOX CONTENT -->
            <div class="widget-box-content no-margin-top">
              <!-- TABLE -->
              <div class="table table-top-friends join-rows">
                <!-- TABLE HEADER -->
                <div class="table-header">
                  <!-- TABLE HEADER COLUMN -->
                  <div class="table-header-column">
                    <!-- TABLE HEADER TITLE -->
                    <p class="table-header-title">Member</p>
                    <!-- /TABLE HEADER TITLE -->
                  </div>
                  <!-- /TABLE HEADER COLUMN -->
            
                  <!-- TABLE HEADER COLUMN -->
                  <div class="table-header-column centered padded">
                    <!-- TABLE HEADER TITLE -->
                    <p class="table-header-title">Posts</p>
                    <!-- /TABLE HEADER TITLE -->
                  </div>
                  <!-- /TABLE HEADER COLUMN -->
            
                  <!-- TABLE HEADER COLUMN -->
                  <div class="table-header-column centered padded">
                    <!-- TABLE HEADER TITLE -->
                    <p class="table-header-title">Comments</p>
                    <!-- /TABLE HEADER TITLE -->
                  </div>
                  <!-- /TABLE HEADER COLUMN -->
            
                  <!-- TABLE HEADER COLUMN -->
                  <div class="table-header-column centered padded">
                    <!-- TABLE HEADER TITLE -->
                    <p class="table-header-title">Badges</p>
                    <!-- /TABLE HEADER TITLE -->
                  </div>
                  <!-- /TABLE HEADER COLUMN -->
            
                  <!-- TABLE HEADER COLUMN -->
                  <div class="table-header-column centered padded">
                    <!-- TABLE HEADER TITLE -->
                    <p class="table-header-title">Level</p>
                    <!-- /TABLE HEADER TITLE -->
                  </div>
                  <!-- /TABLE HEADER COLUMN -->
            
                  <!-- TABLE HEADER COLUMN -->
                  <div class="table-header-column padded-left">
                    <!-- TABLE HEADER TITLE -->
                    <p class="table-header-title">Activity Score</p>
                    <!-- /TABLE HEADER TITLE -->
                  </div>
                  <!-- /TABLE HEADER COLUMN -->
                </div>
                <!-- /TABLE HEADER -->
            
                <!-- TABLE BODY -->
                <div class="table-body">
                  @forelse($hhTopMembers as $member)
                    <!-- TABLE ROW -->
                    <div class="table-row tiny">
                      <!-- TABLE COLUMN -->
                      <div class="table-column">
                        <!-- USER STATUS -->
                        <div class="user-status">
                          <!-- USER STATUS AVATAR -->
                          <a class="user-status-avatar" href="{{ $member['url'] ?? 'javascript:void(0)' }}">
                            <!-- USER AVATAR -->
                            <div class="user-avatar small no-outline">
                              <!-- USER AVATAR CONTENT -->
                              <div class="user-avatar-content">
                                <!-- HEXAGON -->
                                <div class="hexagon-image-30-32" data-src="{{ $member['avatar'] ?? asset('assets/vikinger/img/avatar/01.jpg') }}"></div>
                                <!-- /HEXAGON -->
                              </div>
                              <!-- /USER AVATAR CONTENT -->

                              <!-- USER AVATAR PROGRESS -->
                              <div class="user-avatar-progress">
                                <!-- HEXAGON -->
                                <div class="hexagon-progress-40-44"></div>
                                <!-- /HEXAGON -->
                              </div>
                              <!-- /USER AVATAR PROGRESS -->

                              <!-- USER AVATAR PROGRESS BORDER -->
                              <div class="user-avatar-progress-border">
                                <!-- HEXAGON -->
                                <div class="hexagon-border-40-44"></div>
                                <!-- /HEXAGON -->
                              </div>
                              <!-- /USER AVATAR PROGRESS BORDER -->

                              <!-- USER AVATAR BADGE -->
                              <div class="user-avatar-badge">
                                <!-- USER AVATAR BADGE BORDER -->
                                <div class="user-avatar-badge-border">
                                  <!-- HEXAGON -->
                                  <div class="hexagon-22-24"></div>
                                  <!-- /HEXAGON -->
                                </div>
                                <!-- /USER AVATAR BADGE BORDER -->

                                <!-- USER AVATAR BADGE CONTENT -->
                                <div class="user-avatar-badge-content">
                                  <!-- HEXAGON -->
                                  <div class="hexagon-dark-16-18"></div>
                                  <!-- /HEXAGON -->
                                </div>
                                <!-- /USER AVATAR BADGE CONTENT -->

                                <!-- USER AVATAR BADGE TEXT -->
                                <p class="user-avatar-badge-text">{{ $hhNum($member['level'] ?? 1) }}</p>
                                <!-- /USER AVATAR BADGE TEXT -->
                              </div>
                              <!-- /USER AVATAR BADGE -->
                            </div>
                            <!-- /USER AVATAR -->
                          </a>
                          <!-- /USER STATUS AVATAR -->

                          <!-- USER STATUS TITLE -->
                          <p class="user-status-title"><a class="bold" href="{{ $member['url'] ?? 'javascript:void(0)' }}">{{ $member['name'] ?? 'Mitglied' }}</a></p>
                          <!-- /USER STATUS TITLE -->

                          <!-- USER STATUS TEXT -->
                          <p class="user-status-text small">{{ isset($member['username']) ? '@' . $member['username'] : 'hnt.rocks Member' }}</p>
                          <!-- /USER STATUS TEXT -->
                        </div>
                        <!-- /USER STATUS -->
                      </div>
                      <!-- /TABLE COLUMN -->

                      <!-- TABLE COLUMN -->
                      <div class="table-column centered padded">
                        <!-- TABLE TITLE -->
                        <p class="table-title">{{ $hhNum($member['posts'] ?? 0) }}</p>
                        <!-- /TABLE TITLE -->
                      </div>
                      <!-- /TABLE COLUMN -->

                      <!-- TABLE COLUMN -->
                      <div class="table-column centered padded">
                        <!-- TABLE TITLE -->
                        <p class="table-title">{{ $hhNum($member['comments'] ?? 0) }}</p>
                        <!-- /TABLE TITLE -->
                      </div>
                      <!-- /TABLE COLUMN -->

                      <!-- TABLE COLUMN -->
                      <div class="table-column centered padded">
                        <!-- TABLE TITLE -->
                        <p class="table-title">{{ $hhNum($member['badges'] ?? 0) }}</p>
                        <!-- /TABLE TITLE -->
                      </div>
                      <!-- /TABLE COLUMN -->

                      <!-- TABLE COLUMN -->
                      <div class="table-column centered padded">
                        <!-- TABLE TITLE -->
                        <p class="table-title">{{ $hhNum($member['level'] ?? 1) }}</p>
                        <!-- /TABLE TITLE -->
                      </div>
                      <!-- /TABLE COLUMN -->

                      <!-- TABLE COLUMN -->
                      <div class="table-column padded-left">
                        <!-- PROGRESS STAT WRAP -->
                        <div class="progress-stat-wrap">
                          <!-- PROGRESS STAT -->
                          <div class="progress-stat">
                            <!-- PROGRESS STAT BAR -->
                            <div class="progress-stat-bar"></div>
                            <!-- /PROGRESS STAT BAR -->

                            <!-- BAR PROGRESS WRAP -->
                            <div class="bar-progress-wrap">
                              <!-- BAR PROGRESS INFO -->
                              <p class="bar-progress-info medium negative">{{ $hhNum(($member['posts'] ?? 0) + ($member['comments'] ?? 0) + ($member['badges'] ?? 0)) }}</p>
                              <!-- /BAR PROGRESS INFO -->
                            </div>
                            <!-- /BAR PROGRESS WRAP -->
                          </div>
                          <!-- /PROGRESS STAT -->
                        </div>
                        <!-- /PROGRESS STAT WRAP -->
                      </div>
                      <!-- /TABLE COLUMN -->
                    </div>
                    <!-- /TABLE ROW -->
                  @empty
                    <!-- TABLE ROW -->
                    <div class="table-row tiny">
                      <div class="table-column">
                        <p class="table-text">Noch keine Mitglieder-Aktivität vorhanden.</p>
                      </div>
                    </div>
                    <!-- /TABLE ROW -->
                  @endforelse
                </div>
                <!-- /TABLE BODY -->
              </div>
              <!-- /TABLE -->
            </div>
            <!-- WIDGET BOX CONTENT -->
          </div>
          <!-- /WIDGET BOX -->
        </div>
        <!-- /GRID COLUMN -->

        <!-- GRID COLUMN -->
        <div class="grid-column">
          <!-- WIDGET BOX -->
          <div class="widget-box">
            <!-- WIDGET BOX ACTIONS -->
            <div class="widget-box-actions">
              <!-- WIDGET BOX ACTION -->
              <div class="widget-box-action">
                <!-- WIDGET BOX TITLE -->
                <p class="widget-box-title">Engagements</p>
                <!-- /WIDGET BOX TITLE -->
              </div>
              <!-- /WIDGET BOX ACTION -->
            </div>
            <!-- /WIDGET BOX ACTIONS -->

            <!-- WIDGET BOX CONTENT -->
            <div class="widget-box-content">
              <!-- PROGRESS ARC WRAP -->
              <div class="progress-arc-wrap">
                <!-- PROGRESS ARC -->
                <div class="progress-arc">
                  <canvas id="engagements-chart"></canvas>
                </div>
                <!-- PROGRESS ARC -->

                <!-- PROGRESS ARC INFO -->
                <div class="progress-arc-info">
                  <!-- PROGRESS ARC TITLE -->
                  <p class="progress-arc-title">{{ $hhNum($hhMonthlyEngagements) }}</p>
                  <!-- /PROGRESS ARC TITLE -->

                  <!-- PROGRESS ARC TEXT -->
                  <p class="progress-arc-text">Engagements</p>
                  <!-- /PROGRESS ARC TEXT -->
                </div>
                <!-- /PROGRESS ARC INFO -->
              </div>
              <!-- /PROGRESS ARC WRAP -->

              <!-- USER STATS -->
              <div class="user-stats reference">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- REFERENCE BULLET -->
                  <div class="reference-bullet secondary"></div>
                  <!-- /REFERENCE BULLET -->

                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhEngagementData[0] ?? 0) }}</p>
                  <!-- /USER STAT TITLE -->
          
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">reactions</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
          
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- REFERENCE BULLET -->
                  <div class="reference-bullet primary"></div>
                  <!-- /REFERENCE BULLET -->

                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhEngagementData[1] ?? 0) }}</p>
                  <!-- /USER STAT TITLE -->
          
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">comments</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /USER STATS -->

              <!-- USER STATS -->
              <div class="user-stats reference">
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- REFERENCE BULLET -->
                  <div class="reference-bullet blue"></div>
                  <!-- /REFERENCE BULLET -->

                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhEngagementData[2] ?? 0) }}</p>
                  <!-- /USER STAT TITLE -->
          
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">shares</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
          
                <!-- USER STAT -->
                <div class="user-stat big">
                  <!-- REFERENCE BULLET -->
                  <div class="reference-bullet light-blue"></div>
                  <!-- /REFERENCE BULLET -->

                  <!-- USER STAT TITLE -->
                  <p class="user-stat-title">{{ $hhNum($hhEngagementData[3] ?? 0) }}</p>
                  <!-- /USER STAT TITLE -->
          
                  <!-- USER STAT TEXT -->
                  <p class="user-stat-text">moment comments</p>
                  <!-- /USER STAT TEXT -->
                </div>
                <!-- /USER STAT -->
              </div>
              <!-- /USER STATS -->
            </div>
            <!-- WIDGET BOX CONTENT -->
          </div>
          <!-- /WIDGET BOX -->
        </div>
        <!-- /GRID COLUMN -->
      </div>
      <!-- /GRID -->

      <!-- GRID -->
      <div class="grid grid-3-9 stretched">
        <!-- GRID COLUMN -->
        <div class="grid-column">
          <!-- WIDGET BOX -->
          <div class="widget-box">
            <!-- WIDGET BOX TITLE -->
            <p class="widget-box-title">Visits Top Countries</p>
            <!-- /WIDGET BOX TITLE -->
        
            <!-- WIDGET BOX CONTENT -->
            <div class="widget-box-content">
              <!-- COUNTRY STAT LIST -->
              <div class="country-stat-list">
                @forelse($hhSessionRows->take(9) as $index => $row)
                  <!-- COUNTRY STAT -->
                  <div class="country-stat {{ $index < 3 ? 'with-progress' : '' }}">
                    <!-- COUNTRY STAT IMAGE -->
                    <img class="country-stat-image" src="{{ asset('assets/vikinger/img/flag/' . ($row['flag_slug'] ?? 'germany') . '.png') }}" alt="session-source">
                    <!-- /COUNTRY STAT IMAGE -->

                    @if($index < 3)
                      <!-- PROGRESS STAT -->
                      <div class="progress-stat small">
                        <!-- BAR PROGRESS WRAP -->
                        <div class="bar-progress-wrap">
                          <!-- BAR PROGRESS INFO -->
                          <p class="bar-progress-info medium negative regular">{{ $row['label'] ?? 'Session' }}<span class="bar-progress-text no-space"></span></p>
                          <!-- /BAR PROGRESS INFO -->
                        </div>
                        <!-- /BAR PROGRESS WRAP -->

                        <!-- PROGRESS STAT BAR -->
                        <div class="progress-stat-bar"></div>
                        <!-- /PROGRESS STAT BAR -->
                      </div>
                      <!-- /PROGRESS STAT -->
                    @else
                      <!-- COUNTRY STAT TITLE -->
                      <p class="country-stat-title">{{ $row['label'] ?? 'Session' }}</p>
                      <!-- /COUNTRY STAT TITLE -->

                      <!-- COUNTRY STAT TEXT -->
                      <p class="country-stat-text">{{ $hhNum($row['sessions'] ?? 0) }} Sessions</p>
                      <!-- /COUNTRY STAT TEXT -->
                    @endif
                  </div>
                  <!-- /COUNTRY STAT -->
                @empty
                  <!-- COUNTRY STAT -->
                  <div class="country-stat">
                    <!-- COUNTRY STAT IMAGE -->
                    <img class="country-stat-image" src="{{ asset('assets/vikinger/img/flag/germany.png') }}" alt="flag-germany">
                    <!-- /COUNTRY STAT IMAGE -->

                    <!-- COUNTRY STAT TITLE -->
                    <p class="country-stat-title">Noch keine Sessiondaten</p>
                    <!-- /COUNTRY STAT TITLE -->

                    <!-- COUNTRY STAT TEXT -->
                    <p class="country-stat-text">0</p>
                    <!-- /COUNTRY STAT TEXT -->
                  </div>
                  <!-- /COUNTRY STAT -->
                @endforelse
              </div>
              <!-- /COUNTRY STAT LIST -->
            </div>
            <!-- WIDGET BOX CONTENT -->
          </div>
          <!-- /WIDGET BOX -->
        </div>
        <!-- /GRID COLUMN -->

        <!-- GRID COLUMN -->
        <div class="grid-column">
          <!-- WIDGET BOX -->
          <div class="widget-box">
            <!-- WIDGET BOX ACTIONS -->
            <div class="widget-box-actions">
              <!-- WIDGET BOX ACTION -->
              <div class="widget-box-action">
                <!-- WIDGET BOX TITLE -->
                <p class="widget-box-title">Visits World Map</p>
                <!-- /WIDGET BOX TITLE -->
              </div>
              <!-- /WIDGET BOX ACTION -->

              <!-- WIDGET BOX ACTION -->
              <div class="widget-box-action">
                <!-- FORM SELECT -->
                <div class="form-select v2">
                  <select id="visits-map-date" name="visits_map_date">
                    <option value="0">{{ $hhOverview['monthLabel'] ?? now()->translatedFormat('F Y') }}</option>
                    <option value="1">{{ now()->copy()->subMonth()->translatedFormat('F Y') }}</option>
                  </select>
                  <!-- FORM SELECT ICON -->
                  <svg class="form-select-icon icon-small-arrow">
                    <use xlink:href="#svg-small-arrow"></use>
                  </svg>
                  <!-- /FORM SELECT ICON -->
                </div>
                <!-- /FORM SELECT -->
              </div>
              <!-- /WIDGET BOX ACTION -->
            </div>
            <!-- /WIDGET BOX ACTIONS -->

            <!-- WIDGET BOX CONTENT -->
            <div class="widget-box-content">
              <!-- FULL WIDTH IMAGE -->
              <img class="full-width-image" src="{{ asset('assets/vikinger/img/flag/map.png') }}" alt="map">
              <!-- /FULL WIDTH IMAGE -->

              <!-- WIDGET BOX TEXT -->
              <p class="widget-box-text">Die Tabelle nutzt jetzt echtes Visit-Tracking. Länder erscheinen automatisch, wenn dein Proxy/Hosting einen Country-Header wie CF-IPCountry liefert.</p>
              <!-- /WIDGET BOX TEXT -->
            </div>
            <!-- WIDGET BOX CONTENT -->
          </div>
          <!-- /WIDGET BOX -->
        </div>
        <!-- /GRID COLUMN -->
      </div>
      <!-- /GRID -->

      <!-- WIDGET BOX -->
      <div class="widget-box">
        <!-- WIDGET BOX ACTIONS -->
        <div class="widget-box-actions">
          <!-- WIDGET BOX ACTION -->
          <div class="widget-box-action">
            <!-- WIDGET BOX TITLE -->
            <p class="widget-box-title">Yearly Report</p>
            <!-- /WIDGET BOX TITLE -->
          </div>
          <!-- /WIDGET BOX ACTION -->
    
          <!-- WIDGET BOX ACTION -->
          <div class="widget-box-action">
            <!-- REFERENCE ITEM LIST -->
            <div class="reference-item-list">
              <!-- REFERENCE ITEM -->
              <div class="reference-item">
                <!-- REFERENCE BULLET -->
                <div class="reference-bullet primary"></div>
                <!-- REFERENCE BULLET -->
    
                <!-- REFERENCE ITEM TEXT -->
                <p class="reference-item-text">Reaktionen · {{ $hhNum($hhYearlyReactionsTotal) }}</p>
                <!-- /REFERENCE ITEM TEXT -->
              </div>
              <!-- /REFERENCE ITEM -->
    
              <!-- REFERENCE ITEM -->
              <div class="reference-item">
                <!-- REFERENCE BULLET -->
                <div class="reference-bullet blue"></div>
                <!-- REFERENCE BULLET -->
    
                <!-- REFERENCE ITEM TEXT -->
                <p class="reference-item-text">{{ $hhYearlySecondaryLabel }} · {{ $hhNum($hhYearlyCommentsTotal) }}</p>
                <!-- /REFERENCE ITEM TEXT -->
              </div>
              <!-- /REFERENCE ITEM -->
            </div>
            <!-- /REFERENCE ITEM LIST -->
    
            <!-- FORM SELECT -->
            <div class="form-select v2">
              <select id="rc-yearly-report-date" name="rc_yearly_report_date">
                <option value="0">Jan - Dec {{ $hhOverview['yearLabel'] ?? now()->year }}</option>
                <option value="1">Jan - Dec {{ now()->subYear()->year }}</option>
              </select>
              <!-- FORM SELECT ICON -->
              <svg class="form-select-icon icon-small-arrow">
                <use xlink:href="#svg-small-arrow"></use>
              </svg>
              <!-- /FORM SELECT ICON -->
            </div>
            <!-- /FORM SELECT -->
          </div>
          <!-- /WIDGET BOX ACTION -->
        </div>
        <!-- /WIDGET BOX ACTIONS -->
    
        <!-- WIDGET BOX CONTENT -->
        <div class="widget-box-content">
          <!-- CHART WRAP -->
          <div class="chart-wrap">
            <!-- CHART -->
            <div class="chart">
              <canvas id="rc-yearly-report-chart"></canvas>
            </div>
            <!-- /CHART -->
          </div>
          <!-- /CHART WRAP -->
        </div>
        <!-- WIDGET BOX CONTENT -->
      </div>
      <!-- /WIDGET BOX -->
    </div>
    <!-- /GRID -->
  </div>
  
@endsection

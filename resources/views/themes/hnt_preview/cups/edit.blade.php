@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.cup_edit_title').' · HNT Preview')
@section('meta_description', __('ui.cup_edit_intro'))
@section('robots', 'noindex,follow')
@section('main_class', 'cup-detail-main cup-form-main-page')

@section('content')
    <div class="cup-detail-shell cup-form-page-shell">
        @if ($errors->any())
            <article class="cup-panel cup-form-alert" style="margin-bottom: 18px;">
                <h2>{{ __('ui.please_check') }}</h2>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </article>
        @endif

        <form method="post" action="{{ route('cups.update', $cup) }}" enctype="multipart/form-data">
            @include('themes.hnt_preview.cups.partials.cup-form', ['cup' => $cup])
        </form>
    </div>
@endsection

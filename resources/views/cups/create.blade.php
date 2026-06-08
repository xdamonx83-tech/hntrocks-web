@extends('themes.socialite.layouts.app')

@section('title', __('ui.cup_create_title'))
@section('meta_description', __('ui.cup_create_intro'))
@section('robots', 'noindex,follow')

@section('content')
@if ($errors->any())
    <div class="2xl:max-w-[1220px] max-w-[1065px] mx-auto mb-4">
        <div class="box p-4 border border-red-500/30 bg-red-500/10 text-sm">
            <strong class="text-red-400">{{ __('ui.please_check') }}</strong>
            <ul class="mt-2 list-disc pl-5 space-y-1 text-red-200">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form method="post" action="{{ route('cups.store') }}" enctype="multipart/form-data">
    @include('cups.partials.cup-form', ['cup' => $cup])
</form>
@endsection

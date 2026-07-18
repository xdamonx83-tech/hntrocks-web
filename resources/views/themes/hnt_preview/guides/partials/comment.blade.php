<article class="guide-comment {{ $isReply ? 'reply' : '' }}" id="comment-{{ $comment->id }}">
<img src="{{ $comment->user?->avatarUrl() }}" alt="">
<div>
<header><strong>{{ $comment->user?->name ?: $comment->user?->username }}</strong>@if($comment->user_id === $guide->author_id)<em>{{ __('guides.comments.author_badge') }}</em>@endif<span>{{ $comment->created_at?->diffForHumans() }}</span></header>
@if($comment->trashed())<p class="deleted">{{ __('guides.comments.deleted_label') }}</p>@else<p>{!! nl2br(e($comment->body)) !!}</p>@endif
@auth
@unless($comment->trashed())
<footer>
@unless($isReply)<button type="button" data-comment-reply="{{ $comment->id }}" data-comment-author="{{ $comment->user?->username }}">{{ __('guides.comments.reply') }}</button>@endunless
@if($comment->canEdit(auth()->user()))
<details><summary>{{ __('guides.comments.edit') }}</summary><form action="{{ route('guides.comments.update', $comment) }}" method="post">@csrf @method('patch')<textarea name="body" maxlength="2000" required>{{ $comment->body }}</textarea><button type="submit">{{ __('guides.comments.edit') }}</button></form></details>
@endif
@if($comment->canDelete(auth()->user()))
<form action="{{ route('guides.comments.destroy', $comment) }}" method="post">@csrf @method('delete')<button type="submit">{{ __('guides.comments.delete') }}</button></form>
@endif
@if((int) $comment->user_id !== (int) auth()->id())
<details><summary>{{ __('guides.detail.report') }}</summary><form action="{{ route('reports.store') }}" method="post">@csrf<input type="hidden" name="type" value="guide_comment"><input type="hidden" name="id" value="{{ $comment->id }}"><input type="hidden" name="reason" value="other"><textarea name="body" maxlength="2000" placeholder="{{ __('guides.detail.report_details') }}"></textarea><button type="submit">{{ __('guides.detail.report_send') }}</button></form></details>
@endif
</footer>
@endunless
@endauth
@unless($isReply)
@foreach($comment->replies as $reply)
@include('themes.hnt_preview.guides.partials.comment', ['comment' => $reply, 'guide' => $guide, 'isReply' => true])
@endforeach
@endunless
</div>
</article>

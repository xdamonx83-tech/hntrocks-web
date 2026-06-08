<nav id="navigation-widget-small" class="navigation-widget navigation-widget-desktop closed sidebar left">
    <a class="user-avatar small no-outline" href="{{ route('feed.index') }}">
        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ asset('assets/vikinger/img/avatar/01.jpg') }}"></div></div>
    </a>
    <ul class="menu small">
        <li class="menu-item"><a class="menu-item-link text-tooltip-tfr" href="{{ route('feed.index') }}" data-title="Feed">Feed</a></li>
        <li class="menu-item"><a class="menu-item-link text-tooltip-tfr" href="{{ route('teams.index') }}" data-title="Teams">Teams</a></li>
        <li class="menu-item"><a class="menu-item-link text-tooltip-tfr" href="{{ route('lfg.index') }}" data-title="LFG">LFG</a></li>
        <li class="menu-item"><a class="menu-item-link text-tooltip-tfr" href="{{ route('messages.index') }}" data-title="Nachrichten">Nachrichten</a></li>
    </ul>
</nav>

<header class="header">
    <div class="header-actions">
        <div class="header-brand">
            <div class="logo">
                <img src="{{ asset('assets/vikinger/img/landing/vikinger-logo.png') }}" alt="hnt.rocks">
            </div>
            <h1 class="header-brand-text">hnt.rocks</h1>
        </div>
    </div>

    <div class="header-actions">
        <nav class="navigation">
            <ul class="menu-main">
                <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('feed.index') }}">Feed</a></li>
                <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('members.index') }}">Spieler</a></li>
                <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('teams.index') }}">Teams</a></li>
                <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('lfg.index') }}">LFG</a></li>
                <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('moments.index') }}">Moments</a></li>
                <li class="menu-main-item"><a class="menu-main-item-link" href="{{ route('cups.index') }}">Cups</a></li>
            </ul>
        </nav>
    </div>
</header>

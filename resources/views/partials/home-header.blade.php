<header class="top-header">
    <div class="header-inner">
        <a href="{{ route('home') }}" class="text-decoration-none">
            <div class="logo">Quick Pay<span class="logo-x">Money</span></div>
        </a>

        <div class="header-actions">
            <a class="header-btn icon-only {{ request()->routeIs('home') ? '' : 'header-contact' }}" href="{{ route('contact') }}" aria-label="Contact support">
                <i class="bi bi-headset" aria-hidden="true"></i>
            </a>

            @auth('web')
                @php($user = auth('web')->user())
                <div class="user-menu">
                    <button
                        class="header-btn user-menu-toggle"
                        type="button"
                        aria-label="Account menu for {{ $user->name }}"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="user-menu-panel"
                        data-user-menu-toggle
                    >
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                    </button>
                    <div class="user-menu-panel" id="user-menu-panel" role="menu" hidden>
                        <p class="user-menu-name">{{ $user->name }}</p>
                        <a href="{{ route('profile') }}" role="menuitem">Profile</a>
                        <a href="{{ route('profile', ['tab' => 'history']) }}" role="menuitem">History</a>
                        <form method="POST" action="{{ route('logout') }}" data-confirm="You will be signed out of your account." data-confirm-title="Sign out?">
                            @csrf
                            <button type="submit" role="menuitem">Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <a class="header-btn login-btn" href="{{ route('login') }}">
                    <i class="bi bi-person-fill" aria-hidden="true"></i>
                    <span>Login</span>
                </a>
            @endauth
        </div>
    </div>
</header>

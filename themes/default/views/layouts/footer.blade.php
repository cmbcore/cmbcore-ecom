@php
    $footerMenu = theme_menu_items('footer_menu');
    $mainMenu = theme_menu_items('main_menu');
@endphp

<footer class="sf-footer" id="footer">
    <div class="sf-container">
        <div class="sf-footer__grid">
            <div class="sf-footer__brand">
                <span class="sf-kicker">{{ theme_text('footer.about_title') }}</span>
                <h2>{{ theme_site_name() }}</h2>
                <p>{{ theme_text('footer.ready') }}</p>
                <div class="sf-footer__meta">
                    <span>{{ theme_text('footer.stack') }}</span>
                    <span>{{ theme_text('footer.claim') }}</span>
                </div>
            </div>

            <div class="sf-footer__column">
                <h3>{{ theme_text('navigation.main_menu') }}</h3>
                <ul>
                    @foreach ($mainMenu as $item)
                        <li>
                            <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                                {{ theme_menu_label($item) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="sf-footer__column">
                <h3>{{ theme_text('footer.policy_title') }}</h3>
                <ul>
                    @foreach ($footerMenu as $item)
                        <li>
                            <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                                {{ theme_menu_label($item) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="sf-footer__column">
                <h3>{{ theme_text('navigation.language') }}</h3>
                <ul>
                    @foreach (theme_supported_locales() as $locale)
                        <li>
                            <a href="{{ theme_locale_url($locale['code']) }}">
                                {{ $locale['native_name'] ?? strtoupper($locale['code']) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="sf-footer__bottom">
            <span>{{ theme_site_name() }}</span>
            <span>{{ now()->format('Y') }}</span>
        </div>
    </div>
</footer>

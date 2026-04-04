@php
    $footerAbout = theme_menu_items('footer_about_menu');
    $footerPolicy = theme_menu_items('footer_policy_menu');
    $footerContact = theme_setting_json('footer_contact', []);
    $facebookUrl = (string) theme_setting('facebook_url', '#');
    $socialTitle = (string) theme_setting('social_title', 'Facebook');
    $socialLabel = (string) theme_setting('social_label', theme_text('footer.facebook_cta'));
@endphp

<footer class="cmbcore-footer" id="footer">
    <div class="cmbcore-container cmbcore-footer__grid">
        <div class="cmbcore-footer__column">
            <a class="cmbcore-footer__logo" href="{{ theme_home_url() }}">
                <img src="{{ theme_media_url((string) theme_setting('logo_image', theme_asset('images/logo.png')), theme_asset('images/logo.png')) }}" alt="{{ theme_setting('logo_alt', theme_site_name()) }}">
            </a>
            <ul class="cmbcore-footer__contact">
                @if (!empty($footerContact['address']))
                    <li>{{ $footerContact['address'] }}</li>
                @endif
                @if (!empty($footerContact['phone']))
                    <li><a href="tel:{{ preg_replace('/\D+/', '', (string) $footerContact['phone']) }}">{{ $footerContact['phone'] }}</a></li>
                @endif
                @if (!empty($footerContact['email']))
                    <li><a href="mailto:{{ $footerContact['email'] }}">{{ $footerContact['email'] }}</a></li>
                @endif
            </ul>
            @if (!empty($footerContact['gov_badge_image']) && !empty($footerContact['gov_badge_url']))
                <a class="cmbcore-footer__badge" href="{{ $footerContact['gov_badge_url'] }}" target="_blank" rel="nofollow noreferrer noopener">
                    <img src="{{ theme_media_url($footerContact['gov_badge_image'] ?? null) }}" alt="{{ $footerContact['gov_badge_alt'] ?? 'BCT' }}">
                </a>
            @endif
        </div>

        <div class="cmbcore-footer__column">
            <h3>Về RHYS MAN</h3>
            <ul class="cmbcore-footer__links">
                @foreach ($footerAbout as $item)
                    <li>
                        <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                            {{ theme_menu_label($item) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="cmbcore-footer__column">
            <h3>Chính sách</h3>
            <ul class="cmbcore-footer__links">
                @foreach ($footerPolicy as $item)
                    <li>
                        <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                            {{ theme_menu_label($item) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="cmbcore-footer__column">
            <h3>{{ $socialTitle }}</h3>
            <a class="cmbcore-footer__facebook" href="{{ $facebookUrl }}" target="_blank" rel="noreferrer noopener">
                <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                <span>{{ $socialLabel }}</span>
            </a>
        </div>
    </div>

    <div class="cmbcore-footer__bottom">
        <div class="cmbcore-container">
            <span>Website chính thức của Rhys Man</span>
        </div>
    </div>
</footer>


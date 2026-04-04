@php
    $footerCategories = theme_menu_items('footer_categories_menu');
    $footerInfo = theme_menu_items('footer_info_menu');
    $footerService = theme_menu_items('footer_service_menu');
    $footerContact = theme_setting_json('footer_contact', []);
    $facebookUrl = (string) theme_setting('facebook_url', '#');
    $twitterUrl = (string) theme_setting('twitter_url', '#');
    $instagramUrl = (string) theme_setting('instagram_url', '#');
@endphp

<footer class="electro-footer">
    {{-- TOP FOOTER --}}
    <div class="electro-section">
        <div class="electro-container">
            <div class="electro-row">
                {{-- About Us --}}
                <div class="electro-col electro-col-footer">
                    <div class="electro-footer-block">
                        <h3 class="electro-footer-title">{{ theme_text('footer.about_title') }}</h3>
                        @if (!empty($footerContact['company']))
                            <p>{{ $footerContact['company'] }}</p>
                        @endif
                        <ul class="electro-footer-links">
                            @if (!empty($footerContact['address']))
                                <li><a href="#"><i class="fa fa-map-marker"></i>{{ $footerContact['address'] }}</a></li>
                            @endif
                            @if (!empty($footerContact['phone']))
                                <li><a href="tel:{{ preg_replace('/\D+/', '', (string) $footerContact['phone']) }}"><i class="fa fa-phone"></i>{{ $footerContact['phone'] }}</a></li>
                            @endif
                            @if (!empty($footerContact['email']))
                                <li><a href="mailto:{{ $footerContact['email'] }}"><i class="fa fa-envelope-o"></i>{{ $footerContact['email'] }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- Categories --}}
                <div class="electro-col electro-col-footer">
                    <div class="electro-footer-block">
                        <h3 class="electro-footer-title">{{ theme_text('products.category_sidebar_title') }}</h3>
                        <ul class="electro-footer-links">
                            @foreach ($footerCategories as $item)
                                <li>
                                    <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                                        {{ theme_menu_label($item) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Information --}}
                <div class="electro-col electro-col-footer">
                    <div class="electro-footer-block">
                        <h3 class="electro-footer-title">{{ theme_text('footer.policy_title') }}</h3>
                        <ul class="electro-footer-links">
                            @foreach ($footerInfo as $item)
                                <li>
                                    <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                                        {{ theme_menu_label($item) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Service --}}
                <div class="electro-col electro-col-footer">
                    <div class="electro-footer-block">
                        <h3 class="electro-footer-title">{{ theme_text('footer.claim') }}</h3>
                        <ul class="electro-footer-links">
                            @foreach ($footerService as $item)
                                <li>
                                    <a href="{{ theme_menu_url($item) }}" target="{{ theme_menu_target($item) }}" @if (theme_menu_rel($item)) rel="{{ theme_menu_rel($item) }}" @endif>
                                        {{ theme_menu_label($item) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOTTOM FOOTER --}}
    <div class="electro-bottom-footer">
        <div class="electro-container electro-text-center">
            <ul class="electro-footer-payments">
                <li><i class="fa fa-cc-visa"></i></li>
                <li><i class="fa fa-credit-card"></i></li>
                <li><i class="fa fa-cc-paypal"></i></li>
                <li><i class="fa fa-cc-mastercard"></i></li>
            </ul>
            <span class="electro-copyright">
                &copy; {{ date('Y') }} {{ theme_site_name() }}. {{ theme_text('footer.ready') }}
            </span>
            <ul class="electro-footer-social">
                @if ($facebookUrl !== '#' && $facebookUrl !== '')
                    <li><a href="{{ $facebookUrl }}" target="_blank" rel="noreferrer noopener"><i class="fa fa-facebook"></i></a></li>
                @endif
                @if ($twitterUrl !== '#' && $twitterUrl !== '')
                    <li><a href="{{ $twitterUrl }}" target="_blank" rel="noreferrer noopener"><i class="fa fa-twitter"></i></a></li>
                @endif
                @if ($instagramUrl !== '#' && $instagramUrl !== '')
                    <li><a href="{{ $instagramUrl }}" target="_blank" rel="noreferrer noopener"><i class="fa fa-instagram"></i></a></li>
                @endif
            </ul>
        </div>
    </div>
</footer>

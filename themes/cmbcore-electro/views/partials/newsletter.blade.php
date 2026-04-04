@php
    $facebookUrl = (string) theme_setting('facebook_url', '#');
    $twitterUrl = (string) theme_setting('twitter_url', '#');
    $instagramUrl = (string) theme_setting('instagram_url', '#');
@endphp

<div class="electro-newsletter electro-section">
    <div class="electro-container">
        <div class="electro-newsletter-inner">
            <p>{{ theme_text('home.register_kicker') }}</p>
            <form class="electro-newsletter-form" action="#" method="post">
                @csrf
                <input class="electro-input" type="email" name="email" placeholder="{{ theme_text('account.fields.email') }}" required>
                <button class="electro-newsletter-btn" type="submit">
                    <i class="fa fa-envelope"></i> {{ theme_text('home.actions.explore_slide') }}
                </button>
            </form>
            <ul class="electro-newsletter-follow">
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
</div>

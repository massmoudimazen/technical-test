@php
  $headerCtaUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
  if (!is_string($headerCtaUrl) || $headerCtaUrl === '') {
    $headerCtaUrl = home_url('/');
  }
@endphp

<header class="site-header {{ is_front_page() ? 'is-transparent' : 'is-solid' }}" data-site-header>
  <div class="site-header__inner">
    <a class="site-brand" href="{{ home_url('/') }}">
      {!! $siteName !!}
    </a>

    <div class="site-header__actions">
      @if (has_nav_menu('primary_navigation'))
        <nav
          id="primary-navigation"
          class="site-nav"
          data-nav-wrapper
          aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}"
        >
          {!! wp_nav_menu([
            'theme_location' => 'primary_navigation',
            'menu_class' => 'site-nav__menu',
            'container' => false,
            'echo' => false,
          ]) !!}
        </nav>

        <button
          type="button"
          class="site-nav__toggle"
          data-nav-toggle
          aria-controls="primary-navigation"
          aria-expanded="false"
        >
          <span class="sr-only">{{ __('Toggle navigation', 'sage') }}</span>
          <span class="site-nav__toggle-icon" aria-hidden="true"></span>
        </button>
      @endif

      <a class="site-header__cta" href="{{ esc_url($headerCtaUrl) }}">
        {{ __('Request Quote', 'sage') }}
      </a>
    </div>
  </div>
</header>

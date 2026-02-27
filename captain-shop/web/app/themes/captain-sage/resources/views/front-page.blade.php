@extends('layouts.app')

@section('content')
  @php
    $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');

    if (!is_string($shopUrl) || $shopUrl === '') {
      $shopUrl = home_url('/shop');
    }

    $chartsPage = get_pages([
      'post_type' => 'page',
      'meta_key' => '_wp_page_template',
      'meta_value' => 'template-market-charts.blade.php',
      'number' => 1,
    ]);

    $chartsUrl = !empty($chartsPage) ? get_permalink($chartsPage[0]->ID) : '';

    $featuredProducts = [];

    if (function_exists('wc_get_products')) {
      $featuredProducts = wc_get_products([
        'status' => 'publish',
        'featured' => true,
        'limit' => 4,
      ]);

      if (empty($featuredProducts)) {
        $featuredProducts = wc_get_products([
          'status' => 'publish',
          'orderby' => 'date',
          'order' => 'DESC',
          'limit' => 4,
        ]);
      }
    }
  @endphp

  <section class="captain-hero">
    <div class="captain-hero__surface">
      <p class="captain-eyebrow">{{ __('Captain Scrappin', 'sage') }}</p>
      <h1 class="captain-hero__title">{{ __('Precious Metals Trading Interface Built for Discipline', 'sage') }}</h1>
      <p class="captain-hero__lead">
        {{ __('Access synchronized gold and silver pricing, transparent inventory visibility, and enterprise execution confidence from one institutional dashboard.', 'sage') }}
      </p>

      <div class="captain-hero__actions">
        <a class="captain-btn" href="{{ esc_url($shopUrl) }}">{{ __('Explore Products', 'sage') }}</a>
        @if (!empty($chartsUrl))
          <a class="captain-btn captain-btn--ghost" href="{{ esc_url($chartsUrl) }}">{{ __('Open Market Charts', 'sage') }}</a>
        @endif
      </div>
    </div>

    <div class="captain-hero__metrics" aria-label="{{ __('Market metrics', 'sage') }}">
      <article class="captain-metric">
        <span>{{ __('Asset focus', 'sage') }}</span>
        <strong>{{ __('Gold & Silver', 'sage') }}</strong>
      </article>
      <article class="captain-metric">
        <span>{{ __('Pricing mode', 'sage') }}</span>
        <strong>{{ __('Live synced', 'sage') }}</strong>
      </article>
      <article class="captain-metric">
        <span>{{ __('Fulfillment model', 'sage') }}</span>
        <strong>{{ __('Physical inventory', 'sage') }}</strong>
      </article>
    </div>
  </section>

  <section class="captain-about">
    <div class="captain-panel">
      <h2>{{ __('Captain Scrappin Overview', 'sage') }}</h2>
      <p>
        {{ __('Captain Scrappin provides professional precious metals distribution with strict sourcing controls, synchronized market integration, and execution standards built for serious buyers.', 'sage') }}
      </p>
      <p>
        {{ __('Our frontend experience is designed like a financial terminal: clear market context, transparent stock visibility, and fast product decision workflows across desktop and mobile.', 'sage') }}
      </p>
    </div>
  </section>

  <section class="captain-featured" aria-labelledby="captain-featured-title">
    <div class="captain-section-header">
      <h2 id="captain-featured-title">{{ __('Featured Bullion Products', 'sage') }}</h2>
      <a class="captain-link" href="{{ esc_url($shopUrl) }}">{{ __('Browse full catalog', 'sage') }}</a>
    </div>

    @if (!empty($featuredProducts))
      <div class="captain-highlight-grid">
        @foreach ($featuredProducts as $product)
          @php
            $productId = $product->get_id();
            $stockStatus = $product->get_stock_status();
            $stockText = __('Out of stock', 'sage');
            $stockClass = 'is-out-stock';

            if ($stockStatus === 'instock') {
              $stockText = __('In stock', 'sage');
              $stockClass = 'is-in-stock';
            } elseif ($stockStatus === 'onbackorder') {
              $stockText = __('Backorder', 'sage');
              $stockClass = 'is-backorder';
            }

            $summary = trim((string) $product->get_short_description());
            if ($summary === '') {
              $summary = wp_trim_words(wp_strip_all_tags((string) $product->get_description()), 18);
            }
          @endphp

          <article class="captain-highlight-card">
            <a class="captain-highlight-card__media" href="{{ get_permalink($productId) }}" aria-label="{{ esc_attr(sprintf(__('View %s', 'sage'), $product->get_name())) }}">
              {!! $product->get_image('woocommerce_thumbnail', ['class' => 'captain-highlight-card__image', 'loading' => 'lazy']) !!}
            </a>

            <div class="captain-highlight-card__body">
              <h3>
                <a href="{{ get_permalink($productId) }}">{{ $product->get_name() }}</a>
              </h3>
              <p class="captain-highlight-card__price">{!! $product->get_price_html() !!}</p>
              <p class="captain-highlight-card__stock {{ $stockClass }}">{{ $stockText }}</p>

              @if (!empty($summary))
                <p class="captain-highlight-card__summary">{{ wp_strip_all_tags($summary) }}</p>
              @endif
            </div>
          </article>
        @endforeach
      </div>
    @else
      <div class="captain-panel">
        <p>{{ __('No featured products are currently available.', 'sage') }}</p>
      </div>
    @endif
  </section>
@endsection

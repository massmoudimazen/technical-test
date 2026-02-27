@extends('layouts.app')

@section('content')
  @while(have_posts())
    <?php the_post(); ?>
    @php
      do_action('woocommerce_before_single_product');
    @endphp

    @if (post_password_required())
      {!! get_the_password_form() !!}
      @php
        do_action('woocommerce_after_single_product');
      @endphp
      @break
    @endif

    @php
      global $product;

      if (!$product instanceof WC_Product) {
        $product = wc_get_product(get_the_ID());
      }

      if (!$product) {
        continue;
      }

      $captainOptions = function_exists('captain_opts') ? captain_opts() : get_option('captain_sync_options', []);
      $captainMapping = is_array($captainOptions['mapping'] ?? null) ? $captainOptions['mapping'] : [];
      $mappedSlug = isset($captainMapping[$product->get_id()]) ? sanitize_title((string) $captainMapping[$product->get_id()]) : '';

      $marketAverage = null;
      if ($mappedSlug !== '' && function_exists('captain_fetch_average_price')) {
        $marketAverage = captain_fetch_average_price($mappedSlug);
      }

      if (!is_numeric($marketAverage)) {
        $marketAverage = null;
      } else {
        $marketAverage = (float) $marketAverage;
      }

      $productPrice = (float) $product->get_price();
      $priceDiff = null;
      $priceDiffPercent = null;
      $priceDiffClass = 'is-flat';

      if ($marketAverage !== null) {
        $priceDiff = $productPrice - $marketAverage;
        $priceDiffPercent = $marketAverage > 0 ? ($priceDiff / $marketAverage) * 100 : null;

        if ($priceDiff > 0) {
          $priceDiffClass = 'is-above';
        } elseif ($priceDiff < 0) {
          $priceDiffClass = 'is-below';
        }
      }

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

      $chartsPage = get_pages([
        'post_type' => 'page',
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-market-charts.blade.php',
        'number' => 1,
      ]);

      $chartsUrl = !empty($chartsPage) ? get_permalink($chartsPage[0]->ID) : '';
    @endphp

    <article id="product-{{ $product->get_id() }}" <?php wc_product_class('captain-product-view', $product); ?>>
      <header class="captain-product-view__hero">
        <p class="captain-eyebrow">{{ __('Captain Scrappin Trading Desk', 'sage') }}</p>
        <h1 class="captain-product-view__title">{{ $product->get_name() }}</h1>
      </header>

      <div class="captain-product-view__layout">
        <section class="captain-product-view__gallery" aria-label="{{ __('Product gallery', 'sage') }}">
          @php
            do_action('woocommerce_before_single_product_summary');
          @endphp
        </section>

        <section class="captain-product-view__summary">
          <p class="captain-product-view__price">{!! $product->get_price_html() !!}</p>
          <p class="captain-product-view__stock {{ $stockClass }}">{{ $stockText }}</p>

          @if (!empty($product->get_short_description()))
            <div class="captain-product-view__excerpt">
              {!! wp_kses_post(wpautop($product->get_short_description())) !!}
            </div>
          @endif

          <div class="captain-product-view__cta">
            @php
              woocommerce_template_single_add_to_cart();
            @endphp
            @if (!empty($chartsUrl))
              <a class="captain-btn captain-btn--ghost" href="{{ esc_url($chartsUrl) }}">
                {{ __('Track Market Chart', 'sage') }}
              </a>
            @endif
          </div>

          <div class="captain-product-view__meta">
            @php
              woocommerce_template_single_meta();
            @endphp
          </div>
        </section>

        <aside class="captain-market-panel" aria-label="{{ __('Market information', 'sage') }}">
          <p class="captain-market-panel__label">{{ __('Latest average market price', 'sage') }}</p>

          @if ($marketAverage !== null)
            <p class="captain-market-panel__value">{!! wc_price($marketAverage) !!}</p>
          @else
            <p class="captain-market-panel__value">{{ __('Unavailable', 'sage') }}</p>
          @endif

          @if ($priceDiff !== null)
            <p class="captain-market-panel__delta {{ $priceDiffClass }}">
              @if ($priceDiff > 0)
                {{ __('Above market by', 'sage') }}
              @elseif ($priceDiff < 0)
                {{ __('Below market by', 'sage') }}
              @else
                {{ __('At market parity', 'sage') }}
              @endif

              @if ($priceDiff !== 0.0)
                <strong>{!! wc_price(abs($priceDiff)) !!}</strong>
                @if ($priceDiffPercent !== null)
                  <span>({{ number_format_i18n(abs($priceDiffPercent), 2) }}%)</span>
                @endif
              @endif
            </p>
          @else
            <p class="captain-market-panel__delta is-flat">
              {{ __('Market delta will appear when API data is available.', 'sage') }}
            </p>
          @endif

          @if (!empty($mappedSlug))
            <p class="captain-market-panel__source">
              {{ __('Reference product key:', 'sage') }} <code>{{ $mappedSlug }}</code>
            </p>
          @endif
        </aside>
      </div>

      <section class="captain-product-view__description">
        <h2>{{ __('Product Description', 'sage') }}</h2>
        <div class="captain-wysiwyg">
          @if (!empty($product->get_description()))
            {!! wp_kses_post(apply_filters('the_content', $product->get_description())) !!}
          @else
            <p>{{ __('Detailed product information is currently unavailable.', 'sage') }}</p>
          @endif
        </div>
      </section>

      <section class="captain-product-view__extensions">
        @php
          do_action('woocommerce_after_single_product_summary');
        @endphp
      </section>
    </article>

    @php
      do_action('woocommerce_after_single_product');
    @endphp
  @endwhile
@endsection

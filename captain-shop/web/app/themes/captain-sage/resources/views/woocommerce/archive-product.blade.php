@extends('layouts.app')

@section('content')
  @php
    $shopTitle = woocommerce_page_title(false);
    $shopDescription = term_description();
    $metalTaxonomy = taxonomy_exists('pa_metal') ? 'pa_metal' : 'product_cat';
    $metalTerms = get_terms([
      'taxonomy' => $metalTaxonomy,
      'hide_empty' => true,
    ]);

    if (!is_wp_error($metalTerms)) {
      $metalTerms = array_values(array_filter($metalTerms, static function ($term) use ($metalTaxonomy) {
        if ($metalTaxonomy === 'pa_metal') {
          return true;
        }

        $needle = strtolower($term->slug . ' ' . $term->name);
        return str_contains($needle, 'gold') || str_contains($needle, 'silver');
      }));
    } else {
      $metalTerms = [];
    }

    $chartsPage = get_pages([
      'post_type' => 'page',
      'meta_key' => '_wp_page_template',
      'meta_value' => 'template-market-charts.blade.php',
      'number' => 1,
    ]);

    $chartsUrl = !empty($chartsPage) ? get_permalink($chartsPage[0]->ID) : '';
  @endphp

  <section class="captain-shop-header">
    <div class="captain-shop-header__copy">
      <p class="captain-eyebrow">{{ __('Captain Scrappin Bullion Desk', 'sage') }}</p>
      <h1 class="captain-shop-header__title">{{ $shopTitle }}</h1>

      @if (!empty($shopDescription))
        <div class="captain-shop-header__description">
          {!! wp_kses_post($shopDescription) !!}
        </div>
      @else
        <p class="captain-shop-header__description">
          {{ __('Institutional-grade bullion access with synchronized live pricing and verified inventory levels.', 'sage') }}
        </p>
      @endif
    </div>

    @if (!empty($chartsUrl))
      <a class="captain-btn captain-btn--ghost" href="{{ esc_url($chartsUrl) }}">
        {{ __('Open Market Charts', 'sage') }}
      </a>
    @endif
  </section>

  @if (!empty($metalTerms))
    <section class="captain-shop-filters" data-metal-filters data-target-selector=".captain-product-card">
      <p class="captain-shop-filters__label">{{ __('Filter by metal', 'sage') }}</p>

      <div class="captain-shop-filters__buttons" role="toolbar" aria-label="{{ __('Product metal filters', 'sage') }}">
        <button type="button" class="captain-pill is-active" data-metal-filter="all" aria-pressed="true">
          {{ __('All metals', 'sage') }}
        </button>

        @foreach ($metalTerms as $term)
          <button type="button" class="captain-pill" data-metal-filter="{{ esc_attr($term->slug) }}" aria-pressed="false">
            {{ $term->name }}
          </button>
        @endforeach
      </div>
    </section>
  @endif

  @if (woocommerce_product_loop())
    <section class="captain-shop-loop" aria-label="{{ __('Product listing', 'sage') }}">
      <div class="captain-shop-loop__controls">
        @php
          do_action('woocommerce_before_shop_loop');
        @endphp
      </div>

      <ul class="products captain-product-grid" data-product-grid>
        @while(have_posts())
          <?php the_post(); ?>
          @php
            wc_get_template_part('content', 'product');
          @endphp
        @endwhile
      </ul>

      <div class="captain-shop-loop__pagination">
        @php
          do_action('woocommerce_after_shop_loop');
        @endphp
      </div>
    </section>
  @else
    <section class="captain-shop-empty">
      @php
        do_action('woocommerce_no_products_found');
      @endphp
    </section>
  @endif
@endsection

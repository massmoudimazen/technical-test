{{--
  Template Name: Market Charts
  Template Post Type: page
--}}

@extends('layouts.app')

@section('content')
  @php
    wp_enqueue_script(
      'captain-chartjs',
      'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
      [],
      '4.4.1',
      true
    );

    $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop');
    if (!is_string($shopUrl) || $shopUrl === '') {
      $shopUrl = home_url('/shop');
    }

    $options = function_exists('captain_opts') ? captain_opts() : get_option('captain_sync_options', []);
    $mapping = is_array($options['mapping'] ?? null) ? $options['mapping'] : [];

    $products = [];
    $series = [];
    $vendorPool = [];

    if (!empty($mapping) && function_exists('wc_get_product') && function_exists('captain_api_get')) {
      foreach ($mapping as $productId => $slug) {
        $productId = (int) $productId;
        $slug = sanitize_title((string) $slug);

        if ($productId <= 0 || $slug === '') {
          continue;
        }

        $product = wc_get_product($productId);
        if (!$product || $product->get_status() !== 'publish') {
          continue;
        }

        $products[] = [
          'id' => $productId,
          'name' => $product->get_name(),
          'slug' => $slug,
        ];

        $historyPayload = captain_api_get('/api/v1/products/' . rawurlencode($slug) . '/history');
        if (is_wp_error($historyPayload)) {
          $series[$slug] = [];
          continue;
        }

        $historyRows = $historyPayload['data']['history'] ?? [];
        if (!is_array($historyRows)) {
          $series[$slug] = [];
          continue;
        }

        $normalizedRows = [];

        foreach ($historyRows as $row) {
          if (!is_array($row)) {
            continue;
          }

          $timestamp = $row['timestamp'] ?? $row['scraped_at'] ?? $row['date'] ?? null;
          if (empty($timestamp)) {
            continue;
          }

          $averageValue = null;
          foreach (['average_sell_price', 'market_average', 'sell_price', 'price'] as $averageKey) {
            if (isset($row[$averageKey]) && is_numeric($row[$averageKey])) {
              $averageValue = (float) $row[$averageKey];
              break;
            }
          }

          $vendors = [];
          foreach (['vendors', 'entries', 'quotes'] as $vendorCollectionKey) {
            if (empty($row[$vendorCollectionKey]) || !is_array($row[$vendorCollectionKey])) {
              continue;
            }

            foreach ($row[$vendorCollectionKey] as $vendorEntry) {
              if (!is_array($vendorEntry)) {
                continue;
              }

              $vendorName = trim((string) ($vendorEntry['vendor'] ?? $vendorEntry['name'] ?? $vendorEntry['source'] ?? ''));
              if ($vendorName === '') {
                continue;
              }

              foreach (['sell_price', 'price', 'value', 'amount'] as $vendorPriceKey) {
                if (isset($vendorEntry[$vendorPriceKey]) && is_numeric($vendorEntry[$vendorPriceKey])) {
                  $vendors[$vendorName] = (float) $vendorEntry[$vendorPriceKey];
                  break;
                }
              }
            }
          }

          if (empty($vendors) && !empty($row['vendor']) && isset($row['sell_price']) && is_numeric($row['sell_price'])) {
            $vendors[(string) $row['vendor']] = (float) $row['sell_price'];
          }

          foreach (array_keys($vendors) as $vendorName) {
            $vendorPool[$vendorName] = true;
          }

          $normalizedRows[] = [
            'timestamp' => (string) $timestamp,
            'average_sell_price' => $averageValue,
            'vendors' => $vendors,
          ];
        }

        $series[$slug] = $normalizedRows;
      }
    }

    usort($products, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

    $payload = [
      'products' => $products,
      'series' => $series,
      'vendors' => array_values(array_keys($vendorPool)),
      'defaultRange' => '30d',
    ];
  @endphp

  <section class="captain-chart-page" data-captain-public-chart>
    <header class="captain-chart-page__header">
      <div>
        <p class="captain-eyebrow">{{ __('Captain Scrappin', 'sage') }}</p>
        <h1>{{ __('Public Market Charts', 'sage') }}</h1>
        <p>
          {{ __('Compare market averages and vendor pricing across selected bullion products, then refine the analysis window with institutional date controls.', 'sage') }}
        </p>
      </div>

      <a class="captain-btn captain-btn--ghost" href="{{ esc_url($shopUrl) }}">
        {{ __('Back to Products', 'sage') }}
      </a>
    </header>

    @if (!empty($products))
      <form class="captain-chart-controls" data-chart-controls>
        <label class="captain-field">
          <span>{{ __('Product', 'sage') }}</span>
          <select name="product" data-chart-product>
            @foreach ($products as $product)
              <option value="{{ esc_attr($product['slug']) }}">{{ $product['name'] }}</option>
            @endforeach
          </select>
        </label>

        <label class="captain-field">
          <span>{{ __('Vendor', 'sage') }}</span>
          <select name="vendor" data-chart-vendor>
            <option value="market">{{ __('Market average', 'sage') }}</option>
          </select>
        </label>

        <label class="captain-field">
          <span>{{ __('Date range', 'sage') }}</span>
          <select name="range" data-chart-range>
            <option value="7d">{{ __('Last 7 days', 'sage') }}</option>
            <option value="30d" selected>{{ __('Last 30 days', 'sage') }}</option>
            <option value="90d">{{ __('Last 90 days', 'sage') }}</option>
            <option value="custom">{{ __('Custom range', 'sage') }}</option>
          </select>
        </label>

        <label class="captain-field">
          <span>{{ __('From', 'sage') }}</span>
          <input type="date" name="from" data-chart-from>
        </label>

        <label class="captain-field">
          <span>{{ __('To', 'sage') }}</span>
          <input type="date" name="to" data-chart-to>
        </label>
      </form>

      <div class="captain-chart-canvas-wrap">
        <canvas id="captainPublicMarketChart" class="captain-chart-canvas" data-chart-canvas aria-label="{{ __('Market price chart', 'sage') }}"></canvas>
        <p class="captain-chart-empty" data-chart-empty hidden>
          {{ __('No chart data is available for the selected filters.', 'sage') }}
        </p>
      </div>

      <script type="application/json" data-chart-payload>{!! wp_json_encode($payload) !!}</script>
    @else
      <div class="captain-panel">
        <h2>{{ __('No chart mappings found', 'sage') }}</h2>
        <p>
          {{ __('Map WooCommerce products in Captain Sync settings to expose public price history on this page.', 'sage') }}
        </p>
      </div>
    @endif
  </section>
@endsection

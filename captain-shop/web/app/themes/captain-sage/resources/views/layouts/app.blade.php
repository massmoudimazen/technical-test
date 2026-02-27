<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(do_action('get_header'))
    @php(wp_head())

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class('site-body'))>
    @php(wp_body_open())

    <div id="app" class="site-shell">
      <a class="sr-only focus:not-sr-only" href="#main">
        {{ __('Skip to content', 'sage') }}
      </a>

      @include('sections.header')

      <main id="main" class="site-main">
        @hasSection('sidebar')
          <div class="content-grid">
            <div class="content-column">
              @yield('content')
            </div>

            <aside class="sidebar-column" aria-label="{{ __('Sidebar', 'sage') }}">
              @yield('sidebar')
            </aside>
          </div>
        @else
          <div class="content-grid content-grid--single">
            <div class="content-column">
              @yield('content')
            </div>
          </div>
        @endif
      </main>

      @include('sections.footer')
    </div>

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>

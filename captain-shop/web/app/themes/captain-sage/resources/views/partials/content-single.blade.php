<article @php(post_class('entry-single h-entry'))>
  <header class="entry-single__header">
    <h1 class="entry-single__title p-name">
      {!! $title !!}
    </h1>

    @include('partials.entry-meta')
  </header>

  <div class="entry-content e-content">
    @php(the_content())
  </div>

  @if ($pagination())
    <footer>
      <nav class="page-nav" aria-label="Page">
        {!! $pagination !!}
      </nav>
    </footer>
  @endif
</article>

@php(comments_template())

<article @php(post_class('entry-page'))>
  <div class="entry-content">
    @php(the_content())
  </div>

  @if ($pagination())
    <nav class="page-nav" aria-label="Page">
      {!! $pagination !!}
    </nav>
  @endif
</article>

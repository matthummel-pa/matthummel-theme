{{--
  Template Name: Legal / Policy
  Used for: Privacy Policy, Accessibility Statement, and similar single-column prose pages.
--}}

@extends('layouts.app')

@section('content')

<article class="legal-page container" data-anim="fade-up">
  <header class="legal-header">
    <h1 class="display-title is-page">{{ get_the_title() }}</h1>
    <p class="post-meta">
      {{ __('Last updated:', 'matthummel') }}
      <time datetime="{{ get_the_modified_date('c') }}">{{ get_the_modified_date() }}</time>
    </p>
  </header>

  <div class="entry-content post-prose">
    @php(the_content())
  </div>
</article>

@endsection

@php
  $pageTitle = (isset($title) && is_string($title) && $title !== '') ? $title : get_the_title();
@endphp
@component('partials.page-hero', ['tag' => 'div'])
  <h1 class="display-title is-hero">{{ $pageTitle }}</h1>
@endcomponent

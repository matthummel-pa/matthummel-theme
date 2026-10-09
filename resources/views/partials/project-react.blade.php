{{-- Like / star this project. Counts are visitor reactions, not GitHub stars. --}}
@php
  $postId = (int) ($postId ?? get_the_ID());
  $state = \App\mh_project_reaction_state($postId);
  $github = (string) ($github ?? '');
  $ajax = admin_url('admin-ajax.php');
  $nonce = wp_create_nonce('mh_project_react');
@endphp
<div
  class="project-react"
  data-project-react
  data-post="{{ $postId }}"
  data-ajax="{{ esc_url($ajax) }}"
  data-nonce="{{ esc_attr($nonce) }}"
>
  <p class="project-react__label">{{ __('Visitor feedback', 'sage') }}</p>
  <div class="project-react__row" role="group" aria-label="{{ __('Like or star this project', 'sage') }}">
    <button
      type="button"
      class="project-react__btn{{ $state['like'] ? ' is-on' : '' }}"
      data-react="like"
      aria-pressed="{{ $state['like'] ? 'true' : 'false' }}"
    >
      {!! \App\mh_svg_icon('heart', 16) !!}
      <span class="project-react__name">{{ __('Like this project', 'sage') }}</span>
      <span class="project-react__count" data-react-count="like">{{ number_format_i18n($state['likes']) }}</span>
    </button>
    <button
      type="button"
      class="project-react__btn{{ $state['star'] ? ' is-on' : '' }}"
      data-react="star"
      aria-pressed="{{ $state['star'] ? 'true' : 'false' }}"
    >
      {!! \App\mh_svg_icon('star', 16) !!}
      <span class="project-react__name">{{ __('Star project', 'sage') }}</span>
      <span class="project-react__count" data-react-count="star">{{ number_format_i18n($state['stars']) }}</span>
    </button>
    <a class="project-react__jump" href="#comments">
      {!! \App\mh_svg_icon('comment', 15) !!}
      {{ __('Leave a comment', 'sage') }}
    </a>
    <a class="project-react__jump" href="#project-ask">
      {!! \App\mh_svg_icon('mail', 15) !!}
      {{ __('Ask a question', 'sage') }}
    </a>
    @if ($github !== '' && str_starts_with($github, 'http'))
      <a class="project-react__jump" href="{{ esc_url($github) }}" rel="noopener" target="_blank">
        {!! \App\mh_svg_icon('github', 15) !!}
        {{ __('Star on GitHub', 'sage') }} <span aria-hidden="true">↗</span>
      </a>
    @endif
  </div>
  <p class="project-react__hint" data-react-status hidden></p>
</div>

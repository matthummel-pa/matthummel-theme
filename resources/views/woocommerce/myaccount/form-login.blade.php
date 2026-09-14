{{--
  Login / register desk — form left, tools right (matches cart/checkout).
--}}
@php
  defined('ABSPATH') || exit;
  $registrationEnabled = 'yes' === get_option('woocommerce_enable_myaccount_registration');
  $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
  $contactUrl = home_url('/contact/');
@endphp

@php do_action('woocommerce_before_customer_login_form'); @endphp

<div class="woo-desk woo-desk--login" id="customer_login">
  <div class="woo-desk__layout">
    <div class="woo-desk__main">
      <div class="woo-account-auth">
        <section class="woo-account-auth__card" aria-labelledby="woo-login-title">
          <h2 id="woo-login-title" class="woo-account-auth__title">{{ __('Log in', 'sage') }}</h2>
          <p class="woo-account-auth__lede">{{ __('Use the email from your receipt. Guest checkout works without an account.', 'sage') }}</p>

          <form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
            @php do_action('woocommerce_login_form_start'); @endphp

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
              <label for="username">{{ __('Username or email', 'sage') }}&nbsp;<span class="required" aria-hidden="true">*</span></label>
              <input
                type="text"
                class="woocommerce-Input woocommerce-Input--text input-text"
                name="username"
                id="username"
                autocomplete="username"
                value="{{ ! empty($_POST['username']) && is_string($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : '' }}"
                required
                aria-required="true"
              />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
              <label for="password">{{ __('Password', 'sage') }}&nbsp;<span class="required" aria-hidden="true">*</span></label>
              <input
                class="woocommerce-Input woocommerce-Input--text input-text"
                type="password"
                name="password"
                id="password"
                autocomplete="current-password"
                required
                aria-required="true"
              />
            </p>

            @php do_action('woocommerce_login_form'); @endphp

            <p class="form-row woo-account-auth__row">
              <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                <span>{{ __('Remember me', 'sage') }}</span>
              </label>
              @php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); @endphp
              <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="{{ esc_attr__('Log in', 'sage') }}">
                {{ __('Log in', 'sage') }}
              </button>
            </p>
            <p class="woocommerce-LostPassword lost_password">
              <a href="{{ esc_url(wp_lostpassword_url()) }}">{{ __('Lost your password?', 'sage') }}</a>
            </p>

            @php do_action('woocommerce_login_form_end'); @endphp
          </form>
        </section>

        @if ($registrationEnabled)
          <section class="woo-account-auth__card" aria-labelledby="woo-register-title">
            <h2 id="woo-register-title" class="woo-account-auth__title">{{ __('Create an account', 'sage') }}</h2>
            <p class="woo-account-auth__lede">{{ __('Optional. Handy if you want downloads in one place later.', 'sage') }}</p>

            <form method="post" class="woocommerce-form woocommerce-form-register register" @php do_action('woocommerce_register_form_tag'); @endphp>
              @php do_action('woocommerce_register_form_start'); @endphp

              @if ('no' === get_option('woocommerce_registration_generate_username'))
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                  <label for="reg_username">{{ __('Username', 'sage') }}&nbsp;<span class="required" aria-hidden="true">*</span></label>
                  <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="{{ ! empty($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : '' }}" required aria-required="true" />
                </p>
              @endif

              <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_email">{{ __('Email address', 'sage') }}&nbsp;<span class="required" aria-hidden="true">*</span></label>
                <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="{{ ! empty($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : '' }}" required aria-required="true" />
              </p>

              @if ('no' === get_option('woocommerce_registration_generate_password'))
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                  <label for="reg_password">{{ __('Password', 'sage') }}&nbsp;<span class="required" aria-hidden="true">*</span></label>
                  <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
                </p>
              @else
                <p>{{ __('A link to set your password will be sent to your email address.', 'sage') }}</p>
              @endif

              @php do_action('woocommerce_register_form'); @endphp

              <p class="woocommerce-form-row form-row">
                @php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); @endphp
                <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="{{ esc_attr__('Register', 'sage') }}">
                  {{ __('Register', 'sage') }}
                </button>
              </p>

              @php do_action('woocommerce_register_form_end'); @endphp
            </form>
          </section>
        @endif
      </div>
    </div>

    <aside class="woo-desk__aside" aria-label="{{ esc_attr__('Account help', 'sage') }}">
      <section class="side-card woo-desk-card" aria-labelledby="woo-login-next-h">
        <h2 id="woo-login-next-h" class="side-card-title">{{ __('What you get here', 'sage') }}</h2>
        <ol class="woo-desk-steps">
          <li class="woo-desk-steps__item">
            <span class="woo-desk-steps__num" aria-hidden="true">1</span>
            <span class="woo-desk-steps__copy">
              <strong>{{ __('Downloads', 'sage') }}</strong>
              <span>{{ __('Theme and plugin zips from your orders.', 'sage') }}</span>
            </span>
          </li>
          <li class="woo-desk-steps__item">
            <span class="woo-desk-steps__num" aria-hidden="true">2</span>
            <span class="woo-desk-steps__copy">
              <strong>{{ __('Order history', 'sage') }}</strong>
              <span>{{ __('Receipts and status in one list.', 'sage') }}</span>
            </span>
          </li>
          <li class="woo-desk-steps__item">
            <span class="woo-desk-steps__num" aria-hidden="true">3</span>
            <span class="woo-desk-steps__copy">
              <strong>{{ __('Update notices', 'sage') }}</strong>
              <span>{{ __('I email this address when a zip you bought gets a new version.', 'sage') }}</span>
            </span>
          </li>
        </ol>
      </section>

      <section class="side-card woo-desk-card" aria-labelledby="woo-login-facts-h">
        <h2 id="woo-login-facts-h" class="side-card-title">{{ __('Good to know', 'sage') }}</h2>
        <ul class="woo-desk-trust">
          <li class="woo-desk-trust__item">
            {!! \App\mh_svg_icon('check', 14) !!}
            <span>{{ __('Guest checkout does not require an account', 'sage') }}</span>
          </li>
          <li class="woo-desk-trust__item">
            {!! \App\mh_svg_icon('mail', 14) !!}
            <span>{{ __('Use the same email as your receipt', 'sage') }}</span>
          </li>
          <li class="woo-desk-trust__item">
            {!! \App\mh_svg_icon('download', 14) !!}
            <span>{{ __('Download links also live in the receipt email', 'sage') }}</span>
          </li>
        </ul>
      </section>

      <section class="side-card woo-desk-card woo-desk-card--help" aria-labelledby="woo-login-help-h">
        <h2 id="woo-login-help-h" class="side-card-title">{{ __('Stuck?', 'sage') }}</h2>
        <p class="woo-desk-help__text">{{ __('Cannot find the zip or reset email? Say hello and I will dig it up.', 'sage') }}</p>
        <div class="woo-desk-help__actions">
          <a class="woo-desk-help__btn" href="{{ esc_url($contactUrl) }}">
            {!! \App\mh_svg_icon('mail', 14) !!}
            {{ __('Say hello', 'sage') }}
          </a>
          <a class="woo-desk-help__link" href="{{ esc_url($shopUrl) }}">{{ __('Browse shop', 'sage') }}</a>
        </div>
      </section>
    </aside>
  </div>
</div>

@php do_action('woocommerce_after_customer_login_form'); @endphp

# WordPress / Sage code review checklist

The rules below come from the WordPress Developer Handbook and the Sage docs. Claude,
Cursor, and humans use the same list. `wp-review` automates the parts marked **[auto]**.

Severity: **Blocker** = must fix before merge · **Should fix** = fix in this PR unless
there is a reason · **Nit** = optional.

---

## 1. Security — Blocker

| Check | Rule | Docs |
| --- | --- | --- |
| Escape late **[auto]** | Every echo of dynamic data goes through `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`, `wp_kses_post()` *at the point of output*. In Blade, `{{ }}` escapes; `{!! !!}` does not. | [Escaping](https://developer.wordpress.org/apis/security/escaping/) |
| Sanitize early **[auto]** | `$_GET/$_POST/$_REQUEST/$_COOKIE/$_SERVER` → `wp_unslash()` then `sanitize_text_field()`, `absint()`, `sanitize_email()`, `sanitize_key()`… | [Sanitizing](https://developer.wordpress.org/apis/security/sanitizing/) |
| Validate | Check `isset()` and expected type/allow-list before use. | [Validation](https://developer.wordpress.org/apis/security/data-validation/) |
| Nonces **[auto]** | Every form, AJAX, and state-changing action verifies a nonce (`wp_verify_nonce`, `check_admin_referer`, `check_ajax_referer`). | [Nonces](https://developer.wordpress.org/apis/security/nonces/) |
| Capabilities | Nonce ≠ permission. Also check `current_user_can()` for admin/editor actions. | [Checking capabilities](https://developer.wordpress.org/plugins/security/checking-user-capabilities/) |
| REST endpoints | `register_rest_route()` always has a real `permission_callback` (use `__return_true` only for truly public read endpoints) and `args` with `sanitize_callback`/`validate_callback`. | [Custom endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/) |
| SQL **[auto]** | Any `$wpdb` query with variables uses `$wpdb->prepare()`. Prefer `WP_Query`/`get_posts` over raw SQL. | [wpdb::prepare](https://developer.wordpress.org/reference/classes/wpdb/prepare/) |
| Redirects **[auto]** | `wp_safe_redirect()` + `exit;` for user-supplied URLs. | [wp_safe_redirect](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| Secrets | No tokens, keys, passwords, `.env`, or `wp-config.php` in the repo or workflow files. Use constants/env (`MH_GITHUB_TOKEN`, `KS_GITHUB_TOKEN`). | — |
| File access | No direct file writes from request data; use `WP_Filesystem` / `wp_upload_bits()`. Don't include files from user input. | [Filesystem API](https://developer.wordpress.org/apis/filesystem/) |

## 2. WordPress APIs — Should fix

| Check | Rule | Docs |
| --- | --- | --- |
| Enqueue assets **[auto]** | Scripts/styles via `wp_enqueue_*` (Sage: `@vite` / `bundle()`), never hard-coded `<script src>` / `<link>` in templates. Include a version. | [Including assets](https://developer.wordpress.org/themes/core-concepts/including-assets/) |
| Hooks, not edits | Change behavior with actions/filters; never edit core or vendor files. | [Hooks](https://developer.wordpress.org/plugins/hooks/) |
| Queries | No `query_posts()` **[auto]**. Avoid `posts_per_page => -1` on the front end **[auto]**. Use `no_found_rows => true` when not paginating; `fields => 'ids'` when you only need IDs. | [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/) |
| Caching | Expensive remote calls (GitHub, DEV.to) go through transients with a sane TTL and fail soft. | [Transients](https://developer.wordpress.org/apis/transients/) |
| HTTP | `wp_remote_get()`/`wp_safe_remote_get()` instead of `curl_*`/`file_get_contents()` for URLs **[auto]**; check `is_wp_error()`. | [HTTP API](https://developer.wordpress.org/plugins/http-api/) |
| i18n **[auto]** | Every visitor-facing string uses `__()`/`esc_html__()` with the theme text domain; placeholders use `sprintf` + translators comment. | [Internationalization](https://developer.wordpress.org/apis/internationalization/) |
| Deprecated **[auto]** | No deprecated WP functions for the declared "Requires at least". | — |
| Plugin territory | Themes present content; CPTs, shortcodes, and data that must survive a theme switch belong in a plugin (Acreline Core, mu-plugins). | [Theme review: plugin territory](https://make.wordpress.org/themes/handbook/review/required/) |
| Cron | Scheduled events check `wp_next_scheduled()` before scheduling and unschedule on deactivation. | [Cron](https://developer.wordpress.org/plugins/cron/) |

## 3. Sage / Acorn specifics — Should fix

- Logic in `app/` (composers, providers, helpers); Blade stays presentational. See [Sage docs](https://roots.io/sage/docs/).
- Data for views comes from **View Composers** (`app/View/Composers`), not `global $post` gymnastics in Blade.
- Vite `base` in `vite.config.js` matches the real theme folder name, or every asset 404s.
- `public/build/` is never committed; the release workflow builds it.
- After Blade changes, `wp acorn view:clear` (or `studio wp acorn view:clear`) before judging output.
- `vendor/` in the theme is for production deps only — run `composer install --no-dev` for zips.
- Visitor-facing copy lives in wp-admin fields (`app/page-fields.php` / block attributes), not hard-coded in Blade.

## 4. Accessibility — Should fix

WordPress code must conform to WCAG 2.2 level AA; hold themes to the same bar. [Accessibility standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/)

- Semantic elements (`button` for actions, `a` for navigation, landmarks, one `h1`).
- Visible focus (no `outline: none` without a replacement), 44×44 px touch targets.
- Contrast 4.5:1 body text, 3:1 large text/UI. Status not by color alone.
- Form fields have visible `<label>`s; dynamic results use `aria-live`.
- Respect `prefers-reduced-motion`.
- Images: meaningful `alt`, or `alt=""` if decorative.

## 5. Performance & SEO — Should fix

- Images have `width`/`height` (or `aspect-*`) to prevent CLS; `loading="lazy"` below the fold; hero gets `fetchpriority="high"`.
- No render-blocking third-party scripts; defer/async where safe.
- No N+1 queries inside loops (`get_post_meta` in a loop is fine — it's cached — but `new WP_Query` in a loop is not).
- One `<title>` stack (theme `title-tag` support or the SEO plugin), not both.
- JSON-LD only when valid and truthful; built with `wp_json_encode()`.

## 6. Code quality — Nit unless it hides a bug

- Pint passes (`vendor/bin/pint --test`) **[auto]**.
- Guard clauses first, happy path last. Small functions with typed params/returns (PHP 8.3).
- Names say what things are; no dead code or commented-out blocks.
- `CHANGELOG.md` / `docs/FEATURES.md` updated for user-visible changes.

---

## How to write a review comment

```
[Blocker] app/contact.php:42 — `$_POST['email']` is used without unslash/sanitize.
Why: raw input can carry slashes and markup (developer.wordpress.org/apis/security/sanitizing/).
Fix: $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
```

End the review with: what's good (one line), the verdict (approve / changes requested),
and at most one optional idea.

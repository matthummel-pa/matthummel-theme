# Updating the theme

One loop for every change: branch, edit, check locally, merge to `main`, update the live site.

## 1. Work locally
- Site: the WordPress Studio site `matthummel-theme` (`studio status` prints its URL).
- Theme folder: `wp-content/themes/matthummel`.
- Create a branch first: `git switch -c feature/<short-name>`.
- Lint PHP before committing: `php -l app/<file>.php` and `vendor/bin/pint --test`.

## 2. Check in a browser
- Open the feature and use it: click, type, submit.
- Open every page that shares the same data or components.
- Check a desktop width and a phone width.

## 3. Ship through GitHub
1. Commit only the files you changed: `git add <files>` then `git commit`.
2. `git push -u origin feature/<short-name>` and open a pull request.
3. Merge to `main`. `.github/workflows/deploy.yml` builds the theme and publishes the zip as release `theme-latest`.

## 4. Update the live site (Hostinger)
- WordPress admin: Appearance → **Update Theme** → Update theme from GitHub.
- Or WP-CLI: `wp mh theme-update`.
- Then purge LiteSpeed Cache.
- One-time token setup: `docs/sage/deployment.md`.

## Social API tokens
Set under Appearance → Social & AI (also listed under Hummel Ops). Each service links to the page that issues its token, its setup guide, and its account page. Tokens can instead be set as constants in `wp-config.php` so they stay out of the database:

| Constant | Used for |
|---|---|
| `MH_ANTHROPIC_API_KEY` | Claude Cowork drafting |
| `MH_XAI_API_KEY` | Grokbot drafting |
| `MH_FACEBOOK_PAGE_TOKEN` | Facebook Page token |

Code: `app/social-settings.php` (the screen), `app/social-ai.php` (providers, models, token lookups), `app/social-share.php` (the post editor box).

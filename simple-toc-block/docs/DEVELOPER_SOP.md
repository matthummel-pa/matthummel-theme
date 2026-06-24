# Developer SOP — Simple TOC Block

Standard Operating Procedure for contributing code to **Simple TOC Block**, a
single-purpose WordPress block plugin. Follow this end to end and your change
should sail through review.

> New here? Read the architecture notes in [`CLAUDE.md`](../CLAUDE.md) first —
> it explains how the TOC is generated and why the PHP is structured the way it is.

---

## 1. Prerequisites

| Tool | Version | Why |
| --- | --- | --- |
| Node.js | 18+ (20 LTS recommended) | Runs the `@wordpress/scripts` build chain. |
| npm | 9+ | Ships with Node. |
| Git | any recent | Version control. |
| A local WordPress site | WP 6.4+ / PHP 7.4+ | To actually test the block. Use [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), [Local](https://localwp.com/), or Docker. |

Optional but recommended: VS Code with the **PHP** and **ESLint** extensions.

---

## 2. One-time setup

```bash
# 1. Fork the repo on GitHub (button top-right), then clone YOUR fork:
git clone https://github.com/<your-username>/simple-toc-block.git
cd simple-toc-block

# 2. Point "upstream" at the original repo so you can stay in sync:
git remote add upstream https://github.com/matthummel/simple-toc-block.git

# 3. Install build tooling:
npm install
```

> The compiled `build/` directory is **git-ignored**. You must build at least
> once before the plugin will run.

---

## 3. Run a local dev loop

```bash
npm run start      # watch mode: rebuilds /build on every save
```

Then make the plugin visible to WordPress (pick one):

- **wp-env:** run `npx wp-env start` from the plugin folder — it mounts the
  plugin automatically. Site is at `http://localhost:8888` (admin / password).
- **Local / manual:** symlink or copy this folder into
  `wp-content/plugins/simple-toc-block` of your test site.

Activate **Simple TOC Block** under *Plugins*, create a post with a few H2/H3
headings, add the **Table of Contents** block, and preview.

For a production-style build (minified, what ships):

```bash
npm run build
```

---

## 4. Branching & commits

- Branch off an up-to-date `main`:
  ```bash
  git fetch upstream && git checkout -b feat/scroll-offset upstream/main
  ```
- Use short, descriptive branch prefixes: `feat/`, `fix/`, `docs/`, `chore/`.
- Write present-tense, imperative commit messages
  (e.g. `Add scroll offset for sticky headers`). [Conventional
  Commits](https://www.conventionalcommits.org/) are welcome but not required.
- Keep each PR focused on one change.

---

## 5. Code standards

**PHP** (follow [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)):

- Tabs for indentation, not spaces.
- **Escape on output** (`esc_html`, `esc_attr`, `esc_url`) and sanitize on input.
- Prefix every global function with `simple_toc_` to avoid name collisions.
- All helper functions live in the root `simple-toc-block.php`. **Never declare
  functions in `src/render.php`** — it is included on every render and would
  fatally redeclare them.
- Use the text domain `simple-toc-block` for every translatable string.

**JavaScript / JSX**:

- Use `@wordpress/*` packages (don't pull in React directly).
- Lint before pushing:
  ```bash
  npm run lint:js
  npm run lint:css
  npm run format   # auto-fixes most issues
  ```

---

## 6. Testing checklist (do this before opening a PR)

- [ ] `npm run build` completes with no errors.
- [ ] `npm run lint:js` and `npm run lint:css` pass.
- [ ] Block inserts, renders in the editor, and saves without console errors.
- [ ] Front end shows the TOC and links **scroll to the correct headings**.
- [ ] Level toggles (H2/H3/H4) and the ordered/unordered toggle behave.
- [ ] Duplicate heading text still produces unique, working anchors.
- [ ] No PHP notices/warnings with `WP_DEBUG` enabled.

---

## 7. Opening a Pull Request

1. Push your branch to your fork: `git push origin feat/scroll-offset`.
2. Open a PR against `matthummel/simple-toc-block:main`.
3. Fill in the PR template: what changed, why, and how you tested it.
4. Link any related issue (`Closes #12`).
5. Respond to review feedback by pushing follow-up commits to the same branch.

A maintainer reviews, may request changes, then squash-merges.

---

## 8. Release process (maintainers)

1. Update the version in **three** places so they always match:
   - `simple-toc-block.php` (`Version:` header)
   - `package.json` (`version`)
   - `readme.txt` (`Stable tag`)
   - `src/block.json` (`version`)
2. Add a dated entry to `CHANGELOG.md` and the `== Changelog ==` section of `readme.txt`.
3. `npm run build` then `npm run plugin-zip` to produce the installable `.zip`.
4. Commit, tag (`git tag v0.2.0 && git push --tags`), and create a GitHub Release,
   attaching the `.zip`.
5. (If publishing to WordPress.org) sync the build to the SVN trunk/tag.

---

## 9. Getting help

- Open a [GitHub Issue](https://github.com/matthummel/simple-toc-block/issues)
  for bugs or feature ideas.
- Use [Discussions](https://github.com/matthummel/simple-toc-block/discussions)
  (if enabled) for questions.
- See the roadmap in `CLAUDE.md` for what's planned and what's intentionally
  out of scope for the free version.

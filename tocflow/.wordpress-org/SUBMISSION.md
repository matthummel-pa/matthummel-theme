# Publishing TOCflow to the WordPress.org Plugin Directory

This is the step-by-step process to get TOCflow listed on
[wordpress.org/plugins](https://wordpress.org/plugins/) so anyone can install it
straight from their WP admin (**Plugins → Add New → Search**).

## What's already prepared

- ✅ **`readme.txt`** — formatted to the [WordPress readme standard](https://wordpress.org/plugins/developers/#readme)
  (header, short description ≤150 chars, Description, Installation, FAQ,
  Screenshots, Changelog, Upgrade Notice).
- ✅ **Directory assets** in this `.wordpress-org/` folder:
  | File | Size | Purpose |
  | --- | --- | --- |
  | `icon-256x256.png` / `icon-128x128.png` / `icon.svg` | square | Plugin icon |
  | `banner-1544x500.png` | 1544×500 | Hi-res header banner |
  | `banner-772x250.png` | 772×250 | Standard header banner |
  | `screenshot-1.png` | settings panel | Matches Screenshot #1 in readme |
  | `screenshot-2.png` | front-end output | Matches Screenshot #2 in readme |

> Asset files live in the SVN **`/assets`** directory, **not** inside the plugin
> ZIP. Screenshot files must be named `screenshot-1.png`, `screenshot-2.png`, … to
> match the numbered captions in `readme.txt`.

## Before you submit — checklist

- [ ] Set **`Contributors:`** in `readme.txt` to your real WordPress.org username
      (currently `matthummel` — change if your w.org login differs).
- [ ] Confirm **`Tested up to:`** matches the current WordPress version.
- [ ] Confirm the version matches in all four places: `tocflow.php`,
      `package.json`, `src/block.json`, and `readme.txt` (`Stable tag`).
- [ ] Run `npm run build` so `build/` is current, then build the ZIP.
- [ ] Verify the plugin activates with **no PHP notices** on a clean install.
- [ ] Make sure the plugin slug is unique (search the directory for "tocflow").

## Step 1 — Submit for review

1. Sign in at [wordpress.org/plugins/developers/add](https://wordpress.org/plugins/developers/add/).
2. Upload the plugin ZIP (the same `tocflow.zip` attached to the
   [GitHub release](https://github.com/matthummel-pa/tocflow/releases/latest)).
3. The Plugin Review Team runs an automated check, then a manual review.
   First-time reviews typically take a few days to a couple of weeks.
4. Respond to any feedback by email; resubmit if changes are requested.

## Step 2 — After approval (SVN)

WordPress.org hosts plugins in **Subversion**, not Git. Once approved you'll get
an SVN repo at `https://plugins.svn.wordpress.org/tocflow/`.

```bash
svn co https://plugins.svn.wordpress.org/tocflow tocflow-svn
cd tocflow-svn

# Plugin code goes in trunk/
cp -r /path/to/tocflow/{tocflow.php,readme.txt,build} trunk/

# Directory assets (icon/banner/screenshots) go in assets/
cp /path/to/tocflow/.wordpress-org/{icon-*.png,icon.svg,banner-*.png,screenshot-*.png} assets/

svn add --force trunk assets
svn ci -m "Initial release 0.1.0"

# Tag the release
svn cp trunk tags/0.1.0
svn ci -m "Tag 0.1.0"
```

The directory shows whatever is in `tags/<Stable tag>`. To ship an update: bump
the version, copy new code into `trunk/`, create a new `tags/X.Y.Z`, and commit.

## Common rejection reasons to avoid

- Calling functions/variables without a unique prefix (TOCflow uses `tocflow_`).
- Not escaping output or sanitizing input (already handled in `tocflow.php`).
- Loading remote scripts/styles, or bundling minified code without source.
- Missing or generic `readme.txt`, or a `Tested up to` that's too old.

## Useful links

- Plugin Handbook: https://developer.wordpress.org/plugins/
- Detailed Plugin Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- readme validator: https://wordpress.org/plugins/developers/readme-validator/

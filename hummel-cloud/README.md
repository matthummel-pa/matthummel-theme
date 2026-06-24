# Hummel Cloud ☁️

A private, invite-only family web app: secure document storage plus a personal productivity suite — built with React, Vite, Tailwind CSS, and Supabase, deployed on Netlify.

**Live:** https://hummel-family-cloud.netlify.app

---

## Features

**Files** — upload (drag & drop), folders, download, delete, search, sort, in-browser preview (images, PDF, video, audio, text), and time-limited share links. Files live in a private Supabase Storage bucket.

**Home dashboard** — profile header with cover photo, upcoming calendar events, pinned to-dos, latest notes, favorite documents, favorite trending reads, a family photo gallery, and Microsoft 365 quick links.

**Trending** — live news + social search on any topic with custom categories (saved per device), endless scroll, shuffle refresh, location-aware language/market, per-article summaries (extractive), a text-to-speech reader with voice & speed options, and one-tap favoriting.

**Blog** — pulls post metadata live from a WordPress site (matthummel.com), with per-post social sharing to Reddit, dev.to, Bluesky, and Facebook, optional Claude-powered draft generation (bring your own Anthropic API key), and an openly-licensed image finder (Openverse).

**Notes** — WYSIWYG editor (headings, lists, checklists, links, inline images), file attachments, customizable notebooks (icon + color), and publishing to Notion (real API) or GoodNotes (PDF export) — or a standalone in-app notebook with no external service. First-run setup wizard.

**To-dos** — priorities, due dates with overdue/today highlighting, tags, notes, star-to-pin-on-Home, smart sorting, filters, search, and a progress bar.

**Calendar** — month / week / agenda views, click-to-create, recurring events (daily/weekly/monthly/yearly), all-day and multi-day events, custom calendars with colors, and family-shared collaborative calendars.

**Profile** — avatar, cover photo, display name, bio, location, website, storage stats, and a family members list.

**Settings** — light/dark/system theme, default start page, file sort, share-link expiry, location detection (with consent), news market & language, Claude AI connection, Notes publishing service, and security controls.

**Admin (owner/administrator only)** — user list with usage stats, manual user creation, password reset (email or temporary), 2FA reset, suspend/reactivate, remove, masked last-seen IP addresses, recent activity log, CSV export, and WordPress-style roles (Administrator / Editor / Author / Contributor / Subscriber).

## Security

- Email allowlist — only invited addresses can register (enforced by a DB trigger).
- Row-level security on every table; users only see their own data (plus shared calendars).
- Two-factor authentication (TOTP) — required at every sign-in once enabled.
- Role-based permissions, with read-only **Subscriber** enforced at the database level.
- Owner/administrator-only admin functions, verified server-side in an edge function.
- Auto sign-out on inactivity; strong password policy (10+ chars, letters + numbers).
- Security headers (HSTS, X-Frame-Options, nosniff, Permissions-Policy).
- Last-seen IP addresses masked in the database before they ever reach the browser.

## Tech stack

- **Frontend:** React 18, Vite 6, Tailwind CSS v4
- **Backend:** Supabase (Postgres + Auth + Storage + Edge Functions)
- **Hosting:** Netlify
- **Edge functions (Deno):** `trending`, `summarize`, `blog`, `images`, `notion`, `admin`

## Local development

```bash
npm install
npm run dev      # local dev server
npm run build    # production build to dist/
```

Create a `.env` file (see `.env.example`) with your Supabase URL and publishable key. These are safe to expose in the client — security is enforced by row-level security in the database, not by hiding the key.

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for the full setup: creating the Supabase project, running the migrations, deploying the edge functions, and connecting Netlify.

## Project structure

```
src/
  App.jsx          Routing, auth gate, 2FA gate, auto-lock, theme
  Login.jsx        Sign in / sign up / reset
  MfaGate.jsx      Two-factor code screen
  Home.jsx         Dashboard
  FileManager.jsx  Files
  Trending.jsx     News/social trending
  Blog.jsx         Blog + social sharing
  Notes.jsx        Notes + notebooks + publishing
  Todos.jsx        Task manager
  Calendar.jsx     Calendar
  Profile.jsx      Profile
  Settings.jsx     Settings
  Admin.jsx        Admin panel
  supabase.js      Supabase client
  settings.js      Local settings + helpers
  claude.js        Anthropic API client (key stored locally)
  styles.css       Tailwind + brand theme + dark mode
netlify.toml       Build config + security headers + SPA redirect
```

## License

Private family project. Not licensed for redistribution.

— Built by Matt Hummel · matthummel.com

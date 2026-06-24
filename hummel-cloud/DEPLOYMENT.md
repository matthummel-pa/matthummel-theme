# Deployment guide

How to stand up your own copy of Hummel Cloud.

## 1. Supabase

1. Create a project at [supabase.com](https://supabase.com).
2. In the SQL editor, run the migrations in `supabase/migrations/` in order (they create the tables, row-level security policies, allowlist, roles, and admin helpers).
3. Create a **private** storage bucket named `documents`.
4. Authentication → URL Configuration → set **Site URL** to your deployed site URL (so confirmation/reset emails link correctly).
5. (Recommended) Authentication → Settings → enable **leaked password protection**.
6. Copy your Project URL and the **publishable** (anon) key for the next step.

## 2. Edge functions

Deploy each function in `supabase/functions/` with the Supabase CLI:

```bash
supabase functions deploy trending
supabase functions deploy summarize
supabase functions deploy blog
supabase functions deploy images
supabase functions deploy notion
supabase functions deploy admin
```

The `admin` function uses the service-role key (provided automatically as `SUPABASE_SERVICE_ROLE_KEY` in the function environment). Set the owner email inside `admin/index.ts` and the edge functions before deploying.

## 3. Allowlist

Add the emails allowed to register:

```sql
insert into public.allowed_users (email) values ('you@example.com');
```

## 4. Environment

Create `.env` from `.env.example`:

```
VITE_SUPABASE_URL=https://YOUR-PROJECT.supabase.co
VITE_SUPABASE_KEY=YOUR-PUBLISHABLE-KEY
```

On Netlify, add these as environment variables (Site settings → Environment variables) so production builds pick them up.

## 5. Netlify

1. Connect the repo (or drag-and-drop the project) at [netlify.com](https://netlify.com).
2. Build command: `npm run build` · Publish directory: `dist` (already in `netlify.toml`).
3. Deploy. The `netlify.toml` adds the SPA redirect and security headers automatically.

## 6. First sign-in

Open the site → "First time? Create account" → use an allowlisted email and a strong password → confirm via email → sign in. Then enable 2FA under Settings → Security.

## Optional integrations

- **Claude AI** (Blog drafts): create an API key at [console.anthropic.com](https://console.anthropic.com) and paste it under Settings → Claude AI. Stored only in the browser.
- **Notion** (Notes publishing): create an internal integration at [notion.so/my-integrations](https://www.notion.so/my-integrations), share a page with it, and connect it in the Notes setup wizard.

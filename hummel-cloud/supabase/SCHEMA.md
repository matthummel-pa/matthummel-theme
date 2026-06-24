# Database schema reference

A snapshot of the Supabase schema this app expects. The live project also
includes the `is_allowed()`, `user_role()`, `can_write()`, `admin_stats()`,
and `admin_set_role()` functions, the `enforce_allowlist` signup trigger,
and a private `documents` storage bucket. Recreate these in a new project's
SQL editor (see DEPLOYMENT.md).

## Tables

```sql
CREATE TABLE public.allowed_users (
  email text not null primary key,
  added_at timestamptz default now() not null
);

CREATE TABLE public.profiles (
  id uuid not null primary key references auth.users(id) on delete cascade,
  display_name text, bio text, location text, website text,
  avatar_path text, cover_path text,
  updated_at timestamptz default now() not null
);

CREATE TABLE public.user_roles (
  user_id uuid not null primary key references auth.users(id) on delete cascade,
  role text default 'author' not null
    check (role in ('administrator','editor','author','contributor','subscriber')),
  updated_at timestamptz default now() not null
);

CREATE TABLE public.favorites (
  id bigint generated always as identity primary key,
  user_id uuid not null references auth.users(id) on delete cascade,
  kind text not null check (kind in ('doc','article')),
  payload jsonb not null,
  created_at timestamptz default now() not null,
  unique (user_id, kind, payload)
);

CREATE TABLE public.notebooks (
  id uuid default gen_random_uuid() primary key,
  user_id uuid not null references auth.users(id) on delete cascade,
  name text not null, icon text default '📓' not null,
  color text default 'violet' not null,
  created_at timestamptz default now() not null
);

CREATE TABLE public.notes (
  id uuid default gen_random_uuid() primary key,
  user_id uuid not null references auth.users(id) on delete cascade,
  title text default 'Untitled' not null,
  content text default '' not null,
  content_html text,
  attachments jsonb default '[]' not null,
  notebook_id uuid references public.notebooks(id) on delete set null,
  updated_at timestamptz default now() not null
);

CREATE TABLE public.todos (
  id bigint generated always as identity primary key,
  user_id uuid not null references auth.users(id) on delete cascade,
  title text not null, notes text, due_date date,
  priority text default 'medium' not null check (priority in ('high','medium','low')),
  tags text[] default '{}' not null,
  starred boolean default false not null,
  done boolean default false not null, done_at timestamptz,
  created_at timestamptz default now() not null
);

CREATE TABLE public.calendars (
  id uuid default gen_random_uuid() primary key,
  user_id uuid not null references auth.users(id) on delete cascade,
  name text not null, color text default 'violet' not null,
  shared boolean default false not null,
  created_at timestamptz default now() not null
);

CREATE TABLE public.events (
  id bigint generated always as identity primary key,
  user_id uuid not null references auth.users(id) on delete cascade,
  title text not null, description text, location text,
  start_at timestamptz not null, end_at timestamptz not null,
  all_day boolean default false not null,
  color text default 'violet' not null,
  recur text default 'none' not null check (recur in ('none','daily','weekly','monthly','yearly')),
  recur_until date,
  calendar_id uuid references public.calendars(id) on delete cascade,
  created_at timestamptz default now() not null
);
```

Every table has Row Level Security enabled. The full `CREATE POLICY`
statements are in `policies.sql` in this folder.

-- Row Level Security policies for Hummel Cloud (public + storage schemas).
-- Enable RLS on every table first, e.g.:  alter table public.notes enable row level security;

-- public.allowed_users
CREATE POLICY "allowlisted can read allowlist" ON public.allowed_users FOR SELECT TO authenticated USING (is_allowed());

-- public.profiles
CREATE POLICY "allowlisted read profiles" ON public.profiles FOR SELECT TO authenticated USING (is_allowed());
CREATE POLICY "insert own profile" ON public.profiles FOR INSERT TO authenticated WITH CHECK ((id = auth.uid()) AND can_write());
CREATE POLICY "update own profile" ON public.profiles FOR UPDATE TO authenticated USING (id = auth.uid()) WITH CHECK ((id = auth.uid()) AND can_write());

-- public.user_roles
CREATE POLICY "read own role" ON public.user_roles FOR SELECT TO authenticated USING (user_id = auth.uid());

-- public.favorites
CREATE POLICY "own favorites select" ON public.favorites FOR SELECT TO authenticated USING ((user_id = auth.uid()) AND is_allowed());
CREATE POLICY "own favorites insert" ON public.favorites FOR INSERT TO authenticated WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "own favorites delete" ON public.favorites FOR DELETE TO authenticated USING (user_id = auth.uid());

-- public.notebooks
CREATE POLICY "own notebooks select" ON public.notebooks FOR SELECT TO authenticated USING ((user_id = auth.uid()) AND is_allowed());
CREATE POLICY "own notebooks insert" ON public.notebooks FOR INSERT TO authenticated WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "own notebooks update" ON public.notebooks FOR UPDATE TO authenticated USING (user_id = auth.uid()) WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "own notebooks delete" ON public.notebooks FOR DELETE TO authenticated USING (user_id = auth.uid());

-- public.notes
CREATE POLICY "own notes select" ON public.notes FOR SELECT TO authenticated USING ((user_id = auth.uid()) AND is_allowed());
CREATE POLICY "own notes insert" ON public.notes FOR INSERT TO authenticated WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "own notes update" ON public.notes FOR UPDATE TO authenticated USING (user_id = auth.uid()) WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "own notes delete" ON public.notes FOR DELETE TO authenticated USING (user_id = auth.uid());

-- public.todos
CREATE POLICY "own todos select" ON public.todos FOR SELECT TO authenticated USING ((user_id = auth.uid()) AND is_allowed());
CREATE POLICY "own todos insert" ON public.todos FOR INSERT TO authenticated WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "own todos update" ON public.todos FOR UPDATE TO authenticated USING (user_id = auth.uid()) WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "own todos delete" ON public.todos FOR DELETE TO authenticated USING (user_id = auth.uid());

-- public.calendars
CREATE POLICY "calendars select" ON public.calendars FOR SELECT TO authenticated USING (is_allowed() AND ((user_id = auth.uid()) OR shared));
CREATE POLICY "calendars insert" ON public.calendars FOR INSERT TO authenticated WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "calendars update" ON public.calendars FOR UPDATE TO authenticated USING (user_id = auth.uid()) WITH CHECK ((user_id = auth.uid()) AND can_write());
CREATE POLICY "calendars delete" ON public.calendars FOR DELETE TO authenticated USING (user_id = auth.uid());

-- public.events (shared calendars are collaborative)
CREATE POLICY "events select" ON public.events FOR SELECT TO authenticated
  USING (is_allowed() AND ((user_id = auth.uid()) OR (EXISTS (SELECT 1 FROM calendars c WHERE c.id = events.calendar_id AND c.shared))));
CREATE POLICY "events insert" ON public.events FOR INSERT TO authenticated
  WITH CHECK (can_write() AND (user_id = auth.uid()) AND ((calendar_id IS NULL) OR (EXISTS (SELECT 1 FROM calendars c WHERE c.id = calendar_id AND ((c.user_id = auth.uid()) OR c.shared)))));
CREATE POLICY "events update" ON public.events FOR UPDATE TO authenticated
  USING ((user_id = auth.uid()) OR (EXISTS (SELECT 1 FROM calendars c WHERE c.id = events.calendar_id AND c.shared)))
  WITH CHECK (can_write());
CREATE POLICY "events delete" ON public.events FOR DELETE TO authenticated
  USING ((user_id = auth.uid()) OR (EXISTS (SELECT 1 FROM calendars c WHERE c.id = events.calendar_id AND c.shared)));

-- storage.objects (private 'documents' bucket; read-only Subscribers blocked from writing)
CREATE POLICY "allowlist select" ON storage.objects FOR SELECT TO authenticated USING ((bucket_id = 'documents') AND is_allowed());
CREATE POLICY "allowlist insert" ON storage.objects FOR INSERT TO authenticated WITH CHECK ((bucket_id = 'documents') AND can_write());
CREATE POLICY "allowlist update" ON storage.objects FOR UPDATE TO authenticated USING ((bucket_id = 'documents') AND can_write()) WITH CHECK ((bucket_id = 'documents') AND can_write());
CREATE POLICY "allowlist delete" ON storage.objects FOR DELETE TO authenticated USING ((bucket_id = 'documents') AND is_allowed());

-- Clerks are trusted as soon as they register: no organizer approval step.
-- New auth users get an active clerk row, and any account still waiting in
-- 'pending' from an earlier registration is activated once.

create or replace function public.handle_new_clerk()
returns trigger
language plpgsql
security definer
set search_path = public, pg_temp
as $$
begin
    insert into public.clerks (full_name, email, auth_user_id, status)
    values (
        coalesce(new.raw_user_meta_data ->> 'full_name', ''),
        new.email,
        new.id,
        'active'
    )
    on conflict (auth_user_id) do nothing;

    return new;
end;
$$;

alter table public.clerks alter column status set default 'active';

update public.clerks
   set status = 'active'
 where status = 'pending';

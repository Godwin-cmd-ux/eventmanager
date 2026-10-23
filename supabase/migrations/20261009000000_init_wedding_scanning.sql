-- =============================================================================
-- Wedding QR Code Scanning & Guest Verification System
-- Migration: 20261009000000_init_wedding_scanning.sql
--
-- Phase 1 deliverable. Safe to run more than once where practical; it never
-- deletes or overwrites existing application data.
--
-- Depends on:
--   * Supabase Auth (schema: auth, function: auth.uid(), table: auth.users)
--   * pgcrypto / core gen_random_uuid()
--
-- Application tables (exactly three):
--   public.invited_guests, public.clerks, public.scanner
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 0. Extensions / helpers
-- -----------------------------------------------------------------------------
create extension if not exists pgcrypto;

-- -----------------------------------------------------------------------------
-- 1. TABLE: invited_guests
-- -----------------------------------------------------------------------------
create table if not exists public.invited_guests (
    id               uuid primary key default gen_random_uuid(),
    full_name        text        not null,
    phone            text,
    invitation_type  text        not null,
    allowed_scans    integer     not null,
    successful_scans integer     not null default 0,
    status           text        not null default 'active',
    created_at       timestamptz not null default now(),

    constraint invited_guests_invitation_type_chk
        check (invitation_type in ('single', 'double')),

    -- Invitation type and scan limit must always agree:
    --   single => exactly 1 scan, double => exactly 2 scans.
    constraint invited_guests_type_limit_chk
        check (
            (invitation_type = 'single' and allowed_scans = 1) or
            (invitation_type = 'double' and allowed_scans = 2)
        ),

    constraint invited_guests_status_chk
        check (status in ('active', 'revoked')),

    -- Never negative, never above the invitation's own limit.
    constraint invited_guests_scans_range_chk
        check (successful_scans >= 0 and successful_scans <= allowed_scans)
);

create index if not exists invited_guests_status_idx
    on public.invited_guests (status);

-- -----------------------------------------------------------------------------
-- 2. TABLE: clerks
-- -----------------------------------------------------------------------------
create table if not exists public.clerks (
    id           uuid primary key default gen_random_uuid(),
    full_name    text        not null,
    email        text        not null,
    auth_user_id uuid        not null,
    status       text        not null default 'pending',
    created_at   timestamptz not null default now(),

    constraint clerks_status_chk
        check (status in ('active', 'pending', 'suspended')),

    -- One clerk record per Supabase Auth user.
    constraint clerks_auth_user_id_key unique (auth_user_id),

    constraint clerks_email_key unique (email),

    constraint clerks_auth_user_id_fkey
        foreign key (auth_user_id) references auth.users (id) on delete cascade
);

-- -----------------------------------------------------------------------------
-- 3. TABLE: scanner
-- -----------------------------------------------------------------------------
create table if not exists public.scanner (
    id             uuid primary key default gen_random_uuid(),
    guest_id       uuid,
    clerk_id       uuid        not null,
    scan_result    text        not null,
    failure_reason text,
    scanned_at     timestamptz not null default now(),

    constraint scanner_scan_result_chk
        check (scan_result in ('success', 'failed')),

    constraint scanner_failure_reason_chk
        check (
            failure_reason is null or
            failure_reason in (
                'invalid_qr',
                'invitation_revoked',
                'scan_limit_reached',
                'clerk_not_authorized'
            )
        ),

    -- success must not carry a failure reason; failed must always carry one.
    constraint scanner_result_reason_chk
        check (
            (scan_result = 'success' and failure_reason is null) or
            (scan_result = 'failed'  and failure_reason is not null)
        ),

    -- guest_id is null when the scanned identifier is not a known guest.
    constraint scanner_guest_id_fkey
        foreign key (guest_id) references public.invited_guests (id) on delete set null,

    constraint scanner_clerk_id_fkey
        foreign key (clerk_id) references public.clerks (id) on delete restrict
);

create index if not exists scanner_clerk_id_scanned_at_idx
    on public.scanner (clerk_id, scanned_at desc);

create index if not exists scanner_guest_id_idx
    on public.scanner (guest_id);

create index if not exists scanner_scanned_at_idx
    on public.scanner (scanned_at desc);

-- -----------------------------------------------------------------------------
-- 4. Automatic pending-clerk record on Supabase Auth sign-up
-- -----------------------------------------------------------------------------
-- Registration is separated from authorization: every new auth user gets a
-- clerk row in 'pending'. Only a trusted operator (SQL editor / service role)
-- may later flip status to 'active'. Users can never self-approve through RLS.
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
        'pending'
    )
    on conflict (auth_user_id) do nothing;

    return new;
end;
$$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
    after insert on auth.users
    for each row execute function public.handle_new_clerk();

-- -----------------------------------------------------------------------------
-- 5. ATOMIC SCAN VERIFICATION FUNCTION
-- -----------------------------------------------------------------------------
-- Single source of truth for consuming scans. Runs as SECURITY DEFINER so it
-- may write despite RLS; authorization is enforced inside the function.
-- A row lock (FOR UPDATE) serializes concurrent scans of the same invitation,
-- guaranteeing the scan limit can never be exceeded.
create or replace function public.verify_guest_scan(p_guest_id uuid)
returns jsonb
language plpgsql
security definer
set search_path = public, pg_temp
as $$
declare
    v_clerk_id     uuid;
    v_clerk_status text;
    v_guest        public.invited_guests%rowtype;
    v_remaining    integer;
    v_now          timestamptz := now();
begin
    -- 1. Authenticate the caller via the JWT and resolve their clerk record.
    select id, status
      into v_clerk_id, v_clerk_status
      from public.clerks
     where auth_user_id = auth.uid()
     limit 1;

    -- No clerk record at all: cannot attribute a scan row (clerk_id is NOT NULL).
    if v_clerk_id is null then
        return jsonb_build_object(
            'result', 'failed',
            'failure_reason', 'clerk_not_authorized',
            'guest', null
        );
    end if;

    -- Pending / suspended clerks are authenticated but not authorized.
    if v_clerk_status <> 'active' then
        insert into public.scanner (guest_id, clerk_id, scan_result, failure_reason, scanned_at)
        values (null, v_clerk_id, 'failed', 'clerk_not_authorized', v_now);

        return jsonb_build_object(
            'result', 'failed',
            'failure_reason', 'clerk_not_authorized',
            'guest', null
        );
    end if;

    -- 2. Validate payload shape.
    if p_guest_id is null then
        insert into public.scanner (guest_id, clerk_id, scan_result, failure_reason, scanned_at)
        values (null, v_clerk_id, 'failed', 'invalid_qr', v_now);

        return jsonb_build_object(
            'result', 'failed',
            'failure_reason', 'invalid_qr',
            'guest', null
        );
    end if;

    -- 3. Lock the guest row for the remainder of the transaction.
    select *
      into v_guest
      from public.invited_guests
     where id = p_guest_id
     for update;

    if not found then
        insert into public.scanner (guest_id, clerk_id, scan_result, failure_reason, scanned_at)
        values (null, v_clerk_id, 'failed', 'invalid_qr', v_now);

        return jsonb_build_object(
            'result', 'failed',
            'failure_reason', 'invalid_qr',
            'guest', null
        );
    end if;

    -- 4. Revoked invitation.
    if v_guest.status <> 'active' then
        insert into public.scanner (guest_id, clerk_id, scan_result, failure_reason, scanned_at)
        values (v_guest.id, v_clerk_id, 'failed', 'invitation_revoked', v_now);

        return jsonb_build_object(
            'result', 'failed',
            'failure_reason', 'invitation_revoked',
            'guest', jsonb_build_object(
                'id', v_guest.id,
                'full_name', v_guest.full_name,
                'invitation_type', v_guest.invitation_type,
                'successful_scans', v_guest.successful_scans,
                'allowed_scans', v_guest.allowed_scans,
                'remaining_scans', v_guest.allowed_scans - v_guest.successful_scans
            )
        );
    end if;

    -- 5. Allowance exhausted.
    if v_guest.successful_scans >= v_guest.allowed_scans then
        insert into public.scanner (guest_id, clerk_id, scan_result, failure_reason, scanned_at)
        values (v_guest.id, v_clerk_id, 'failed', 'scan_limit_reached', v_now);

        return jsonb_build_object(
            'result', 'failed',
            'failure_reason', 'scan_limit_reached',
            'guest', jsonb_build_object(
                'id', v_guest.id,
                'full_name', v_guest.full_name,
                'invitation_type', v_guest.invitation_type,
                'successful_scans', v_guest.successful_scans,
                'allowed_scans', v_guest.allowed_scans,
                'remaining_scans', 0
            )
        );
    end if;

    -- 6. Eligible: consume exactly one scan and record it in the same tx.
    update public.invited_guests
       set successful_scans = successful_scans + 1
     where id = v_guest.id
     returning successful_scans into v_remaining;

    insert into public.scanner (guest_id, clerk_id, scan_result, failure_reason, scanned_at)
    values (v_guest.id, v_clerk_id, 'success', null, v_now);

    return jsonb_build_object(
        'result', 'success',
        'failure_reason', null,
        'guest', jsonb_build_object(
            'id', v_guest.id,
            'full_name', v_guest.full_name,
            'invitation_type', v_guest.invitation_type,
            'successful_scans', v_remaining,
            'allowed_scans', v_guest.allowed_scans,
            'remaining_scans', v_guest.allowed_scans - v_remaining,
            'scanned_at', v_now
        )
    );
end;
$$;

-- -----------------------------------------------------------------------------
-- 6. ROW LEVEL SECURITY
-- -----------------------------------------------------------------------------
alter table public.invited_guests enable row level security;
alter table public.clerks         enable row level security;
alter table public.scanner        enable row level security;

-- invited_guests: no policies at all. Nobody (anon or authenticated) may read
-- or write the guest list directly. Only the SECURITY DEFINER function and
-- privileged server-side roles can access it.

-- clerks: an authenticated clerk may read only their own record.
drop policy if exists clerks_select_own on public.clerks;
create policy clerks_select_own
    on public.clerks
    for select
    to authenticated
    using (auth_user_id = auth.uid());

-- No INSERT policy: clerk rows are created exclusively by the auth trigger.
-- No UPDATE/DELETE policy: clerks can never change their own status.

-- scanner: an authenticated clerk may read only their own scan rows.
drop policy if exists scanner_select_own on public.scanner;
create policy scanner_select_own
    on public.scanner
    for select
    to authenticated
    using (
        clerk_id = (
            select c.id
              from public.clerks c
             where c.auth_user_id = auth.uid()
        )
    );

-- No INSERT/UPDATE/DELETE policies: scan rows are written only by the
-- SECURITY DEFINER function. Clerks cannot fabricate or edit scans.

-- -----------------------------------------------------------------------------
-- 7. PRIVILEGES
-- -----------------------------------------------------------------------------
-- Defense in depth on top of RLS: remove direct write access to the tables
-- from browser-facing roles. The verification function runs as its owner.
revoke all on public.invited_guests from anon, authenticated;
revoke all on public.scanner        from anon, authenticated;

revoke insert, update, delete on public.clerks from anon, authenticated;
grant select on public.clerks to authenticated;

-- Only authenticated (and checked-in-function) users may call the verifier.
revoke all on function public.verify_guest_scan(uuid) from public, anon;
grant execute on function public.verify_guest_scan(uuid) to authenticated;

revoke all on function public.handle_new_clerk() from public, anon, authenticated;

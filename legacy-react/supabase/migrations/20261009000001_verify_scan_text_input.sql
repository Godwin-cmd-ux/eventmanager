-- =============================================================================
-- Follow-up migration: verify_guest_scan accepts text input.
--
-- Why: QR payloads are arbitrary strings. The original function took a uuid
-- argument, so a malformed payload caused a PostgREST cast error instead of a
-- recorded 'invalid_qr' failure. This version validates the payload inside the
-- function and records EVERY attempt (including malformed codes) in scanner.
--
-- Safe to run after 20261009000000_init_wedding_scanning.sql. Does not alter
-- tables, only replaces the function and its grants.
-- =============================================================================

-- Remove the old uuid-overload so only one function remains.
drop function if exists public.verify_guest_scan(uuid);

-- -----------------------------------------------------------------------------
-- ATOMIC SCAN VERIFICATION (text input)
-- -----------------------------------------------------------------------------
create or replace function public.verify_guest_scan(p_guest_id text)
returns jsonb
language plpgsql
security definer
set search_path = public, pg_temp
as $$
declare
    v_clerk_id     uuid;
    v_clerk_status text;
    v_guest_uuid   uuid;
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

    -- 2. Validate payload shape (empty or not a UUID => invalid QR).
    begin
        if p_guest_id is null or btrim(p_guest_id) = '' then
            raise invalid_text_representation;
        end if;
        v_guest_uuid := lower(btrim(p_guest_id))::uuid;
    exception
        when invalid_text_representation then
            insert into public.scanner (guest_id, clerk_id, scan_result, failure_reason, scanned_at)
            values (null, v_clerk_id, 'failed', 'invalid_qr', v_now);

            return jsonb_build_object(
                'result', 'failed',
                'failure_reason', 'invalid_qr',
                'guest', null
            );
    end;

    -- 3. Lock the guest row for the remainder of the transaction.
    select *
      into v_guest
      from public.invited_guests
     where id = v_guest_uuid
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
                'remaining_scans', 0
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
-- Grants for the new signature
-- -----------------------------------------------------------------------------
revoke execute on function public.verify_guest_scan(text) from public, anon;
grant execute on function public.verify_guest_scan(text) to authenticated;
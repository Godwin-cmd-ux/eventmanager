<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase connection (GoTrue + PostgREST over HTTPS)
    |--------------------------------------------------------------------------
    | All values are read from environment variables and never leave the
    | server. The anon key is technically public, but we keep it server-side
    | because the entire browser client talks to Laravel, not to Supabase.
    */
    'url' => env('SUPABASE_URL', 'https://<project-ref>.supabase.co'),
    'anon_key' => env('SUPABASE_ANON_KEY', ''),
    'secret_key' => env('SUPABASE_SECRET_KEY', ''),
];

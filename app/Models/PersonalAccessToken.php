<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Vendor mobile-app API tokens.
 *
 * These are pinned to the central platform connection (`saleprosaas_landlord`)
 * so they are consistent across tenancy switches. A token is created at login
 * (which runs in tenant context) and verified on every subsequent request — if
 * the token lived on the tenancy-switched default connection, creation and
 * verification could hit different databases (the "personal_access_tokens
 * doesn't exist" mismatch). Storing them centrally removes that ambiguity.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'saleprosaas_landlord';
}

<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    use HasRoles;

    /**
     * Always use the 'tenant' DB connection when inside a tenancy context.
     * This prevents the auth guard from querying the landlord DB for the
     * users table (which doesn't exist there) when tenancy is initialized.
     */
    public function getConnectionName()
    {
        try {
            if (config('database.connections.saleprosaas_landlord') && tenant()) {
                return 'tenant';
            }
        } catch (\Throwable $e) {
            // Not in tenant context — use default
        }
        return parent::getConnectionName();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        "phone",
        "company_name",
        "role_id",
        "biller_id",
        "warehouse_id",
        "account_id",
        "kitchen_id",
        "service_staff",
        "is_active",
        "is_deleted"
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Keep Spatie's model_has_roles pivot in sync with users.role_id.
     * The app assigns roles via the role_id column, but Blade @can/@canany
     * resolve user permissions through the pivot — without it they fail.
     */
    protected static function booted()
    {
        static::saved(function (self $user) {
            if (! $user->role_id) {
                return;
            }
            if (! $user->wasRecentlyCreated && ! $user->wasChanged('role_id')) {
                return;
            }
            try {
                $db = \Illuminate\Support\Facades\DB::connection($user->getConnectionName());
                $db->table('model_has_roles')
                    ->where('model_type', self::class)
                    ->where('model_id', $user->id)
                    ->delete();
                $db->table('model_has_roles')->insertOrIgnore([
                    'role_id'    => $user->role_id,
                    'model_type' => self::class,
                    'model_id'   => $user->id,
                ]);
            } catch (\Throwable $e) {
                // Pivot table missing (pre-migration) — role-level checks still work
            }
        });
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function holiday() {
        return $this->hasMany('App\Models\Holiday');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

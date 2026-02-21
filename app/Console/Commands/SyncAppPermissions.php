<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Config;

class SyncAppPermissions extends Command
{
    protected $signature = 'permission:sync';
    protected $description = 'Sync permissions from config/app-permissions.php to database';

    //php artisan permission:sync

    public function handle()
    {
        $this->info('Syncing permissions from config...');

        $permissions = Config::get('app-permissions.permissions');

        foreach ($permissions as $permissionKey => $permissionLabel) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionKey],
                ['guard_name' => 'web']
            );

            $this->info("✓ Created/Updated: {$permissionLabel} ({$permissionKey})");
        }

        $this->info('All permissions synced successfully!');
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantAllPermissions extends Command
{
    protected $signature = 'permissions:grant-all {user_id? : User ID or leave empty for all users}';
    protected $description = 'Grant all permissions to user(s)';

    public function handle()
    {
        // Get all permissions
        $permissions = Permission::all();

        if ($this->argument('user_id')) {
            // Grant to specific user
            $user = User::find($this->argument('user_id'));
            if (!$user) {
                $this->error('User not found!');
                return 1;
            }

            $user->givePermissionTo($permissions);
            $this->line("✓ Granted " . count($permissions) . " permissions to user {$user->id} ({$user->email})");
        } else {
            // Grant to all users
            $users = User::all();
            foreach ($users as $user) {
                $user->givePermissionTo($permissions);
                $this->line("✓ Granted permissions to {$user->email}");
            }
            $this->info("✓ Done! Granted permissions to all users!");
        }

        // Clear cache
        \Artisan::call('cache:clear');
        $this->line("✓ Cache cleared");

        return 0;
    }
}

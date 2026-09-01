<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the application's super admin account.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::updateOrCreate(
                ['email' => 'admin@gmail.com'],
                [
                    'name' => 'Super Admin',
                    'password' => '123456',
                    'email_verified_at' => now(),
                ],
            );

            $team = $user->personalTeam() ?? Team::create([
                'name' => "Super Admin's Team",
                'slug' => $this->uniqueTeamSlug("Super Admin's Team"),
                'is_personal' => true,
            ]);

            $team->memberships()->updateOrCreate(
                ['user_id' => $user->id],
                ['role' => TeamRole::Owner],
            );

            $user->switchTeam($team);
        });
    }

    private function uniqueTeamSlug(string $name): string
    {
        $slug = Str::slug($name);
        $candidate = $slug;
        $suffix = 1;

        while (Team::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$suffix++;
        }

        return $candidate;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $activeTeamIds = DB::table('teams')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->pluck('id');

        $staleMemberships = DB::table('team_members')
            ->where('status', 'active');

        if ($activeTeamIds->isEmpty()) {
            $staleMemberships->update(['status' => 'withdrawn']);
        } else {
            $staleMemberships
                ->whereNotIn('team_id', $activeTeamIds)
                ->update(['status' => 'withdrawn']);
        }

        $duplicateUserIds = DB::table('team_members')
            ->where('status', 'active')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        if ($duplicateUserIds->isNotEmpty()) {
            throw new RuntimeException(
                'Single-team migration stopped: users with multiple active memberships: '.
                $duplicateUserIds->implode(', ')
            );
        }

        Schema::table('team_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('active_user_id')->nullable()->after('user_id');
        });

        DB::table('team_members')
            ->where('status', 'active')
            ->update(['active_user_id' => DB::raw('user_id')]);

        Schema::table('team_members', function (Blueprint $table): void {
            $table->unique('active_user_id', 'team_members_one_active_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table): void {
            $table->dropUnique('team_members_one_active_user_unique');
            $table->dropColumn('active_user_id');
        });
    }
};

<?php

namespace Tests\Feature;

use App\Models\FeedPost;
use App\Services\Teams\TeamParticipationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTeamDomain;
use Tests\TestCase;

class TeamParticipationTest extends TestCase
{
    use CreatesTeamDomain;
    use RefreshDatabase;

    public function test_points_are_team_specific_and_badges_follow_thresholds(): void
    {
        $member = $this->user('member');
        $first = $this->team(null, [[$member]]);
        $second = $this->team(null, [[$member]]);
        $service = app(TeamParticipationService::class);

        $service->award($first, $member, 'team_post_created', 'first', 700);
        $service->award($second, $member, 'team_post_created', 'second', 5000);

        $this->assertDatabaseHas('team_participations', ['team_id' => $first->id, 'user_id' => $member->id, 'points_total' => 700, 'badge_key' => 'growing_participation']);
        $this->assertDatabaseHas('team_participations', ['team_id' => $second->id, 'user_id' => $member->id, 'points_total' => 5000, 'badge_key' => 'outstanding_participation']);
        $this->assertSame('top_participation', $service->badgeFor(1500));
    }

    public function test_self_reactions_and_reaction_toggles_cannot_be_farmed(): void
    {
        $author = $this->user('author');
        $reactor = $this->user('reactor');
        $team = $this->team($author, [[$reactor]]);
        $post = FeedPost::query()->create(['user_id' => $author->id, 'team_id' => $team->id, 'body' => 'Team post', 'visibility' => 'team', 'status' => 'published']);

        $post->reactions()->create(['user_id' => $author->id, 'type' => 'like']);
        $reaction = $post->reactions()->create(['user_id' => $reactor->id, 'type' => 'like']);
        $reaction->delete();
        $post->reactions()->create(['user_id' => $reactor->id, 'type' => 'love']);

        $this->assertDatabaseCount('team_participation_events', 2);
        $this->assertDatabaseHas('team_participations', ['team_id' => $team->id, 'user_id' => $author->id, 'points_total' => 23]);
    }

    public function test_post_daily_limit_is_enforced_and_updates_do_not_score(): void
    {
        config()->set('team_progression.participation.activities.team_post_created.daily_limit', 1);
        $member = $this->user('member');
        $team = $this->team(null, [[$member]]);
        $service = app(TeamParticipationService::class);

        $service->award($team, $member, 'team_post_created', 'post-1');
        $service->award($team, $member, 'team_post_created', 'post-2');

        $this->assertDatabaseHas('team_participations', ['team_id' => $team->id, 'user_id' => $member->id, 'points_total' => 20]);
        $this->assertDatabaseCount('team_participation_events', 1);
    }
}

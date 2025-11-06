<?php

use Ritechoice23\Followable\Tests\Models\Organization;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user1 = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $this->user2 = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    $this->user3 = User::create(['name' => 'Charlie', 'email' => 'charlie@example.com']);
    $this->team1 = Team::create(['name' => 'Team Alpha']);
    $this->team2 = Team::create(['name' => 'Team Beta']);
    $this->team3 = Team::create(['name' => 'Team Gamma']);
    $this->org = Organization::create(['name' => 'Acme Corp']);
});

// Mutual Followers Tests (HasFollowers trait)

test('mutualFollowers returns followers shared between two models', function () {
    // User1 and User2 both follow Team1
    $this->user1->follow($this->team1);
    $this->user2->follow($this->team1);

    // User1 and User3 both follow Team2
    $this->user1->follow($this->team2);
    $this->user3->follow($this->team2);

    // Only User2 follows Team3
    $this->user2->follow($this->team3);

    // Team1 and Team2 share User1 as a mutual follower
    $mutualFollowers = $this->team1->mutualFollowers($this->team2, User::class);

    expect($mutualFollowers)->toHaveCount(1)
        ->and($mutualFollowers->first()->id)->toBe($this->user1->id);
});

test('mutualFollowers returns empty collection when no mutual followers', function () {
    $this->user1->follow($this->team1);
    $this->user2->follow($this->team2);

    $mutualFollowers = $this->team1->mutualFollowers($this->team2, User::class);

    expect($mutualFollowers)->toBeEmpty();
});

test('mutualFollowers works with multiple mutual followers', function () {
    $this->user1->follow($this->team1);
    $this->user1->follow($this->team2);

    $this->user2->follow($this->team1);
    $this->user2->follow($this->team2);

    $this->user3->follow($this->team1);
    $this->user3->follow($this->team2);

    $mutualFollowers = $this->team1->mutualFollowers($this->team2, User::class);

    expect($mutualFollowers)->toHaveCount(3)
        ->and($mutualFollowers->pluck('id')->sort()->values()->all())
        ->toBe([$this->user1->id, $this->user2->id, $this->user3->id]);
});

test('mutualFollowers works without specifying type', function () {
    $this->user1->follow($this->team1);
    $this->user1->follow($this->team2);

    $this->org->follow($this->team1);
    $this->org->follow($this->team2);

    $mutualFollowers = $this->team1->mutualFollowers($this->team2);

    expect($mutualFollowers)->toHaveCount(2);
});

// Mutual Followings Tests (CanFollow trait)

test('mutualFollowings returns followings shared between two models', function () {
    // User1 and User2 both follow Team1
    $this->user1->follow($this->team1);
    $this->user2->follow($this->team1);

    // User1 and User2 both follow Team2
    $this->user1->follow($this->team2);
    $this->user2->follow($this->team2);

    // Only User1 follows Team3
    $this->user1->follow($this->team3);

    // User1 and User2 share Team1 and Team2 as mutual followings
    $mutualFollowings = $this->user1->mutualFollowings($this->user2, Team::class);

    expect($mutualFollowings)->toHaveCount(2)
        ->and($mutualFollowings->pluck('id')->sort()->values()->all())
        ->toBe([$this->team1->id, $this->team2->id]);
});

test('mutualFollowings returns empty collection when no mutual followings', function () {
    $this->user1->follow($this->team1);
    $this->user2->follow($this->team2);

    $mutualFollowings = $this->user1->mutualFollowings($this->user2, Team::class);

    expect($mutualFollowings)->toBeEmpty();
});

test('mutualFollowings works with mixed types', function () {
    $this->user1->follow($this->team1);
    $this->user1->follow($this->org);

    $this->user2->follow($this->team1);
    $this->user2->follow($this->org);

    $mutualFollowings = $this->user1->mutualFollowings($this->user2);

    expect($mutualFollowings)->toHaveCount(2);
});

// Mutual Followers (Bidirectional) Tests

test('isMutualFollow returns true when both follow each other', function () {
    $this->user1->follow($this->user2);
    $this->user2->follow($this->user1);

    expect($this->user1->isMutualFollow($this->user2))->toBeTrue()
        ->and($this->user2->isMutualFollow($this->user1))->toBeTrue();
});

test('isMutualFollow returns false when only one follows', function () {
    $this->user1->follow($this->user2);

    expect($this->user1->isMutualFollow($this->user2))->toBeFalse()
        ->and($this->user2->isMutualFollow($this->user1))->toBeFalse();
});

test('isMutualFollow returns false when neither follows', function () {
    expect($this->user1->isMutualFollow($this->user2))->toBeFalse();
});

test('mutualConnections returns users who follow each other', function () {
    // User1 and User2 follow each other (mutual)
    $this->user1->follow($this->user2);
    $this->user2->follow($this->user1);

    // User1 and User3 follow each other (mutual)
    $this->user1->follow($this->user3);
    $this->user3->follow($this->user1);

    // User2 follows User3 but not vice versa (not mutual)
    $this->user2->follow($this->user3);

    $mutualConnections = $this->user1->mutualConnections(User::class);

    expect($mutualConnections)->toHaveCount(2)
        ->and($mutualConnections->pluck('id')->sort()->values()->all())
        ->toBe([$this->user2->id, $this->user3->id]);
});

test('mutualConnections returns empty when no mutual relationships', function () {
    $this->user1->follow($this->user2);
    $this->user1->follow($this->user3);

    $mutualConnections = $this->user1->mutualConnections(User::class);

    expect($mutualConnections)->toBeEmpty();
});

test('mutualConnections works with teams following each other', function () {
    $this->team1->follow($this->team2);
    $this->team2->follow($this->team1);

    $this->team1->follow($this->team3);
    $this->team3->follow($this->team1);

    $mutualConnections = $this->team1->mutualConnections(Team::class);

    expect($mutualConnections)->toHaveCount(2)
        ->and($mutualConnections->pluck('id')->sort()->values()->all())
        ->toBe([$this->team2->id, $this->team3->id]);
});

test('mutual methods handle empty relationships gracefully', function () {
    $mutualFollowers1 = $this->team1->mutualFollowers($this->team2, User::class);
    $mutualFollowings1 = $this->user1->mutualFollowings($this->user2, Team::class);
    $mutualConnections = $this->user1->mutualConnections(User::class);

    expect($mutualFollowers1)->toBeEmpty()
        ->and($mutualFollowings1)->toBeEmpty()
        ->and($mutualConnections)->toBeEmpty();
});

test('mutualFollowings with specific type filters correctly', function () {
    $this->user1->follow($this->team1);
    $this->user1->follow($this->team2);
    $this->user1->follow($this->org);

    $this->user2->follow($this->team1);
    $this->user2->follow($this->team2);
    $this->user2->follow($this->org);

    // Only get Team mutual followings
    $mutualTeams = $this->user1->mutualFollowings($this->user2, Team::class);

    expect($mutualTeams)->toHaveCount(2)
        ->and($mutualTeams->first())->toBeInstanceOf(Team::class);

    // Only get Organization mutual followings
    $mutualOrgs = $this->user1->mutualFollowings($this->user2, Organization::class);

    expect($mutualOrgs)->toHaveCount(1)
        ->and($mutualOrgs->first())->toBeInstanceOf(Organization::class);
});

test('mutualFollowers with specific type filters correctly', function () {
    $this->user1->follow($this->team1);
    $this->user2->follow($this->team1);
    $this->user3->follow($this->team1);

    $this->org->follow($this->team1);
    $this->org->follow($this->team2);

    $this->user1->follow($this->team2);

    // Only get User mutual followers
    $mutualUsers = $this->team1->mutualFollowers($this->team2, User::class);

    expect($mutualUsers)->toHaveCount(1)
        ->and($mutualUsers->first())->toBeInstanceOf(User::class)
        ->and($mutualUsers->first()->id)->toBe($this->user1->id);
});

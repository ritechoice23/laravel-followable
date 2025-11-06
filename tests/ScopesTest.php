<?php

use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $this->user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
    $this->team = Team::create(['name' => 'Team Alpha']);
    $this->team2 = Team::create(['name' => 'Team Beta']);
});

test('whereFollowings scope returns users following a team', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);

    $users = User::whereFollowings($this->team)->get();

    expect($users)->toHaveCount(2)
        ->and($users->pluck('id')->toArray())->toContain($this->user1->id, $this->user2->id);
});

test('whereFollowings scope returns empty when no users follow', function () {
    $users = User::whereFollowings($this->team)->get();

    expect($users)->toHaveCount(0);
});

test('whereFollowings scope can be chained with other queries', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);

    $users = User::whereFollowings($this->team)
        ->where('name', 'John Doe')
        ->get();

    expect($users)->toHaveCount(1)
        ->and($users->first()->id)->toBe($this->user1->id);
});

test('whereFollowers scope returns teams followed by a user', function () {
    $this->user1->follow($this->team);
    $this->user1->follow($this->team2);

    $teams = Team::whereFollowers($this->user1)->get();

    expect($teams)->toHaveCount(2)
        ->and($teams->pluck('id')->toArray())->toContain($this->team->id, $this->team2->id);
});

test('whereFollowers scope returns empty when user follows no teams', function () {
    $teams = Team::whereFollowers($this->user1)->get();

    expect($teams)->toHaveCount(0);
});

test('whereFollowers scope can be chained with other queries', function () {
    $this->user1->follow($this->team);
    $this->user1->follow($this->team2);

    $teams = Team::whereFollowers($this->user1)
        ->where('name', 'Team Alpha')
        ->get();

    expect($teams)->toHaveCount(1)
        ->and($teams->first()->id)->toBe($this->team->id);
});

test('scopes work with polymorphic relationships', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->user3);

    $usersFollowingTeam = User::whereFollowings($this->team)->get();
    expect($usersFollowingTeam)->toHaveCount(1);

    $usersFollowingUser = User::whereFollowings($this->user3)->get();
    expect($usersFollowingUser)->toHaveCount(1);

    $teamsFollowedByUser = Team::whereFollowers($this->user1)->get();
    expect($teamsFollowedByUser)->toHaveCount(1);
});

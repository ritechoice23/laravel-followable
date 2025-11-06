<?php

use Ritechoice23\Followable\Models\Follow;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->team = Team::create(['name' => 'Team Alpha']);
});

test('followingRecords returns morphMany relationship', function () {
    $this->user->follow($this->team);

    $follows = $this->user->followingRecords;

    expect($follows)->toHaveCount(1)
        ->and($follows->first())->toBeInstanceOf(Follow::class);
});

test('followRecords returns morphMany relationship of Follow models', function () {
    $this->user->follow($this->team);

    $followRecords = $this->team->followRecords;

    expect($followRecords)->toHaveCount(1)
        ->and($followRecords->first())->toBeInstanceOf(Follow::class);
});

test('follow model has follower relationship', function () {
    $this->user->follow($this->team);

    $follow = Follow::first();

    expect($follow->follower)->toBeInstanceOf(User::class)
        ->and($follow->follower->id)->toBe($this->user->id);
});

test('follow model has followable relationship', function () {
    $this->user->follow($this->team);

    $follow = Follow::first();

    expect($follow->followable)->toBeInstanceOf(Team::class)
        ->and($follow->followable->id)->toBe($this->team->id);
});

test('eager loading works correctly on followRecords', function () {
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $team2 = Team::create(['name' => 'Team Beta']);

    $this->user->follow($this->team);
    $user2->follow($team2);

    $follows = Follow::with(['follower', 'followable'])->get();

    expect($follows)->toHaveCount(2)
        ->and($follows->first()->relationLoaded('follower'))->toBeTrue()
        ->and($follows->first()->relationLoaded('followable'))->toBeTrue();
});

<?php

use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->team = Team::create(['name' => 'Team Alpha']);
});

test('follow accepts model instance', function () {
    $result = $this->user->follow($this->team);

    expect($result)->toBeTrue()
        ->and($this->user->isFollowing($this->team))->toBeTrue();
});

test('isFollowing accepts model instance', function () {
    $this->user->follow($this->team);

    expect($this->user->isFollowing($this->team))->toBeTrue();
});

test('unfollow accepts model instance', function () {
    $this->user->follow($this->team);

    $result = $this->user->unfollow($this->team);

    expect($result)->toBeTrue()
        ->and($this->user->isFollowing($this->team))->toBeFalse();
});

test('isFollowedBy accepts model instance', function () {
    $this->user->follow($this->team);

    expect($this->team->isFollowedBy($this->user))->toBeTrue();
});

test('whereFollowings scope accepts model instance', function () {
    $this->user->follow($this->team);

    $users = User::whereFollowings($this->team)->get();

    expect($users)->toHaveCount(1)
        ->and($users->first()->id)->toBe($this->user->id);
});

test('whereFollowers scope accepts model instance', function () {
    $this->user->follow($this->team);

    $teams = Team::whereFollowers($this->user)->get();

    expect($teams)->toHaveCount(1)
        ->and($teams->first()->id)->toBe($this->team->id);
});

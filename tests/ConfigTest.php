<?php

use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
});

test('self follow is prevented by default', function () {
    $result = $this->user->follow($this->user);

    expect($result)->toBeFalse()
        ->and($this->user->isFollowing($this->user))->toBeFalse();
});

test('self follow is allowed when config permits', function () {
    config(['follow.allow_self_follow' => true]);

    $result = $this->user->follow($this->user);

    expect($result)->toBeTrue()
        ->and($this->user->isFollowing($this->user))->toBeTrue();
});

test('custom table name is used when configured', function () {
    config(['follow.table_name' => 'custom_follows']);

    // Verify the Follow model uses the configured table name
    $follow = new \Ritechoice23\Followable\Models\Follow;
    expect($follow->getTable())->toBe('custom_follows');

    // Reset to default for other tests
    config(['follow.table_name' => 'follows']);
});

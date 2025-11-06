<?php

use Ritechoice23\Followable\Tests\Models\Organization;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->team1 = Team::create(['name' => 'Team Alpha']);
    $this->team2 = Team::create(['name' => 'Team Beta']);
    $this->team3 = Team::create(['name' => 'Team Gamma']);
    $this->org = Organization::create(['name' => 'Acme Corp']);
});

test('followings returns actual followable models not Follow models', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);

    $followings = $this->user->followings()->get();

    expect($followings)->toHaveCount(2)
        ->and($followings->first())->toBeInstanceOf(Team::class)
        ->and($followings->pluck('id')->sort()->values()->all())->toBe([$this->team1->id, $this->team2->id]);
});

test('followings returns empty collection when not following anyone', function () {
    $followings = $this->user->followings()->get();

    expect($followings)->toBeEmpty();
});

test('followings can be paginated', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->team3);

    $paginated = $this->user->followings()->paginate(2);

    expect($paginated)->toHaveCount(2)
        ->and($paginated->total())->toBe(3)
        ->and($paginated->lastPage())->toBe(2);
});

test('followings can be filtered with where clauses', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);

    $followings = $this->user->followings()
        ->where('name', 'Team Alpha')
        ->get();

    expect($followings)->toHaveCount(1)
        ->and($followings->first()->id)->toBe($this->team1->id);
});

test('followings can be ordered', function () {
    $this->user->follow($this->team2);
    $this->user->follow($this->team1);

    $followings = $this->user->followings()
        ->orderBy('name', 'asc')
        ->get();

    expect($followings->first()->name)->toBe('Team Alpha')
        ->and($followings->last()->name)->toBe('Team Beta');
});

test('followings with single type returns correct models', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);

    $followings = $this->user->followings(Team::class)->get();

    expect($followings)->toHaveCount(2)
        ->and($followings->first())->toBeInstanceOf(Team::class);
});

test('followingsPaginated returns paginated results', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->team3);

    $paginated = $this->user->followingsPaginated(2);

    expect($paginated->total())->toBe(3)
        ->and($paginated->perPage())->toBe(2)
        ->and($paginated)->toHaveCount(2);
});

test('followingsPaginated with custom per page', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->team3);

    $paginated = $this->user->followingsPaginated(10);

    expect($paginated->perPage())->toBe(10)
        ->and($paginated)->toHaveCount(3);
});

test('followingsPaginated can filter by type', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->org);

    $paginated = $this->user->followingsPaginated(10, Team::class);

    expect($paginated->total())->toBe(1)
        ->and($paginated->first())->toBeInstanceOf(Team::class);
});

test('followingsOfType returns only specified type', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->org);

    $followings = $this->user->followingsOfType(Team::class)->get();

    expect($followings)->toHaveCount(1)
        ->and($followings->first())->toBeInstanceOf(Team::class);
});

test('followingsOfType with array of types works via followingsGrouped', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->org);

    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $this->user->follow($user2);

    // For multiple types, use followingsGrouped() which is the recommended approach
    $grouped = $this->user->followingsGrouped();

    expect($grouped->has(Team::class))->toBeTrue()
        ->and($grouped->has(Organization::class))->toBeTrue()
        ->and($grouped->has(User::class))->toBeTrue()
        ->and($grouped->get(Team::class))->toHaveCount(2)
        ->and($grouped->get(Organization::class))->toHaveCount(1)
        ->and($grouped->get(User::class))->toHaveCount(1);

    // Total count across all types
    $totalCount = $grouped->flatten()->count();
    expect($totalCount)->toBe(4);
});

test('followingsOfType with single type in array works', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->org);

    $followings = $this->user->followingsOfType([Team::class])->get();

    expect($followings)->toHaveCount(1)
        ->and($followings->first())->toBeInstanceOf(Team::class);
});

test('followingsGrouped returns collection grouped by type', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->org);

    $grouped = $this->user->followingsGrouped();

    expect($grouped)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($grouped->has(Team::class))->toBeTrue()
        ->and($grouped->has(Organization::class))->toBeTrue()
        ->and($grouped->get(Team::class))->toHaveCount(2)
        ->and($grouped->get(Organization::class))->toHaveCount(1);
});

test('followingsGrouped returns empty collection when not following anyone', function () {
    $grouped = $this->user->followingsGrouped();

    expect($grouped)->toBeEmpty();
});

test('followingsGrouped models are actual instances', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->org);

    $grouped = $this->user->followingsGrouped();

    expect($grouped->get(Team::class)->first())->toBeInstanceOf(Team::class)
        ->and($grouped->get(Team::class)->first()->id)->toBe($this->team1->id)
        ->and($grouped->get(Organization::class)->first())->toBeInstanceOf(Organization::class)
        ->and($grouped->get(Organization::class)->first()->id)->toBe($this->org->id);
});

test('followingCount returns correct count', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->team3);

    expect($this->user->followingCount())->toBe(3);
});

test('followingCount with type filter returns correct count', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->org);

    expect($this->user->followingCount(Team::class))->toBe(2)
        ->and($this->user->followingCount(Organization::class))->toBe(1);
});

test('followingCount returns zero when not following anyone', function () {
    expect($this->user->followingCount())->toBe(0);
});

test('followingRecords still returns Follow models', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);

    $records = $this->user->followingRecords;

    expect($records)->toHaveCount(2)
        ->and($records->first())->toBeInstanceOf(\Ritechoice23\Followable\Models\Follow::class);
});

test('followings query is optimized and uses JOIN', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);

    // Get SQL query
    $query = $this->user->followings(Team::class);
    $sql = $query->toSql();

    // Should contain JOIN
    expect($sql)->toContain('join');
});

test('followings with mixed types should use followingsGrouped', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->org);

    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $this->user->follow($user2);

    // When you have multiple followable types, use followingsGrouped()
    $grouped = $this->user->followingsGrouped();

    expect($grouped)->toHaveCount(3);  // 3 different types

    // Verify we have all different types properly grouped
    expect($grouped->has(Team::class))->toBeTrue()
        ->and($grouped->has(Organization::class))->toBeTrue()
        ->and($grouped->has(User::class))->toBeTrue();

    // Verify correct models
    expect($grouped->get(Team::class)->first())->toBeInstanceOf(Team::class)
        ->and($grouped->get(Organization::class)->first())->toBeInstanceOf(Organization::class)
        ->and($grouped->get(User::class)->first())->toBeInstanceOf(User::class);
});

test('followings returns results ordered by follow date descending', function () {
    $this->user->follow($this->team1);
    sleep(1);
    $this->user->follow($this->team2);
    sleep(1);
    $this->user->follow($this->team3);

    $followings = $this->user->followings()->get();

    // Most recent following should be first
    expect($followings->first()->id)->toBe($this->team3->id)
        ->and($followings->last()->id)->toBe($this->team1->id);
});

test('followings can chain with other query methods', function () {
    $this->user->follow($this->team1);
    $this->user->follow($this->team2);
    $this->user->follow($this->team3);

    $count = $this->user->followings()
        ->where('name', 'like', '%Team%')
        ->count();

    expect($count)->toBe(3);
});

test('followings works with users following users', function () {
    $user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

    $this->user->follow($user2);
    $this->user->follow($user3);

    $followings = $this->user->followings(User::class)->get();

    expect($followings)->toHaveCount(2)
        ->and($followings->first())->toBeInstanceOf(User::class);
});

<?php

use Ritechoice23\Followable\Tests\Models\Organization;
use Ritechoice23\Followable\Tests\Models\Team;
use Ritechoice23\Followable\Tests\Models\User;

beforeEach(function () {
    $this->team = Team::create(['name' => 'Team Alpha']);
    $this->user1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->user2 = User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $this->user3 = User::create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);
    $this->org = Organization::create(['name' => 'Acme Corp']);
});

test('followers returns actual follower models not Follow models', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);

    $followers = $this->team->followers()->get();

    expect($followers)->toHaveCount(2)
        ->and($followers->first())->toBeInstanceOf(User::class)
        ->and($followers->pluck('id')->sort()->values()->all())->toBe([$this->user1->id, $this->user2->id]);
});

test('followers returns empty collection when no followers', function () {
    $followers = $this->team->followers()->get();

    expect($followers)->toBeEmpty();
});

test('followers can be paginated', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->user3->follow($this->team);

    $paginated = $this->team->followers()->paginate(2);

    expect($paginated)->toHaveCount(2)
        ->and($paginated->total())->toBe(3)
        ->and($paginated->lastPage())->toBe(2);
});

test('followers can be filtered with where clauses', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);

    $followers = $this->team->followers()
        ->where('name', 'John Doe')
        ->get();

    expect($followers)->toHaveCount(1)
        ->and($followers->first()->id)->toBe($this->user1->id);
});

test('followers can be ordered', function () {
    $this->user1->follow($this->team);
    sleep(1);
    $this->user2->follow($this->team);

    $followers = $this->team->followers()
        ->orderBy('name', 'asc')
        ->get();

    expect($followers->first()->name)->toBe('Jane Doe')
        ->and($followers->last()->name)->toBe('John Doe');
});

test('followers with single type returns correct models', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);

    $followers = $this->team->followers(User::class)->get();

    expect($followers)->toHaveCount(2)
        ->and($followers->first())->toBeInstanceOf(User::class);
});

test('followersPaginated returns paginated results', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->user3->follow($this->team);

    $paginated = $this->team->followersPaginated(2);

    expect($paginated->total())->toBe(3)
        ->and($paginated->perPage())->toBe(2)
        ->and($paginated)->toHaveCount(2);
});

test('followersPaginated with custom per page', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->user3->follow($this->team);

    $paginated = $this->team->followersPaginated(10);

    expect($paginated->perPage())->toBe(10)
        ->and($paginated)->toHaveCount(3);
});

test('followersPaginated can filter by type', function () {
    $this->user1->follow($this->team);
    $this->org->follow($this->team);

    $paginated = $this->team->followersPaginated(10, User::class);

    expect($paginated->total())->toBe(1)
        ->and($paginated->first())->toBeInstanceOf(User::class);
});

test('followersOfType returns only specified type', function () {
    $this->user1->follow($this->team);
    $this->org->follow($this->team);

    $followers = $this->team->followersOfType(User::class)->get();

    expect($followers)->toHaveCount(1)
        ->and($followers->first())->toBeInstanceOf(User::class);
});

test('followersOfType with array of types works via followersGrouped', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->org->follow($this->team);

    $team2 = Team::create(['name' => 'Team Beta']);
    $team2->follow($this->team);

    // For multiple types, use followersGrouped() which is the recommended approach
    $grouped = $this->team->followersGrouped();

    expect($grouped->has(User::class))->toBeTrue()
        ->and($grouped->has(Organization::class))->toBeTrue()
        ->and($grouped->has(Team::class))->toBeTrue()
        ->and($grouped->get(User::class))->toHaveCount(2)
        ->and($grouped->get(Organization::class))->toHaveCount(1)
        ->and($grouped->get(Team::class))->toHaveCount(1);

    // Total count across all types
    $totalCount = $grouped->flatten()->count();
    expect($totalCount)->toBe(4);
});

test('followersOfType with single type in array works', function () {
    $this->user1->follow($this->team);
    $this->org->follow($this->team);

    $followers = $this->team->followersOfType([User::class])->get();

    expect($followers)->toHaveCount(1)
        ->and($followers->first())->toBeInstanceOf(User::class);
});

test('followersGrouped returns collection grouped by type', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->org->follow($this->team);

    $grouped = $this->team->followersGrouped();

    expect($grouped)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($grouped->has(User::class))->toBeTrue()
        ->and($grouped->has(Organization::class))->toBeTrue()
        ->and($grouped->get(User::class))->toHaveCount(2)
        ->and($grouped->get(Organization::class))->toHaveCount(1);
});

test('followersGrouped returns empty collection when no followers', function () {
    $grouped = $this->team->followersGrouped();

    expect($grouped)->toBeEmpty();
});

test('followersGrouped models are actual instances', function () {
    $this->user1->follow($this->team);
    $this->org->follow($this->team);

    $grouped = $this->team->followersGrouped();

    expect($grouped->get(User::class)->first())->toBeInstanceOf(User::class)
        ->and($grouped->get(User::class)->first()->id)->toBe($this->user1->id)
        ->and($grouped->get(Organization::class)->first())->toBeInstanceOf(Organization::class)
        ->and($grouped->get(Organization::class)->first()->id)->toBe($this->org->id);
});

test('followersCount returns correct count', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->user3->follow($this->team);

    expect($this->team->followersCount())->toBe(3);
});

test('followersCount with type filter returns correct count', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->org->follow($this->team);

    expect($this->team->followersCount(User::class))->toBe(2)
        ->and($this->team->followersCount(Organization::class))->toBe(1);
});

test('followersCount returns zero when no followers', function () {
    expect($this->team->followersCount())->toBe(0);
});

test('followRecords still returns Follow models', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);

    $records = $this->team->followRecords;

    expect($records)->toHaveCount(2)
        ->and($records->first())->toBeInstanceOf(\Ritechoice23\Followable\Models\Follow::class);
});

test('followers query is optimized and uses JOIN', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);

    // Get SQL query
    $query = $this->team->followers(User::class);
    $sql = $query->toSql();

    // Should contain JOIN
    expect($sql)->toContain('join');
});

test('followers with mixed types should use followersGrouped', function () {
    $this->user1->follow($this->team);
    $this->org->follow($this->team);

    $team2 = Team::create(['name' => 'Team Beta']);
    $team2->follow($this->team);

    // When you have multiple follower types, use followersGrouped()
    $grouped = $this->team->followersGrouped();

    expect($grouped)->toHaveCount(3);  // 3 different types

    // Verify we have all different types properly grouped
    expect($grouped->has(User::class))->toBeTrue()
        ->and($grouped->has(Organization::class))->toBeTrue()
        ->and($grouped->has(Team::class))->toBeTrue();

    // Verify correct models
    expect($grouped->get(User::class)->first())->toBeInstanceOf(User::class)
        ->and($grouped->get(Organization::class)->first())->toBeInstanceOf(Organization::class)
        ->and($grouped->get(Team::class)->first())->toBeInstanceOf(Team::class);
});

test('followers returns results ordered by follow date descending', function () {
    $this->user1->follow($this->team);
    sleep(1);
    $this->user2->follow($this->team);
    sleep(1);
    $this->user3->follow($this->team);

    $followers = $this->team->followers()->get();

    // Most recent follower should be first
    expect($followers->first()->id)->toBe($this->user3->id)
        ->and($followers->last()->id)->toBe($this->user1->id);
});

test('followers can chain with other query methods', function () {
    $this->user1->follow($this->team);
    $this->user2->follow($this->team);
    $this->user3->follow($this->team);

    $count = $this->team->followers()
        ->where('name', 'like', '%Doe%')
        ->count();

    expect($count)->toBe(2);
});

test('followers works with models that can follow themselves', function () {
    $team2 = Team::create(['name' => 'Team Beta']);
    $team3 = Team::create(['name' => 'Team Gamma']);

    $team2->follow($this->team);
    $team3->follow($this->team);

    $followers = $this->team->followers(Team::class)->get();

    expect($followers)->toHaveCount(2)
        ->and($followers->first())->toBeInstanceOf(Team::class);
});

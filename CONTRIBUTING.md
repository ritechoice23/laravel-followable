# Contributing to Laravel Followable

Thank you for considering contributing to Laravel Followable! We welcome contributions from the community and are grateful for any help you can provide.

## Table of Contents

-   [Code of Conduct](#code-of-conduct)
-   [Getting Started](#getting-started)
-   [Development Setup](#development-setup)
-   [How to Contribute](#how-to-contribute)
-   [Coding Standards](#coding-standards)
-   [Testing Guidelines](#testing-guidelines)
-   [Pull Request Process](#pull-request-process)
-   [Reporting Bugs](#reporting-bugs)
-   [Suggesting Enhancements](#suggesting-enhancements)
-   [Documentation](#documentation)

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please be respectful, inclusive, and considerate in all interactions.

## Getting Started

Before you begin:

-   Make sure you have a [GitHub account](https://github.com/signup/free)
-   Familiarize yourself with the [README](README.md) and package functionality
-   Check existing [issues](https://github.com/ritechoice23/laravel-followable/issues) and [pull requests](https://github.com/ritechoice23/laravel-followable/pulls) to avoid duplicates

## Development Setup

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   Git

### Installation

1. **Fork the repository** on GitHub

2. **Clone your fork** locally:

    ```bash
    git clone https://github.com/YOUR-USERNAME/followable.git
    cd followable
    ```

3. **Install dependencies**:

    ```bash
    composer install
    ```

4. **Create a new branch** for your feature or bugfix:
    ```bash
    git checkout -b feature/your-feature-name
    # or
    git checkout -b fix/your-bugfix-name
    ```

## How to Contribute

### Types of Contributions

We welcome many types of contributions:

-   **Bug fixes**: Fix issues reported in the issue tracker
-   **New features**: Add new functionality (please discuss in an issue first)
-   **Documentation**: Improve README, code comments, or create examples
-   **Tests**: Add or improve test coverage
-   **Code quality**: Refactoring, optimization, or style improvements

### Before You Start

For major changes:

1. **Open an issue** first to discuss what you would like to change
2. Wait for feedback from maintainers
3. Once approved, create your pull request

For minor changes (typos, small bugs, documentation):

-   Feel free to submit a PR directly

## Coding Standards

### PHP Standards

We follow Laravel and PSR-12 coding standards:

-   Use **4 spaces** for indentation (no tabs)
-   Follow **PSR-12** naming conventions
-   Use **type hints** for parameters and return types
-   Write **descriptive variable and method names**
-   Keep methods **focused and single-purpose**

### Code Style

This project uses **Laravel Pint** for automatic code formatting:

```bash
composer format
```

**Always run Pint before committing** to ensure consistent code style.

### PHPStan

We use PHPStan for static analysis to catch potential bugs:

```bash
composer analyse
```

Ensure your code passes PHPStan analysis before submitting a PR.

## Testing Guidelines

### Running Tests

Run the full test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Run specific test files:

```bash
./vendor/bin/pest tests/FollowTest.php
```

Run tests by filter:

```bash
./vendor/bin/pest --filter=mutual
```

### Writing Tests

-   **All new features** must include tests
-   **Bug fixes** should include a test that fails before the fix
-   Use **descriptive test names** that explain what is being tested
-   Follow the existing test structure and conventions
-   Aim for **high test coverage** (we maintain comprehensive coverage)

### Test Structure

```php
it('describes what the test does in plain English', function () {
    // Arrange: Set up test data
    $user = User::factory()->create();
    $team = Team::factory()->create();

    // Act: Perform the action being tested
    $user->follow($team);

    // Assert: Verify the expected outcome
    expect($user->isFollowing($team))->toBeTrue();
});
```

### Test Categories

Tests are organized into categories:

-   **FollowTest.php**: Basic follow/unfollow operations
-   **FollowersQueryTest.php**: Follower query methods
-   **FollowingsQueryTest.php**: Following query methods
-   **MutualTest.php**: Mutual relationships
-   **PolymorphicTest.php**: Polymorphic relationships
-   **ScopesTest.php**: Query scopes
-   **EdgeCasesTest.php**: Edge cases and error handling
-   **ConfigTest.php**: Configuration options

Add your tests to the appropriate file or create a new category if needed.

## Pull Request Process

### Before Submitting

Ensure your PR meets these requirements:

1. ✅ **Tests pass**: `composer test`
2. ✅ **Code style**: `composer format`
3. ✅ **Static analysis**: `composer analyse`
4. ✅ **Tests added**: New features have tests
5. ✅ **Documentation updated**: README or code comments updated if needed
6. ✅ **Changelog updated**: Add your changes to CHANGELOG.md (under "Unreleased")

### Submitting a Pull Request

1. **Push your changes** to your fork:

    ```bash
    git push origin feature/your-feature-name
    ```

2. **Create a Pull Request** on GitHub:

    - Go to the main repository
    - Click "New Pull Request"
    - Select your fork and branch
    - Fill out the PR template

3. **PR Title Format**:

    - Use present tense: "Add feature" not "Added feature"
    - Be descriptive: "Add mutual followers query method"
    - Prefix with type: `[Feature]`, `[Fix]`, `[Docs]`, `[Refactor]`, `[Test]`

4. **PR Description** should include:
    - Summary of changes
    - Motivation and context
    - Related issue number (if applicable): "Fixes #123"
    - Screenshots (if UI-related)
    - Breaking changes (if any)

### Review Process

-   Maintainers will review your PR as soon as possible
-   Be responsive to feedback and questions
-   Make requested changes by pushing new commits
-   Once approved, your PR will be merged

## Reporting Bugs

### Before Reporting

-   Check if the bug has already been reported in [issues](https://github.com/ritechoice23/laravel-followable/issues)
-   Test on the latest version to ensure the bug still exists
-   Gather information about your environment

### Bug Report Template

When creating a bug report, include:

**Description**
A clear description of the bug

**Steps to Reproduce**

1. Step one
2. Step two
3. Step three

**Expected Behavior**
What you expected to happen

**Actual Behavior**
What actually happened

**Environment**

-   Laravel version:
-   PHP version:
-   Package version:
-   Database:

**Code Sample**

```php
// Minimal code that reproduces the issue
```

**Stack Trace**

```
// Error message and stack trace if applicable
```

## Suggesting Enhancements

We love enhancement suggestions! To suggest a feature:

1. **Check existing issues** to see if it's already suggested
2. **Open a new issue** with the `[Feature Request]` prefix
3. **Describe the feature**:
    - What problem does it solve?
    - How should it work?
    - Example usage code
    - Potential implementation approach

## Documentation

### Code Comments

-   Write **clear, concise comments** for complex logic
-   Use **PHPDoc blocks** for all public methods:
    ```php
    /**
     * Follow the given model.
     *
     * @param  Model|int|string  $followable  The model to follow
     * @param  array  $metadata  Optional metadata to attach
     * @return \Ritechoice23\Followable\Models\Follow
     */
    public function follow(Model|int|string $followable, array $metadata = []): Follow
    {
        // Implementation
    }
    ```

### README Updates

When adding features:

-   Update the README with usage examples
-   Add to the appropriate section
-   Keep examples simple and clear
-   Maintain consistent formatting

### Changelog

Follow [Keep a Changelog](https://keepachangelog.com/) format:

```markdown
## [Unreleased]

### Added

-   New feature description

### Changed

-   Changed feature description

### Fixed

-   Bug fix description
```

## Development Workflow

### Branch Naming

-   `feature/feature-name` - New features
-   `fix/bug-name` - Bug fixes
-   `refactor/description` - Code refactoring
-   `test/description` - Test improvements

### Commit Messages

We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification for clear and standardized commit messages:

**Format:**

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**Types:**

-   `feat:` - A new feature
-   `fix:` - A bug fix
-   `docs:` - Documentation only changes
-   `style:` - Code style changes (formatting, missing semicolons, etc.)
-   `refactor:` - Code changes that neither fix a bug nor add a feature
-   `test:` - Adding or updating tests
-   `chore:` - Maintenance tasks, dependency updates, etc.
-   `perf:` - Performance improvements
-   `ci:` - CI/CD changes

**Example:**

```
feat(mutual): add mutual followers query method

- Implement mutualFollowers() in HasFollowers trait
- Implement mutualFollowings() in CanFollow trait
- Add comprehensive tests for mutual relationships
- Update README with usage examples

Fixes #123
```

**Good commit messages:**

-   `feat: add block functionality to prevent following`
-   `fix: resolve n+1 query issue in followers relationship`
-   `docs: update README with batch operations examples`
-   `test: add edge case tests for polymorphic relationships`
-   `refactor: simplify follow status checking logic`

**Bad commit messages:**

-   "update"
-   "fix bug"
-   "changes"
-   "wip"

**Guidelines:**

-   Keep the subject line under 72 characters
-   Use the imperative mood: "add" not "added" or "adds"
-   Don't capitalize the first letter after the type
-   No period at the end of the subject line
-   Add details in the body if needed (separated by a blank line)
-   Reference issues in the footer: "Fixes #123" or "Closes #456"
-   Use `BREAKING CHANGE:` in the footer for breaking changes

## Questions?

If you have questions about contributing:

1. Check the [README](README.md)
2. Search [existing issues](https://github.com/ritechoice23/laravel-followable/issues)
3. Open a new issue with your question
4. Contact the maintainer: daramolatunde23@gmail.com

## Recognition

Contributors will be recognized in:

-   The project's README (Contributors section)
-   GitHub's contributor graph
-   Release notes (for significant contributions)

Thank you for contributing to Laravel Followable! 🎉

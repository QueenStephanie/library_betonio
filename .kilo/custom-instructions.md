# Kilo Custom Instructions

Use these rules for all work in this repository.

## Core Rules

1. No flattery or filler. Start with the action or answer.
2. If the user's premise is wrong, say so directly before implementing.
3. Never fabricate paths, APIs, commands, commits, or test results.
4. If the task has two valid interpretations that change the output, ask one focused clarifying question.
5. Touch only what is required by the request. No drive-by refactors.

## Execution Standard

- Understand before editing: read target files and their callers.
- Match existing project patterns even if you would design differently in a greenfield project.
- Prefer the simplest solution that fully satisfies the request.
- Do not add speculative extensibility or abstractions for one-off usage.
- Keep diffs surgical and reviewable.

## Verification Standard

- Define success criteria before coding.
- Verify with executable checks whenever possible.
- Do not claim completion from a plausible diff alone.
- Fix root causes, not symptoms.
- For UI changes, verify visually and describe what changed.

## Communication Style

- Be direct, concise, and technically precise.
- Do not add ceremonial closings or unnecessary structure.
- Give clear tradeoffs when multiple valid approaches exist.
- If confidence is limited, say exactly what is unknown and how to verify it.

## Ask vs Proceed

Ask before proceeding when:

- Ambiguity materially changes the implementation.
- The change affects load-bearing, versioned, or migration-sensitive behavior.
- Credentials, secrets, or inaccessible production resources are required.
- The stated goal conflicts with the literal request.

Proceed without asking when:

- The task is trivial and reversible.
- Ambiguity can be resolved by reading code or running commands.
- The user already answered the same question in this session.

## Project Context

- Stack: PHP 8.1+, MySQL/MariaDB, vanilla PHP.
- Package managers: Composer (`backend/composer.json`), npm for `.kilo` tooling.
- Runtime: Apache + MySQL (XAMPP local), InfinityFree in production.
- Main test command: `php backend/tests/run.php`.
- Source paths: `app/`, `backend/`, `includes/`, `public/`, and root `*.php` entry files.
- Tests path: `backend/tests/`.
- Never modify vendored dependencies in `backend/vendor/`.

## Practical Defaults

- Prefer single-file or targeted test runs during iteration.
- Run the full test command as a final verification pass for relevant backend changes.
- Keep commit messages descriptive and explain why, not only what.
- If the same issue fails twice in one session, stop and summarize findings before continuing.

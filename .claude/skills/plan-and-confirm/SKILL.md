---
name: plan-and-confirm
description: Explore the codebase, present a clear implementation plan, and wait for explicit user approval before writing any code. Use for any non-trivial change (new feature, bug fix, refactor). Rule #1 — never code without OKAY.
---

# Plan & Confirm

## Phase 1 — Explore (read only, no modifications)

Before writing any plan, explore the relevant files:

1. Read all files directly related to the task
2. Search for usages of affected symbols with `Grep` (function names, class names, component names)
3. Understand the project's patterns and conventions (naming, structure, validation approach)
4. Identify potential side effects: other files that import or depend on what will change

**Rule: zero file modifications in this phase.**

---

## Phase 2 — Present the plan

Structure your response exactly as follows:

### What I understood
- Describe the request with precise file:line references
- Flag any ambiguous or non-obvious points
- List all possible interpretations if more than one exists

### What I will do
- List every file to modify or create, with the reasoning for each change
- State explicitly what will NOT be changed and why
- If the change spans multiple files with ordering constraints, explain the sequence

### Risks & attention points
- Any code that could break elsewhere in the app
- Any assumption made (and why)
- Any breaking API change, DB migration, or env variable addition

---

## Phase 3 — Wait for approval

**STOP. Do not touch any file.**

Output literally:

> Please confirm with **OKAY** if the understanding and plan are correct.
> Otherwise, tell me what is wrong and I will adjust before coding.

**Absolute rule:** If the user does not say OKAY (or equivalent: "yes", "go", "ok", "c'est bon", "lance"), stay in Phase 3. Do not proceed.

---

## Phase 4 — Implement (only after OKAY)

Once OKAY is received:

1. Implement exactly as described in the approved plan — no additions, no improvements beyond scope
2. If something discovered during implementation changes the plan → **stop and re-present** before continuing
3. Summarize changes with file:line references after completion

**Never commit** unless the user explicitly asks.

---

## Rules

- **Never code without OKAY** — this is rule #1
- Base all analysis on files actually read — no guessing
- If the plan is rejected, re-explore if needed then present a revised plan
- A "trivial change" (typo, single string edit) does not require this workflow

$ARGUMENTS

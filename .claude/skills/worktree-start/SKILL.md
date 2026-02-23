---
name: worktree-start
description: Start work on a GitHub issue using a git worktree. Use when starting a new feature or fix from an issue number.
---

# Start Worktree — Issue → Worktree → Branch

Follow these steps to start working on a GitHub issue.

## Step 1 — Fetch issue info

```bash
gh issue view $ARGUMENTS
```

Extract:
- The **title** to name the branch (slug en kebab-case)
- The **type**: feat / fix / refactor / test / chore

## Step 2 — Update presentation locally

```bash
git fetch origin
git checkout presentation
git pull origin presentation
```

**Never use `develop` or `main` as base for this project. Always use `presentation`.**

## Step 3 — Create the worktree

Folder format: `../KLASSCIv2-issue-<N>`
Branch format: `issue-<N>-<slug>`

Example for issue #42 "Add filière liaison modal":
- Folder: `../KLASSCIv2-issue-42`
- Branch: `issue-42-filiere-liaison-modal`

```bash
git worktree add ../KLASSCIv2-issue-<N> -b issue-<N>-<slug> origin/presentation
```

Verify with:
```bash
git worktree list
```

## Step 4 — Configure commit author in the worktree

```bash
cd ../KLASSCIv2-issue-<N>
git config user.name "James10192"
git config user.email "djedjelipatrick@gmail.com"
```

## Step 5 — Confirm to user

Output:
```
Worktree created:
  Folder : ../KLASSCIv2-issue-<N>
  Branch : issue-<N>-<slug>
  Base   : origin/presentation
  Author : James10192 <djedjelipatrick@gmail.com>

All files to modify are in: ../KLASSCIv2-issue-<N>/
Once done: /commit then /workflow:create-pr then /git:worktree-finish <N>
```

## Rules

- **Never work directly on `presentation`, `main`, or `develop`**
- Always base from `origin/presentation` (not local, not develop)
- Worktree is a sibling directory: `../KLASSCIv2-issue-<N>` (one level above the repo)
- Branch naming: `issue-N-slug` (no type prefix)
- Commit author must be set in the worktree: `James10192 <djedjelipatrick@gmail.com>`
- NEVER `git add .` or `git add -A` — always stage files explicitly
- NEVER "Generated with Claude Code" or "Co-Authored-By" in commits

$ARGUMENTS

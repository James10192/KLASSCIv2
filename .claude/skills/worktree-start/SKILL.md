---
name: worktree-start
description: Start work on a GitHub issue using a git worktree. Use when starting a new feature or fix from an issue number.
---

# Start Worktree — Issue → Worktree → Branch → PR

Follow these steps to start working on a GitHub issue.

## Step 1 — Fetch issue info

```bash
gh issue view $ARGUMENTS
```

Extract:
- The **title** to name the branch
- The **type**: feat / fix / refactor / test / chore

## Step 2 — Update develop locally

```bash
git fetch origin
git checkout develop
git pull origin develop
```

If no `develop` branch exists, use `main`.

## Step 3 — Create the worktree

Folder format: `../worktree-<issue>-<slug>`
Branch format: `<type>/<issue>-<slug>`

Example for issue #42 "Add image gallery filter":
- Folder: `../worktree-42-image-gallery-filter`
- Branch: `feature/42-image-gallery-filter`

```bash
git worktree add ../worktree-<issue>-<slug> -b <type>/<issue>-<slug> origin/develop
```

## Step 4 — Confirm to user

Output:
```
Worktree created:
  Folder : ../worktree-<issue>-<slug>
  Branch : <type>/<issue>-<slug>
  Base   : origin/develop

Open this folder in your editor or navigate with:
  cd ../worktree-<issue>-<slug>
```

## Rules

- **Never work directly on `develop` or `main`**
- The worktree is a separate folder — multiple features can run in parallel
- Always base from `origin/develop` (not local)
- Branch naming: `feature/N-desc`, `fix/N-desc`, `hotfix/N-desc`, `chore/desc`

$ARGUMENTS

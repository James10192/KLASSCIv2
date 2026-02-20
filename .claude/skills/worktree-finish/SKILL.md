---
name: worktree-finish
description: Finish work on a worktree — push branch, create PR to develop, clean up. Use when the feature/fix is complete.
---

# Finish Worktree — Push → PR → Cleanup

Follow these steps to finalize work in a worktree.

## Step 1 — Check state

```bash
git status
git diff --staged
git log --oneline origin/develop..HEAD
```

Make sure there are no uncommitted changes.

## Step 2 — Push branch

```bash
git push -u origin HEAD
```

## Step 3 — Create the PR targeting `develop`

```bash
gh pr create \
  --base develop \
  --title "<type>(<scope>): <description>" \
  --body "$(cat <<'EOF'
## Description

<!-- What this PR does -->

## Closes

Closes #<issue-number>

## Checklist

- [ ] Tests pass
- [ ] Linting passes
- [ ] Branch is up to date with develop
- [ ] No secrets committed
EOF
)"
```

If there is no `develop` branch, use `main` as the base.

## Step 4 — Show the PR link

```bash
gh pr view --web
```

## Step 5 — Clean up the worktree (AFTER merge only)

**Only remove the worktree AFTER the PR is merged into develop.**

```bash
# From the main repo folder (not the worktree)
git worktree remove ../worktree-<issue>-<slug>
git worktree prune
```

## Step 6 — Confirm to user

```
PR created: <URL>
  Base   : develop
  Branch : <branch-name>
  Issue  : #<issue-number>

Waiting for review.
Once merged, clean up with:
  git worktree remove ../worktree-<issue>-<slug>
```

## Rules

- **PR targets `develop`, never directly `main`**
- PR title must follow: `type(scope): description`
- `Closes #N` in the body auto-closes the issue on merge

$ARGUMENTS

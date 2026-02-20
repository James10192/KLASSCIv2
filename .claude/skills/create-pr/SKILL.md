---
name: create-pr
description: Create a pull request. Use when the user asks to create a PR or push their branch.
---

Create a pull request following the team workflow.

## Step 1 — Verify state
```bash
git status
git log develop..HEAD --oneline
git diff develop...HEAD --stat
```

If there is no `develop` branch, use `main` as the base.

## Step 2 — Push branch
```bash
git push -u origin HEAD
```

## Step 3 — Pre-PR sanity check
Before creating the PR, verify:
- No `.env`, secrets, or build artifacts committed
- All commits follow conventional format (`type(scope): description`)
- CI checks are expected to pass

## Step 4 — Create the PR

```bash
gh pr create \
  --base develop \
  --title "<type>(<scope>): <description>" \
  --body "$(cat <<'EOF'
## Summary
- [What this PR does in 1-3 bullet points]

## Changes
- `file` — [what changed and why]

## Type of change
- [ ] feat — new feature
- [ ] fix — bug fix
- [ ] refactor — no behavior change
- [ ] test — tests only
- [ ] chore — maintenance

## Testing
- [ ] Tests added/updated
- [ ] Tested locally
- [ ] No regressions

## Breaking changes
- [ ] No breaking changes
- [ ] Breaking change — describe migration steps below

## Checklist
- [ ] Follows project conventions (CLAUDE.md)
- [ ] No secrets or sensitive data committed
- [ ] CI expected to pass
EOF
)"
```

## Step 5 — Output
Return the PR URL to the user.

$ARGUMENTS

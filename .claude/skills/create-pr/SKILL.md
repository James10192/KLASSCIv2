---
name: create-pr
description: Create a pull request. Use when the user asks to create a PR or push their branch.
---

Create a pull request following the team workflow.

## Step 1 — Verify state
```bash
git status
git log presentation..HEAD --oneline
git diff presentation...HEAD --stat
```

**For this project, the base branch is always `presentation`.**

## Step 2 — Push branch
```bash
git push -u origin HEAD
```

## Step 3 — Pre-PR sanity check
Before creating the PR, verify:
- No `.env`, secrets, or build artifacts committed
- All commits follow conventional format (`type(scope): description`)
- Commit author is `James10192 <djedjelipatrick@gmail.com>`
- No "Generated with Claude Code" or "Co-Authored-By" in any commit

## Step 4 — Create the PR

```bash
gh pr create \
  --base presentation \
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
- [ ] chore — maintenance

## Testing
- [ ] Tested on esbtp-abidjan.klassci.com after deploy
- [ ] No regressions

## Checklist
- [ ] No secrets or sensitive data committed
- [ ] PHP syntax verified (php -l) on all modified PHP files
EOF
)"
```

## Step 5 — Output
Return the PR URL to the user.

After PR is created, inform the user:
> "PR créée. Après review et merge, lancez `/git:worktree-finish <N>`"

$ARGUMENTS

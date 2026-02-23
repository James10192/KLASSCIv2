---
name: commit
description: Create a conventional commit. Use when the user asks to commit changes.
---

Create a conventional commit following these steps:

1. Run `git status` to see all changed files
2. Run `git diff --staged` and `git diff` to review all changes
3. Run `git log --oneline -5` to understand the project's commit style and scopes in use

4. Analyze the changes and determine:
   - **type**: feat | fix | refactor | test | docs | chore | perf | style | ci
   - **scope**: a short noun describing the area changed (e.g. matieres, liaisons, auth, migrations)
   - **description**: imperative present tense, ≤ 72 chars (e.g. "add filiere liaison modal")

5. Verify commit author is set correctly in the worktree:
   ```bash
   git config user.name
   git config user.email
   ```
   If not set, configure before committing:
   ```bash
   git config user.name "James10192"
   git config user.email "djedjelipatrick@gmail.com"
   ```

6. Stage only relevant files explicitly (NEVER `git add -A` or `git add .`)
   - Never stage: `.env`, `.env.*`, secrets, build artifacts, OS files (.DS_Store, Thumbs.db)

7. Create the commit:
```bash
git commit -m "$(cat <<'EOF'
<type>(<scope>): <description>

[optional body explaining WHY if not obvious]
EOF
)"
```

**Rules:**
- NO "Generated with Claude Code" or "Co-Authored-By" in commits
- NO WIP commits
- Commit author: `James10192 <djedjelipatrick@gmail.com>`
- If multiple unrelated changes exist, ask the user to split into separate commits
- Always verify `git diff --staged` before committing

$ARGUMENTS

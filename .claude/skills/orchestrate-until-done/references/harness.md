# Harness : un skill, plusieurs produits

Sources consultées 15/09/2026 :

- [Agent Skills spec](https://agentskills.io/specification) — `SKILL.md` + frontmatter `name`/`description`
- [Anthropic Skills overview](https://docs.anthropic.com/en/docs/agents-and-tools/agent-skills/overview) — progressive disclosure
- [OpenCode Skills](https://opencode.ai/docs/skills) — découvre `.claude/skills`, `.opencode/skills`, `.agents/skills`
- [OpenCode Commands](https://opencode.ai/docs/commands) — `.opencode/commands/*.md`
- [OpenCode Plugins](https://opencode.ai/docs/plugins) — `session.idle`, `experimental.session.compacting`
- Claude Code Stop hooks — `.claude/settings.json` → `completude-check.sh`

## Il n'y a pas de Workflow engine ici

`.claude/commands/workflow/` ne contient que `plan-and-confirm.md`. L'outil `Workflow` n'existe pas dans OpenCode. Ce skill + `/orchestrate` + plugin idle **sont** le workflow.

## Où poser les fichiers pour que « n'importe quel harness » les voie

| Harness | Ce qu'il lit | Fichier KLASSCI |
|---|---|---|
| OpenCode | AGENTS.md, `.claude/skills`, `.opencode/commands`, plugins | les trois |
| Claude Code | `.claude/skills`, `.claude/commands`, Stop hooks, CLAUDE.md | skill + command + hook existant |
| Cursor | AGENTS.md, `.cursor/rules` optionnel | bloc Pre-prompt AGENTS.md |
| Codex | AGENTS.md, `.Codex/rules` | idem |
| Copilot / autres | AGENTS.md à la racine | idem |

Junctions : `scripts/link-opencode-claude.ps1` relie `.opencode/{rules,hooks,skills}` → `.claude/`. **Pas** les commands ni les plugins : d'où les copies `.opencode/commands/orchestrate.md` et `.opencode/plugins/completude-idle.js`.

## Pre-prompt (copie dans AGENTS.md)

Le skill ne se charge que si l'agent **décide** de l'appeler. Les modèles paresseux skippent les skills. Le paragraphe AGENTS.md est donc le filet : il est **toujours** dans le contexte. Il dit : dès qu'il y a plusieurs points, ouvre completude.md et charge `orchestrate-until-done`.

## Compaction

Quand le contexte est plein, le modèle « oublie » la liste et rend la main. Le plugin OpenCode injecte les cases `[ ]` restantes dans `experimental.session.compacting`. Sur Claude Code, le Stop hook rattrape après coup.

## Windows

`completude-check.sh` a besoin de `jq` (souvent absent). Le plugin Node/Bun n'en a pas besoin. Les deux lisent le même markdown.

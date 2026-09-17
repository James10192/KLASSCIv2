import { readFileSync, existsSync } from "node:fs"
import { join } from "node:path"

function remaining(directory) {
  const rec = join(directory, ".claude", "completude.md")
  if (!existsSync(rec)) return null
  const text = readFileSync(rec, "utf8")
  const unchecked = text.match(/^[\t ]*[-*][\t ]+\[[\t ]\]/gm) || []
  if (unchecked.length === 0) return null
  const lines = text
    .split(/\r?\n/)
    .filter((l) => /^[\t ]*[-*][\t ]+\[[\t ]\]/.test(l))
    .slice(0, 12)
  return { count: unchecked.length, lines, excerpt: text.slice(0, 4000) }
}

export const CompletudeIdle = async ({ directory, client }) => {
  return {
    event: async ({ event }) => {
      if (event?.type !== "session.idle") return
      const left = remaining(directory)
      if (!left) return
      try {
        await client?.tui?.showToast?.({
          title: "Compte rendu incomplet",
          message: `${left.count} point(s) encore ouverts dans .claude/completude.md`,
        })
      } catch {
        // toast optionnel selon version SDK
      }
    },
    "experimental.session.compacting": async (_input, output) => {
      const left = remaining(directory)
      if (!left) return
      output.context.push(`## Compte rendu encore ouvert (.claude/completude.md)

${left.count} case(s) non cochée(s). Tu n'as pas le droit de rendre la main.
Soit tu les fais, soit tu les déplaces dans « Non fait, et pourquoi ».
Interdit : « veux-tu que je continue », supprimer une vue mobile, sauter la recherche internet.

${left.lines.join("\n")}
`)
    },
  }
}

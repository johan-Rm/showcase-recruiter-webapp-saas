import { readFileSync } from 'node:fs'
import { relative, resolve } from 'node:path'

import { Glob } from 'bun'
import { parse } from 'yaml'

import { CHECKERS } from './checkers'
import { regionOf, type RegionName } from './regions'
import { STRUCTURE_CHECKERS } from './structure-checkers'

const ROOT = resolve(import.meta.dir, '../..')
const ALL_CHECKERS = { ...CHECKERS, ...STRUCTURE_CHECKERS }

type Forbid = { text?: string; pattern?: string }

interface Rule {
  name: string
  readme: string
  scope: string[]
  except?: string[]
  region?: RegionName
  forbid?: Forbid[]
  check?: string
  message: string
}

interface Violation {
  rule: Rule
  file: string
  line: number
  hit: string
}

const hitOf = (line: string, forbid: Forbid): string | null => {
  if (forbid.text && line.includes(forbid.text)) return forbid.text
  if (forbid.pattern) {
    const found = line.match(new RegExp(forbid.pattern))
    if (found) return found[0]
  }
  return null
}

const checkerOf = (rule: Rule) => {
  const checker = ALL_CHECKERS[rule.check!]
  if (!checker) {
    console.error(`✖ ${rule.name} — vérificateur « ${rule.check} » inconnu`)
    process.exit(1)
  }
  return checker
}

const checkRule = (rule: Rule): Violation[] => {
  const violations: Violation[] = []
  const exempt = (rule.except ?? []).map((glob) => new Glob(glob))

  for (const glob of rule.scope) {
    for (const relPath of new Glob(glob).scanSync({ cwd: ROOT })) {
      if (exempt.some((pattern) => pattern.match(relPath))) continue
      const file = resolve(ROOT, relPath)
      const content = readFileSync(file, 'utf-8')

      if (rule.check) {
        for (const { line, hit } of checkerOf(rule)(relPath, content)) {
          violations.push({ rule, file: relPath, line, hit })
        }
        continue
      }

      const { text, offset } = regionOf(content, rule.region ?? 'all')
      text.split('\n').forEach((line, index) => {
        const trimmed = line.trim()
        if (trimmed.startsWith('//') || trimmed.startsWith('*') || trimmed.startsWith('<!--'))
          return
        for (const forbid of rule.forbid ?? []) {
          const hit = hitOf(line, forbid)
          if (hit !== null) {
            violations.push({ rule, file: relative(ROOT, file), line: offset + index + 1, hit })
          }
        }
      })
    }
  }

  return violations
}

const loadRules = (file: string): Rule[] => {
  const { rules } = parse(readFileSync(resolve(import.meta.dir, file), 'utf-8')) as {
    rules: Rule[]
  }
  return rules
}

const rules = [...loadRules('rules.yaml'), ...loadRules('structure-rules.yaml')]
const violations = rules.flatMap(checkRule)

if (violations.length === 0) {
  console.warn(`✔ conventions — ${rules.length} règles, aucune violation`)
  process.exit(0)
}

const byRule = Map.groupBy(violations, (violation) => violation.rule.name)
for (const [name, hits] of byRule) {
  const rule = hits[0]!.rule
  console.error(`\n✖ ${name} — ${hits.length} violation(s)`)
  console.error(`  ${rule.message.trim()}`)
  console.error(`  Réf : ${rule.readme}`)
  for (const hit of hits) console.error(`    ${hit.file}:${hit.line} → ${hit.hit}`)
}

console.error(`\n${violations.length} violation(s) sur ${rules.length} règles.`)
process.exit(1)

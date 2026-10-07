import { readFileSync } from 'node:fs'
import { join } from 'node:path'

import { describe, expect, it } from 'vitest'
import { parse } from 'yaml'

// Chemin relatif et non l'alias `#shared` : ce test doit tourner sans le runtime Nuxt, qui est le
// seul à résoudre les alias.
import { AVAILABLE_LOCALES, FALLBACK_LOCALE, SUPPORTED_LOCALES } from '../../../shared/utils/locale'

import type { LocaleCode } from '../../../shared/types/i18n'

/**
 * Les trois langues doivent porter le MÊME jeu de clés, avec les mêmes paramètres et le même nombre
 * de formes plurielles.
 *
 * Ce test existe parce que rien ne voyait la dérive : le français est la langue de repli, donc une
 * clé absente n'affiche pas un fragment technique mais **le texte français**. L'écran reste lisible
 * et l'anomalie ne se signale nulle part — sauf à un lecteur anglophone qui trouve une phrase
 * française au milieu de sa page. Trois tâches d'affilée ont pu creuser l'écart sans que personne
 * le remarque.
 *
 * Il regarde dans LES DEUX SENS : ne vérifier que ce qui manque laisserait s'accumuler des clés
 * mortes, qui survivent aux écrans les appelant et font croire à une couverture inexistante.
 *
 * Il lit les fichiers directement, sans démarrer l'application, et tire la liste des langues du
 * registre unique plutôt que de la réécrire — une langue ajoutée là est couverte ici sans rien
 * toucher.
 */

const LOCALES_DIR = join(import.meta.dirname, '.')

type Entries = Map<string, string>

/** Aplatit l'arbre YAML en chemins pointés, seule forme comparable entre deux fichiers. */
const flatten = (node: unknown, prefix = '', into: Entries = new Map()): Entries => {
  if (null === node || 'object' !== typeof node) {
    into.set(prefix, String(node))

    return into
  }

  for (const [key, value] of Object.entries(node as Record<string, unknown>)) {
    flatten(value, prefix ? `${prefix}.${key}` : key, into)
  }

  return into
}

const load = (code: LocaleCode): Entries =>
  flatten(parse(readFileSync(join(LOCALES_DIR, AVAILABLE_LOCALES[code].file), 'utf-8')))

/**
 * `{count}`, `{name}`… — une clé qui perd un paramètre affiche une phrase amputée, en silence.
 *
 * On compare l'ENSEMBLE des paramètres, pas leur nombre d'occurrences : une branche de pluriel peut
 * légitimement écrire le nombre en toutes lettres — l'arabe dit « ملف واحد » (un seul fichier)
 * plutôt que « 1 fichier », et compter les occurrences punirait cette traduction juste.
 */
const placeholders = (text: string): string[] =>
  [...new Set([...text.matchAll(/\{(\w+)\}/g)].flatMap((match) => match[1] ?? []))].sort()

/** Les branches d'un pluriel, séparées par `|` — deux en français, donc deux ailleurs. */
const pluralForms = (text: string): number => text.split('|').length

const CODES: readonly LocaleCode[] = SUPPORTED_LOCALES.map(({ code }) => code)
const OTHERS = CODES.filter((code) => code !== FALLBACK_LOCALE)
// Chaque fichier n'est lu et aplati QU'UNE FOIS : le relire par clé rendait la suite dix mille
// fois plus lente pour le même verdict.
const reference = load(FALLBACK_LOCALE)
const translations = new Map(OTHERS.map((code) => [code, load(code)]))
const entriesOf = (code: LocaleCode): Entries => translations.get(code) as Entries

describe('parité des fichiers de langue', () => {
  it('déclare bien trois langues, dont le français en référence', () => {
    expect(CODES).toEqual(['fr', 'en', 'ar'])
    expect(FALLBACK_LOCALE).toBe('fr')
  })

  it.each(OTHERS)('%s ne laisse aucune clé française sans traduction', (code) => {
    const missing = [...reference.keys()].filter((key) => !entriesOf(code).has(key))

    // La liste NOMMÉE, pas un décompte : « 3 clés manquent » se corrige à l'aveugle.
    expect(missing).toEqual([])
  })

  it.each(OTHERS)('%s ne garde aucune clé que le français n’a plus', (code) => {
    const dead = [...entriesOf(code).keys()].filter((key) => !reference.has(key))

    expect(dead).toEqual([])
  })

  it.each(OTHERS)('%s conserve les paramètres nommés de chaque clé', (code) => {
    const entries = entriesOf(code)
    const drifted = [...reference].flatMap(([key, french]) => {
      const translated = entries.get(key)
      const expected = placeholders(french)

      return undefined === translated || String(placeholders(translated)) === String(expected)
        ? []
        : [
            `${key} — attendu ${JSON.stringify(expected)}, trouvé ${JSON.stringify(placeholders(translated))}`,
          ]
    })

    expect(drifted).toEqual([])
  })

  it.each(OTHERS)('%s conserve le nombre de formes plurielles de chaque clé', (code) => {
    const entries = entriesOf(code)
    const drifted = [...reference].flatMap(([key, french]) => {
      const translated = entries.get(key)
      const expected = pluralForms(french)

      return undefined === translated || pluralForms(translated) === expected
        ? []
        : [`${key} — attendu ${expected} forme(s), trouvé ${pluralForms(translated)}`]
    })

    expect(drifted).toEqual([])
  })
})

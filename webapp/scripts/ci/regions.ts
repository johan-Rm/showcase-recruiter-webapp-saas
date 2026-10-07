import { parse } from 'vue/compiler-sfc'

/**
 * Isolation de la région d'un fichier ciblée par une règle — `script`, `template`, ou tout.
 *
 * Séparé de `verify-conventions.ts` pour être **testable** : le vérificateur s'exécute au niveau
 * module (il lit `rules.yaml` et appelle `process.exit`) et importe `Glob` depuis `bun`. L'importer
 * depuis un test lancerait la vérification entière. Ici, une fonction pure et rien d'autre — même
 * partage que `checkers.ts`.
 */

/** Région ciblée, et le décalage de lignes qui permet de rapporter la bonne ligne du fichier. */
export interface Region {
  text: string
  offset: number
}

export type RegionName = 'script' | 'template' | 'all'

export const regionOf = (content: string, region: RegionName): Region => {
  if (region === 'all') {
    return { text: content, offset: 0 }
  }

  // L'AST du SFC, et non une expression régulière. Celle d'avant était NON GOURMANDE : elle
  // s'arrêtait à la première `</template>`, c'est-à-dire à celle du premier slot nommé. Sur une
  // page qui en porte un tôt, elle lisait 19 % du fichier et rendait vert — deux règles étaient
  // aveugles sans que rien ne le signale.
  //
  // Un `[\s\S]*` gourmand aurait déplacé le problème sur le bloc `script`. Le compilateur, lui,
  // connaît l'imbrication : même dépendance que `checkers.ts`, et pour la même raison.
  const { descriptor } = parse(content)

  const block =
    region === 'script' ? (descriptor.scriptSetup ?? descriptor.script) : descriptor.template

  if (!block) {
    return { text: '', offset: 0 }
  }

  // `loc.start.line` est 1-indexée et pointe la ligne où le CONTENU commence ; l'appelant ajoute
  // ensuite `index + 1`. D'où le `- 1`, sans quoi chaque violation est rapportée une ligne trop
  // bas — ce que l'ancienne arithmétique faisait, silencieusement.
  return { text: block.content, offset: block.loc.start.line - 1 }
}

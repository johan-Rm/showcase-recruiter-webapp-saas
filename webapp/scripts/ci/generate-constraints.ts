/**
 * Génère `shared/types/organization-constraints.ts` depuis l'OpenAPI.
 *
 * POURQUOI — `openapi-typescript` (api.ts) conserve les enums mais JETTE `maxLength` / `minLength`. Le
 * front en a pourtant besoin pour valider AVANT l'envoi sans recopier les bornes à la main. On les
 * extrait donc du MÊME contrat ; le gate `quality-contracts-check` (git diff --exit-code) casse si
 * l'API change une borne sans régénérer — anti-dérive garanti.
 *
 * RÉUTILISATION GÉNÉRIQUE — pour exposer les bornes d'un AUTRE Input du contrat, ajouter UNE entrée à
 * `SCHEMAS` (nom du schéma OpenAPI + nom de l'export généré). Rien d'autre : la constante apparaît dans
 * le fichier généré, prête à être consommée par la validation front, et le gate la surveille. Les
 * champs sans borne de longueur (enum seul, etc.) sont ignorés — les enums vivent déjà dans api.ts.
 *
 * Sortie déterministe (ordre des schémas, puis des propriétés du schéma) pour un diff stable.
 */
import { readFileSync, writeFileSync } from 'node:fs'

const SOURCE = process.env.SHOWCASE_RECRUITER_WEBAPP_SAAS_OPENAPI_SOURCE ?? '../api/openapi.json'
const OUTPUT = 'shared/types/organization-constraints.ts'

/** Les Input du contrat dont on veut les bornes côté front. Une ligne = un Input exposé. */
const SCHEMAS: ReadonlyArray<{ schema: string; exportName: string }> = [
  { schema: 'Organization.OrganizationInput', exportName: 'ORGANIZATION_INPUT_CONSTRAINTS' },
]

interface Constraint {
  minLength?: number
  maxLength?: number
}

const openapi = JSON.parse(readFileSync(SOURCE, 'utf8')) as {
  components?: { schemas?: Record<string, { properties?: Record<string, Constraint> }> }
}

const constant = ({ schema, exportName }: (typeof SCHEMAS)[number]): string => {
  const properties = openapi.components?.schemas?.[schema]?.properties
  if (!properties) {
    throw new Error(`Schéma "${schema}" introuvable dans ${SOURCE}`)
  }

  const lines: string[] = []
  for (const [name, property] of Object.entries(properties)) {
    const parts: string[] = []
    if ('number' === typeof property.minLength) {
      parts.push(`minLength: ${property.minLength}`)
    }
    if ('number' === typeof property.maxLength) {
      parts.push(`maxLength: ${property.maxLength}`)
    }
    if (parts.length > 0) {
      lines.push(`  ${name}: { ${parts.join(', ')} },`)
    }
  }

  return `export const ${exportName} = {\n${lines.join('\n')}\n} as const satisfies Record<string, FieldConstraint>\n`
}

const content = `/**
 * GÉNÉRÉ depuis l'OpenAPI par scripts/ci/generate-constraints.ts — NE PAS ÉDITER.
 * Régénérer avec les types : bun run quality-generate-schemas. Gate anti-dérive : quality-contracts-check.
 *
 * Bornes de longueur du contrat, que openapi-typescript ne garde pas dans api.ts. Le front les consomme
 * pour la validation AVANT l'envoi ; le serveur (Symfony Validator) reste l'arbitre.
 */

export interface FieldConstraint {
  readonly minLength?: number
  readonly maxLength?: number
}

${SCHEMAS.map(constant).join('\n')}`

writeFileSync(OUTPUT, content)
process.stdout.write(`✔ ${OUTPUT} — ${SCHEMAS.length} schéma(s) depuis ${SOURCE}\n`)

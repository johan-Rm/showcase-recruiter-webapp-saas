/**
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

export const ORGANIZATION_INPUT_CONSTRAINTS = {
  givenName: { maxLength: 100 },
  familyName: { maxLength: 100 },
  name: { maxLength: 255 },
  legalName: { maxLength: 255 },
  taxId: { maxLength: 50 },
  country: { minLength: 2, maxLength: 2 },
  city: { maxLength: 100 },
  website: { maxLength: 255 },
  jobTitle: { maxLength: 255 },
  acquisitionChannelDetail: { maxLength: 255 },
  acquisitionReferrer: { maxLength: 255 },
} as const satisfies Record<string, FieldConstraint>

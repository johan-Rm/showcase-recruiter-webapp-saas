import Anthropic from '@anthropic-ai/sdk'
import { z } from 'zod'

import {
  AVAILABILITY_VALUES,
  PROFICIENCY_LEVELS,
  RETURN_HORIZON_VALUES,
} from '#shared/types/ai-search'

import type { SearchCriteria } from '#shared/types/ai-search'

/**
 * Traduit la demande libre du recruteur en critères structurés, proxifiée : la clé Claude reste
 * côté serveur, comme la clé Places (cf. server/api/places/*).
 *
 * INVARIANT du cadrage : la phrase du recruteur est la SEULE donnée envoyée au modèle — aucun profil
 * du vivier ne transite. Le filtrage restera côté serveur (Postgres FTS), sur des critères, jamais
 * sur des profils confiés au LLM.
 *
 * Sans clé configurée (le cas en dev) : erreur `not_configured` explicite plutôt qu'un appel qui
 * planterait — le client bascule alors son message et la recherche par critères reste, elle, offerte.
 */

const bodySchema = z.object({
  query: z.string().trim().min(1).max(500),
})

// Sortie structurée (json_schema) : le modèle est CONTRAINT de rendre exactement cette forme.
// Nullabilité en `anyOf` (et non `type: [..., 'null']`) — forme la plus robuste pour ce mode.
const CRITERIA_SCHEMA = {
  type: 'object',
  properties: {
    keywords: {
      type: 'array',
      description:
        'Groupes de mots-clés métier/compétence/secteur. ET entre les groupes, OU entre les variantes d’un groupe.',
      items: {
        type: 'object',
        properties: {
          terms: {
            type: 'array',
            description: 'Variantes équivalentes du même concept, en français ET en anglais.',
            items: { type: 'string' },
          },
        },
        required: ['terms'],
        additionalProperties: false,
      },
    },
    country: {
      description:
        'Pays de résidence actuel, code ISO 3166-1 alpha-2 (FR, BE, MA…). null si non mentionné.',
      anyOf: [{ type: 'string' }, { type: 'null' }],
    },
    city: {
      description: 'Ville de résidence actuelle. null si non mentionnée.',
      anyOf: [{ type: 'string' }, { type: 'null' }],
    },
    languages: {
      type: 'array',
      description: 'Langues exigées par le recruteur (ET entre elles).',
      items: {
        type: 'object',
        properties: {
          language: { type: 'string', description: 'Code ISO 639-1 (fr, en, ar, es, nl, de…).' },
          minProficiency: {
            description: 'Niveau minimum exigé, null si non précisé.',
            anyOf: [{ type: 'string', enum: [...PROFICIENCY_LEVELS] }, { type: 'null' }],
          },
        },
        required: ['language', 'minProficiency'],
        additionalProperties: false,
      },
    },
    availability: {
      description:
        'Disponibilité (préavis) : IMMEDIATE (tout de suite), NOTICE_1M (préavis 1 mois), NOTICE_3M (préavis 3 mois). null si non mentionnée.',
      anyOf: [{ type: 'string', enum: [...AVAILABILITY_VALUES] }, { type: 'null' }],
    },
    returnHorizon: {
      description:
        'Horizon de retour au Maroc : M3 (sous 3 mois), M6 (sous 6 mois), Y1 (dans l’année), THINKING (en réflexion). null si non mentionné.',
      anyOf: [{ type: 'string', enum: [...RETURN_HORIZON_VALUES] }, { type: 'null' }],
    },
    geoMobility: {
      description: 'true si le recruteur exige une mobilité géographique. null si non mentionné.',
      anyOf: [{ type: 'boolean' }, { type: 'null' }],
    },
  },
  required: [
    'keywords',
    'country',
    'city',
    'languages',
    'availability',
    'returnHorizon',
    'geoMobility',
  ],
  additionalProperties: false,
} as const

const SYSTEM_PROMPT = `Tu traduis la demande d'un recruteur en critères de recherche de candidats pour showcase-recruiter-webapp-saas,
un vivier de talents de la diaspora marocaine en Europe envisageant un retour au Maroc.

Règles impératives :
- Ne déduis un critère que s'il est explicite ou fortement implicite dans la demande. Sinon laisse null (ou tableau vide).
- Pour keywords : un groupe par concept distinct (métier, compétence, secteur, séniorité). Dans chaque groupe,
  fournis 2 à 5 variantes équivalentes en français ET en anglais (les profils sont bilingues) — ex. pour
  « commercial » : ["commercial", "sales", "business development", "business developer"].
  N'invente pas de concepts absents de la demande.
- Les mots-clés sont matchés en "contient", insensible à la casse et aux accents, sur : accroche, résumé,
  compétences, intitulés de poste, entreprises, formations, secteur/fonction recherchés, ville.
  Préfère des termes courts et génériques à des expressions longues.
- country/city = lieu de résidence ACTUEL du candidat. « au Maroc » → country MA ; « en France » → FR ; « en Belgique » → BE.
- languages : uniquement si une langue est exigée. « bilingue » → NATIVE_OR_BILINGUAL, « courant » → FULL_PROFESSIONAL,
  « professionnel » → PROFESSIONAL_WORKING. Une langue exigée ne va PAS aussi dans keywords.
- availability = préavis professionnel ; returnHorizon = horizon du retour au Maroc. Deux notions distinctes :
  « dispo immédiatement » → availability IMMEDIATE ; « rentre au Maroc dans l'année » → returnHorizon Y1.
- geoMobility : true seulement si la mobilité géographique est explicitement demandée.
- Le salaire n'est PAS un critère filtrable ici : ignore-le (ne le transforme pas en mots-clés).`

export default defineEventHandler(async (event): Promise<SearchCriteria> => {
  const { query } = await readValidatedBody(event, bodySchema.parse)

  const apiKey = useRuntimeConfig(event).anthropicApiKey
  if (!apiKey) {
    throw createError({
      statusCode: 503,
      statusMessage: 'Recherche assistée non configurée',
      data: { reason: 'not_configured' },
    })
  }

  const client = new Anthropic({ apiKey })

  const response = await client.messages.create({
    model: 'claude-opus-4-8',
    max_tokens: 1024,
    system: SYSTEM_PROMPT,
    output_config: { format: { type: 'json_schema', schema: CRITERIA_SCHEMA } },
    messages: [{ role: 'user', content: query }],
  })

  // Une sortie tronquée (`max_tokens`) rendrait un JSON coupé, donc invalide : on le signale au lieu
  // de laisser `JSON.parse` échouer sur une forme à demi valide.
  if ('refusal' === response.stop_reason || 'max_tokens' === response.stop_reason) {
    throw createError({ statusCode: 502, statusMessage: 'Traduction IA incomplète' })
  }

  const textBlock = response.content.find((block) => 'text' === block.type)
  if (!textBlock || 'text' !== textBlock.type) {
    throw createError({
      statusCode: 502,
      statusMessage: 'Réponse IA inattendue (aucun bloc texte)',
    })
  }

  return JSON.parse(textBlock.text) as SearchCriteria
})

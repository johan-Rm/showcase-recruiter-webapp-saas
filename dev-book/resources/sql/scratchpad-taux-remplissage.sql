-- Taux de remplissage des champs importés de Jarvi (source de vérité = candidate_jarvi).
-- Ne PAS se fier à hasEmail/hasPhone de l'API : ils lisent person.* que l'import ne remplit pas.
-- Usage : docker compose -f docker-compose.dev.yml exec -T db psql -U showcase-recruiter-webapp-saas -d showcase-recruiter-webapp-saas -f - < ce_fichier.sql

WITH cj AS (
  SELECT
    p.given_name, p.family_name,
    cj.contacts::jsonb          AS contacts,
    cj.resumes::jsonb           AS resumes,
    cj.work_experiences::jsonb  AS work_experiences,
    cj.raw_payload::jsonb       AS raw
  FROM candidate_jarvi cj
  JOIN candidate c ON c.id = cj.candidate_id
  JOIN person p    ON p.id = c.person_id
),
flags AS (
  SELECT
    (given_name  IS NOT NULL AND btrim(given_name)  <> '') AS f_prenom,
    (family_name IS NOT NULL AND btrim(family_name) <> '') AS f_nom,
    EXISTS (SELECT 1 FROM jsonb_array_elements(contacts) e
            WHERE e->>'type'='email' AND btrim(COALESCE(e->>'value','')) <> '') AS f_email,
    EXISTS (SELECT 1 FROM jsonb_array_elements(contacts) e
            WHERE e->>'type'='phone' AND btrim(COALESCE(e->>'value','')) <> '') AS f_phone,
    (btrim(COALESCE(raw->>'profileUrl','')) <> '')                      AS f_linkedin,
    (jsonb_array_length(COALESCE(work_experiences,'[]'::jsonb)) > 0)    AS f_emploi,
    (jsonb_array_length(COALESCE(resumes,'[]'::jsonb)) > 0)             AS f_cv,
    EXISTS (SELECT 1 FROM jsonb_array_elements(COALESCE(raw->'fieldsValues','[]'::jsonb)) e
            WHERE e->'field'->>'name' = 'Récap Entretien'
              AND btrim(COALESCE(e->>'value','')) NOT IN ('','null'))   AS f_recap
  FROM cj
)
SELECT champ, rempli, total, round(100.0*rempli/total,1) AS pct FROM (
  SELECT '01 Prénom'      AS champ, count(*) FILTER (WHERE f_prenom)   AS rempli, count(*) AS total FROM flags
  UNION ALL SELECT '02 Nom',        count(*) FILTER (WHERE f_nom),      count(*) FROM flags
  UNION ALL SELECT '03 E-mail',     count(*) FILTER (WHERE f_email),    count(*) FROM flags
  UNION ALL SELECT '04 Téléphone',  count(*) FILTER (WHERE f_phone),    count(*) FROM flags
  UNION ALL SELECT '05 LinkedIn',   count(*) FILTER (WHERE f_linkedin), count(*) FROM flags
  UNION ALL SELECT '06 Employeurs', count(*) FILTER (WHERE f_emploi),   count(*) FROM flags
  UNION ALL SELECT '07 CV',         count(*) FILTER (WHERE f_cv),       count(*) FROM flags
  UNION ALL SELECT '08 CR entretien', count(*) FILTER (WHERE f_recap),  count(*) FROM flags
) s ORDER BY champ;
-- Rappel : WhatsApp et Photo ne sont pas ingérés (absents de la query GraphQL Jarvi) → non mesurables ici.

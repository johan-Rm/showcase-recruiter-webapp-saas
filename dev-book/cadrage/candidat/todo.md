# showcase-recruiter-webapp-saas — TODO / Backlog **Espace Candidat**

> Backlog **spécifique au périmètre candidat** (décisions ouvertes + cadrage à compléter).
> Les sujets transverses (billing, notification multi-canal, RGPD global, socle technique) vivent
> dans le backlog central [../todo.md](../todo.md). Vue d'ensemble : [vue_d_ensemble.md](./vue_d_ensemble.md).

## 🔵 Décisions ouvertes (à trancher)

- [ ] **Critères d'un profil candidat « complet »** : définir les conditions qui débloquent les
      fonctions après l'onboarding (différenciants minimum : projet de retour, disponibilité,
      prétentions, présence Maroc ? consentement/visibilité ?). Conditionne l'UX d'onboarding et le
      gating des actions self-service. → [vue_d_ensemble.md](./vue_d_ensemble.md) §2.
- [ ] **Paiement candidat** (options premium) : candidat = **Stripe customer** (récurrent) ou
      **paiement one-shot** ? → transverse billing, cf. [../todo.md](../todo.md).
- [ ] 🔭 **Options payantes candidat** (`CandidateOption`, **décision reportée en V2**) : deux formules
      envisagées — (a) **mise en avant homepage** pour une durée (X jours/semaines, placement vitrine),
      OU (b) **diffusion sectorielle** du profil complet + compte rendu (push actif vers les recruteurs
      du secteur). Dépend du billing (Stripe/CMI) et du produit. → [vue_d_ensemble.md](./vue_d_ensemble.md) §1.

## ✅ Décisions actées

- ✅ **Exposition du compte rendu d'entretien** (2026-07-02) : **recruteur uniquement**, jamais côté
      candidat. Base légale + rétention RGPD à formaliser avant ingestion prod.
      → [api/profil-jarvi-reference.md](./api/profil-jarvi-reference.md).
- ✅ **Ingestion du compte rendu Jarvi** (2026-07-01) : ingéré comme payload sensible maître Jarvi dans
      `candidate_jarvi.rawPayload`, non normalisé, distinct du compte rendu Star showcase-recruiter-webapp-saas.
      → [api/profil-jarvi-reference.md](./api/profil-jarvi-reference.md).

## 🟠 Cadrage à compléter

- [ ] **Consentement & RGPD candidat** : consentement explicite avant exposition aux recruteurs,
      horodatage visibilité/consentement, base légale et rétention. Volet candidat du chantier RGPD
      global — cf. item **RGPD** de [../todo.md](../todo.md) et **tâche 018**.
      → [ui/parcours_utilisateur_candidat.md](./ui/parcours_utilisateur_candidat.md).
- [ ] **CV — accès binaire** ✅ résolu (2026-07-02) : servi via l'URL du storage Jarvi
      (`…/v1/files/<fileId>`), **pas de copie** showcase-recruiter-webapp-saas. Reste à valider le **mode d'auth** de l'URL
      (public vs jeton) → éventuel proxy API. → [api/profil-jarvi-reference.md](./api/profil-jarvi-reference.md).

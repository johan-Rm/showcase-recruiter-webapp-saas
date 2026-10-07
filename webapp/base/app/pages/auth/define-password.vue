<script setup lang="ts">
/**
 * Définition du mot de passe (`/auth/define-password?token=…`) — chemin construit par l'API après
 * validation de l'entreprise par un administrateur.
 *
 * Vit dans le socle : le lien part vers le host du destinataire, et la mécanique resservira à tout
 * rôle dont le compte est créé côté admin. Ce chemin n'ouvre pas de session — succès ⇒ login.
 */
definePageMeta({ layout: 'auth' })

const route = useRoute()
const { localePath } = useLocale()
const { fetch: refreshSession } = useUserSession()
const { setupPassword, pending, expired, error, fieldErrors } = usePasswordSetup()

const token = String(route.query.token ?? '')

const password = ref('')
const confirmation = ref('')
// L'œil ne vaut QUE pour le mot de passe. La confirmation reste masquée : la retaper à l'aveugle est
// justement ce qui attrape la faute de frappe — l'y révéler la viderait de son sens.
const passwordVisible = ref(false)
const mismatch = ref(false)

// Checklist vivante : chaque règle vire au vert à mesure que le mot de passe la satisfait. `satisfied`
// verrouille l'envoi tant que le compte n'est pas solide — l'API reste juge, ceci n'est que le guide.
const { rules, satisfied } = usePasswordPolicy(password)

// État affiché DANS chaque champ (coche / alerte), à droite. `empty` = rien : on ne juge pas un champ
// vierge. Le mot de passe suit la politique ; la confirmation suit la concordance, quelle que soit la
// force du mot de passe (l'envoi reste bloqué par `satisfied` de toute façon).
type FieldState = 'empty' | 'valid' | 'invalid'

const passwordState = computed<FieldState>(() => {
  if ('' === password.value) {
    return 'empty'
  }

  return satisfied.value ? 'valid' : 'invalid'
})

const confirmationState = computed<FieldState>(() => {
  if ('' === confirmation.value) {
    return 'empty'
  }

  return confirmation.value === password.value ? 'valid' : 'invalid'
})

// Le mot de passe posé ouvre la session (le BFF rejoue un login) : on rafraîchit la session côté
// client puis on dépose l'utilisateur dans son espace — pas d'étape de connexion manuelle.
const submit = async () => {
  mismatch.value = password.value !== confirmation.value
  if (mismatch.value) {
    return
  }

  if (await setupPassword(token, password.value)) {
    await refreshSession()
    await navigateTo(localePath('/'), { replace: true })
  }
}
</script>

<template>
  <div>
    <template v-if="!token || expired">
      <h1 class="text-h2 font-bold">{{ $t('inscription.password.invalidTitle') }}</h1>
      <p class="mt-2 text-small text-muted">{{ $t('inscription.password.invalidMessage') }}</p>
      <UiButton :to="localePath('/login')" class="mt-8 w-full justify-center">
        {{ $t('inscription.link.restart') }}
      </UiButton>
    </template>

    <template v-else>
      <h1 class="text-h2 font-bold">{{ $t('inscription.password.title') }}</h1>
      <p class="mt-2 text-small text-muted">{{ $t('inscription.password.message') }}</p>

      <form class="mt-8 flex flex-col gap-4" @submit.prevent="submit">
        <UiField
          id="password"
          :label="$t('inscription.password.field')"
          :error="fieldErrors.password"
        >
          <div class="relative">
            <!-- `pe`/`end` et pas `pr`/`right` : en arabe, les commandes basculent du bon côté. -->
            <input
              id="password"
              v-model="password"
              :type="passwordVisible ? 'text' : 'password'"
              class="w-full pe-20"
              name="new-password"
              autocomplete="new-password"
              :minlength="PASSWORD_MIN_LENGTH"
              required
            />

            <!-- État + œil regroupés à droite : un seul conteneur les aligne, l'icône ne chevauche
                 jamais le bouton. -->
            <div class="absolute inset-y-1 end-1 flex items-center gap-1">
              <Icon
                v-if="'empty' !== passwordState"
                :name="'valid' === passwordState ? 'lucide:circle-check' : 'lucide:circle-alert'"
                class="pointer-events-none"
                :class="'valid' === passwordState ? 'text-accent' : 'text-alert'"
                aria-hidden="true"
              />

              <button
                type="button"
                class="flex h-full w-9 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:text-text focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                :aria-label="passwordVisible ? $t('auth.hidePassword') : $t('auth.showPassword')"
                :aria-pressed="passwordVisible"
                @click="passwordVisible = !passwordVisible"
              >
                <Icon
                  :name="passwordVisible ? 'lucide:eye-off' : 'lucide:eye'"
                  aria-hidden="true"
                />
              </button>
            </div>
          </div>
        </UiField>

        <!-- Checklist vivante : elle GUIDE, elle ne juge pas (l'API tranche). Affichée d'emblée pour
             annoncer l'exigence, chaque règle passe au vert dès qu'elle est satisfaite. -->
        <div class="define-password__rules">
          <p class="define-password__rules-title">{{ $t('auth.passwordRulesTitle') }}</p>
          <ul>
            <li
              v-for="rule in rules"
              :key="rule.key"
              class="define-password__rule"
              :class="{ 'define-password__rule--met': rule.met }"
            >
              <Icon
                :name="rule.met ? 'lucide:circle-check' : 'lucide:circle'"
                class="define-password__rule-icon"
                aria-hidden="true"
              />
              {{ $t(`auth.passwordRule.${rule.key}`, { n: PASSWORD_MIN_LENGTH }) }}
            </li>
          </ul>
        </div>

        <UiField
          id="confirmation"
          :label="$t('inscription.password.confirmField')"
          :error="mismatch ? $t('inscription.password.mismatch') : undefined"
        >
          <div class="relative">
            <!-- Toujours masquée, pas d'œil : la confirmation ne sert QUE si on la retape à l'aveugle. -->
            <input
              id="confirmation"
              v-model="confirmation"
              type="password"
              class="w-full pe-12"
              name="new-password"
              autocomplete="new-password"
              :minlength="PASSWORD_MIN_LENGTH"
              required
            />

            <!-- Seul l'état de concordance, à droite : coche si les deux correspondent, alerte sinon. -->
            <Icon
              v-if="'empty' !== confirmationState"
              :name="'valid' === confirmationState ? 'lucide:circle-check' : 'lucide:circle-alert'"
              class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2"
              :class="'valid' === confirmationState ? 'text-accent' : 'text-alert'"
              aria-hidden="true"
            />
          </div>
        </UiField>

        <p v-if="error" class="text-small text-alert" role="alert">{{ $t(error) }}</p>

        <!-- Verrouillé tant que les règles ne sont pas toutes vertes : on ne laisse pas soumettre un
             mot de passe que l'API refusera. La concordance, elle, se vérifie à l'envoi. -->
        <UiButton
          type="submit"
          :disabled="pending || !satisfied"
          class="mt-2 w-full justify-center"
        >
          {{ $t('inscription.password.submit') }}
        </UiButton>
      </form>
    </template>
  </div>
</template>

<style scoped>
.define-password__rules-title {
  font: 600 11px / 1.4 var(--font-ui);
  color: var(--muted);
  margin-bottom: 8px;
}

.define-password__rule {
  display: flex;
  align-items: center;
  gap: 8px;
  font: 400 13px / 1.5 var(--font-ui);
  /* Non satisfaite : effacée, c'est un objectif pas une erreur — surtout PAS en rouge d'alerte. */
  color: var(--muted);
  transition: color var(--transition-fast);
}

/* Satisfaite : l'accent, le même vert que le reste. La couleur seule ne porte pas l'info — l'icône
   passe de cercle vide à coche, lisible sans distinguer les teintes. */
.define-password__rule--met {
  color: var(--accent);
}

.define-password__rule-icon {
  flex: none;
  width: 15px;
  height: 15px;
}
</style>

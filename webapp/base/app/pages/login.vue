<script setup lang="ts">
// Connexion ET première étape d'inscription vivent sur cette page : l'écran scindé doit rester
// monté d'un état à l'autre pour que le panneau de marque glisse au lieu de se recharger.
//
// L'état est porté par l'URL et non par un ref : `/login?inscription` reste partageable, et le
// garde de l'étape 2 y renvoie le déclarant sans mot de passe (cf. inscription/entreprise.vue).
// Vue Router ne remonte pas le composant sur un changement de query — d'où la persistance.
definePageMeta({ layout: 'auth' })

const { localePath } = useLocale()
const { available: hasRegistration, registering, registerPath } = useRegistration()

// L'écran d'auth est partagé par tous les espaces (base/) : le badge dit lequel, sans rien coder en
// dur. `space.entreprise` = « Espace entreprise », `space.admin` = « Administration »… Il lève
// l'ambiguïté qui ferait qu'un candidat pourrait se croire au bon endroit.
const space = useSpace()

const sentTo = ref<string | null>(null)

// Retour du social login : le BFF ramène ici avec `?social=…` (cf. server/api/auth/social/*). L'état
// vient de l'URL et non d'un ref parce que le navigateur revient d'un AUTRE site — un état local
// aurait été perdu en chemin.
const route = useRoute()
const socialOutcome = computed(() => String(route.query.social ?? ''))

// Un consentement refusé (`cancelled`) n'est pas une panne : l'utilisateur a dit non, on le ramène
// sans rien lui reprocher. Seul un vrai échec technique (`failed`) mérite un message.
const socialFailed = computed(() => 'failed' === socialOutcome.value)

// Une session close ramène ici avec `?session=expired` (cf. useSessionExpiry). Le dire évite que la
// personne cherche ce qu'elle a fait de travers, et l'état vient de l'URL pour la même raison que
// le retour du social login : la redirection est un chargement complet, un ref n'y survivrait pas.
const sessionExpired = computed(() => 'expired' === String(route.query.session ?? ''))

// Pilote le séparateur « ou » : sans fournisseur configuré il n'aurait rien à séparer.
const { providers: socialProviders } = useSocialLogin()
const hasSocial = computed(() => socialProviders.value.length > 0)

// L'écran « e-mail envoyé » est le même par les deux chemins (décision de cadrage). Le social login
// n'affiche pas l'adresse : elle vit dans l'id_token, que le BFF ne déchiffre pas pour l'exposer.
const showSent = computed(() => null !== sentTo.value || 'sent' === socialOutcome.value)
</script>

<template>
  <div>
    <!-- Hors de la Transition : l'identité de l'espace est CONSTANTE, elle ne doit pas s'animer
         quand le titre passe de connexion à inscription. -->
    <UiBadge class="mb-4 uppercase tracking-wide">{{ $t(`space.${space}`) }}</UiBadge>

    <Transition name="auth-pane" mode="out-in">
      <!-- Inscription — lien envoyé -->
      <div v-if="registering && showSent" key="sent">
        <h1 class="text-h2 font-bold">{{ $t('inscription.sent.title') }}</h1>
        <!-- L'adresse n'est nommée que si NOUS l'avons saisie. Par le social login elle vient de
             l'id_token, que le BFF ne déchiffre pas pour l'afficher — d'où la variante sans adresse. -->
        <p class="mt-2 text-small text-muted">
          {{
            sentTo
              ? $t('inscription.sent.message', { email: sentTo })
              : $t('inscription.sent.messageAnonymous')
          }}
        </p>
        <p class="mt-2 text-small text-muted">{{ $t('inscription.sent.action') }}</p>
        <p class="mt-4 text-small text-muted">{{ $t('inscription.sent.hint') }}</p>

        <!-- Repartir doit aussi effacer `?social=sent` : sans ça, l'URL rejouerait l'écran d'envoi
             et le bouton semblerait sans effet. `to` plutôt que `@click` — le retour au formulaire
             est un changement d'URL, pas un état local. -->
        <UiButton variant="secondary" class="mt-8" :to="registerPath" @click="sentTo = null">
          {{ $t('inscription.sent.retry') }}
        </UiButton>
      </div>

      <!-- Inscription — saisie de l'e-mail -->
      <div v-else-if="registering" key="register">
        <h1 class="text-h2 font-bold">{{ $t('inscription.start.title') }}</h1>
        <p class="mt-2 text-small text-muted">{{ $t('inscription.start.message') }}</p>

        <!-- Le social login échoue en revenant ICI, jamais sur une page d'erreur : le tunnel e-mail
             juste en dessous reste utilisable, l'inscription n'est donc pas bloquée. -->
        <p v-if="socialFailed" class="mt-4 text-small text-alert" role="alert">
          {{ $t('inscription.social.failed') }}
        </p>

        <!-- Les fournisseurs AVANT le champ e-mail : c'est le chemin le plus court quand il est
             disponible. Rien ne s'affiche si aucun n'est configuré — pas de séparateur orphelin. -->
        <RegistrationSocialButtons class="mt-8" />

        <div v-if="hasSocial" class="mt-6 flex items-center gap-4">
          <span class="h-px flex-1 bg-line" />
          <span class="text-small text-muted">{{ $t('inscription.social.or') }}</span>
          <span class="h-px flex-1 bg-line" />
        </div>

        <RegistrationEmailForm class="mt-6" @sent="(email) => (sentTo = email)" />

        <p class="mt-8 border-t border-line pt-6 text-center text-small text-muted">
          {{ $t('inscription.login.back') }}
          <NuxtLink class="font-semibold text-accent" :to="localePath('/login')">
            {{ $t('nav.login') }}
          </NuxtLink>
        </p>
      </div>

      <!-- Connexion -->
      <div v-else key="login">
        <h1 class="text-h2 font-bold">{{ $t('nav.login') }}</h1>
        <!-- Le libellé de l'espace en minuscule dans la phrase : « votre espace entreprise ». Le badge
             le montre déjà capitalisé au-dessus ; ici il se fond dans le texte. -->
        <p class="mt-2 text-small text-muted">
          {{ $t('auth.intro', { space: $t(`space.${space}`).toLowerCase() }) }}
        </p>

        <p v-if="sessionExpired" class="mt-4 text-small text-alert" role="alert">
          {{ $t('auth.sessionExpired') }}
        </p>

        <AuthLoginForm class="mt-8" />

        <p
          v-if="hasRegistration"
          class="mt-8 border-t border-line pt-6 text-center text-small text-muted"
        >
          {{ $t('inscription.login.prompt') }}
          <NuxtLink class="font-semibold text-accent" :to="registerPath">
            {{ $t('inscription.login.link') }}
          </NuxtLink>
        </p>
      </div>
    </Transition>
  </div>
</template>

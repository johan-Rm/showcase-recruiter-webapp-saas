<script setup lang="ts">
definePageMeta({ layout: 'inscription' })

const route = useRoute()
const { localePath } = useLocale()
const { loggedIn, user, fetch: refreshSession, clear: clearSession } = useUserSession()
const { consumeLink } = useMagicLink()

// Le magic link atterrit ICI : l'API pointe le lien sur cette page plutôt que sur un écran
// d'échange, qui montrait une coque d'application complète — et un bouton « Se déconnecter »
// cliquable — avant de jeter le déclarant dans un tout autre univers.
const token = String(route.query.token ?? '')

const consuming = ref(token !== '')
const expired = ref(false)

// Le garde ne se déclenche PAS quand un token attend d'être consommé : la session n'existe pas
// encore, c'est justement ce que le token va ouvrir. Sans session ni token, en revanche, il n'y a
// rien à faire ici — on renvoie au début du tunnel et non au formulaire de connexion : le déclarant
// n'a pas encore de mot de passe.
if (!loggedIn.value && '' === token) {
  await navigateTo(localePath({ path: '/login', query: { inscription: null } }))
}

/**
 * Plancher d'affichage du loader.
 *
 * L'échange du token prend ~360 ms : à cette vitesse, l'écran passe avant qu'on ait lu ce qu'il
 * annonce. Or il ne dit pas que ça charge — ça, le spinner le montre — il dit CE QUI VIENT APRÈS et
 * combien de temps ça prendra. C'est cette annonce qui a besoin d'exister, pas l'attente.
 *
 * On ne ralentit donc pas pour faire sérieux : on garde l'écran le temps qu'il soit lu, puis on
 * passe. Si l'échange traîne au-delà, le plancher ne coûte rien — il est déjà écoulé.
 */
const MIN_VISIBLE_MS = 1500

const held = (started: number): Promise<void> =>
  new Promise((resolve) =>
    setTimeout(resolve, Math.max(0, MIN_VISIBLE_MS - (Date.now() - started))),
  )

onMounted(async () => {
  if ('' === token) {
    return
  }

  // Le magic link PRIME sur tout : on déconnecte d'abord la session en cours, quelle qu'elle soit
  // (autre compte, membre déjà inscrit, session périmée), pour repartir d'un état propre. Rien ne
  // court-circuite la consommation du token — même si l'échange échoue, on reste déconnecté.
  await clearSession()

  const started = Date.now()

  if (!(await consumeLink(token))) {
    // Pas de plancher sur l'échec : personne n'a envie qu'on retienne une mauvaise nouvelle.
    expired.value = true
    consuming.value = false

    return
  }

  await refreshSession()
  await held(started)

  // Re-navigation sans le token : il disparaît de l'historique — une preuve à usage unique n'a rien
  // à y faire — et le middleware reprend la main. C'est LUI qui renverra vers son espace un membre
  // déjà inscrit : il connaît les rôles, inutile de refaire cet aiguillage ici.
  await navigateTo(localePath('/inscription/entreprise'), { replace: true })
  consuming.value = false
})

// L'écran de fin est un ÉTAT de cette page, pas une route. `OrganizationCreator` accorde
// ROLE_COMPANY_OWNER dès la création, et le middleware renvoie sur `/` quiconque porte ce rôle sur
// `/inscription/*` : naviguer vers un écran dédié ne fonctionnerait qu'aussi longtemps que la
// session client reste périmée. Un `refreshSession` ajouté plus tard suffirait à le faire
// disparaître sans prévenir.
const registered = ref(false)
</script>

<template>
  <Transition name="reveal" mode="out-in">
    <!-- Le loader occupe la coque de l'onboarding, pas un écran à lui : le monde ne change pas entre
         la validation du lien et le formulaire.
         Le sous-texte annonce CE QUI VIENT et sa durée : c'est lui qui justifie le plancher
         d'affichage — sans quoi l'écran passerait en 360 ms, avant d'avoir été lu. -->
    <div v-if="consuming" key="consuming" class="m-auto flex max-w-form flex-col gap-4">
      <p class="flex items-center gap-3">
        <UiSpinner />
        <span>{{ $t('inscription.link.checking') }}</span>
      </p>

      <p class="text-small text-muted">{{ $t('inscription.link.next') }}</p>
    </div>

    <section v-else-if="expired" key="expired" class="m-auto flex max-w-form flex-col gap-4">
      <h1 class="font-display text-h2 font-bold">{{ $t('inscription.link.invalidTitle') }}</h1>
      <p class="text-muted">{{ $t('inscription.link.invalidMessage') }}</p>

      <div class="mt-2">
        <UiButton :to="localePath({ path: '/login', query: { inscription: null } })">
          {{ $t('inscription.link.restart') }}
        </UiButton>
      </div>
    </section>

    <!-- L'écran ne fait plus patienter : l'accès est ouvert, il ne manque qu'un mot de passe. Il
         relaie donc vers l'e-mail — ce qui vient de partir, à quelle adresse, et quoi faire s'il ne
         se voit pas. Aucun lien vers la connexion : le déclarant n'a pas encore de mot de passe, l'y
         envoyer lui ferait tenter une porte fermée. -->
    <section v-else-if="registered" key="registered" class="m-auto flex max-w-form flex-col gap-5">
      <!-- Titre en deux temps : la nouvelle en grand, puis ce qu'il reste à faire en lime — c'est
           cette seconde ligne qui porte la couleur, parce que c'est elle qui appelle un geste. -->
      <div>
        <h1 class="font-display text-h1 font-bold tracking-tight">
          {{ $t('inscription.review.title') }}
        </h1>
        <p class="mt-2 text-h3 font-semibold text-accent">
          {{ $t('inscription.review.received') }}
        </p>
      </div>

      <!-- L'adresse est nommée : c'est ce qui permet de repérer une faute de frappe avant d'aller
           fouiller ses indésirables. -->
      <p class="text-muted">{{ $t('inscription.review.sent', { email: user?.email ?? '' }) }}</p>
      <p class="text-muted">{{ $t('inscription.review.why') }}</p>

      <p class="text-small text-muted">{{ $t('inscription.review.missing') }}</p>
    </section>

    <RegistrationCompanyForm v-else key="form" @created="registered = true" />
  </Transition>
</template>

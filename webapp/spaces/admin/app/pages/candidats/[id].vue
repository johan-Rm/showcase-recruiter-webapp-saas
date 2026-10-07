<script setup lang="ts">
// Fiche candidat admin — vue dédiée, profil complet NON masqué.
// Zone C : profil (coordonnées, pro, expériences, formations, projet Maroc). Zone D : exploitation.
definePageMeta({ layout: 'dashboard' })

const route = useRoute()
const { localePath } = useLocale()
const { candidate, error } = useCandidate(String(route.params.id))

const day = (iso: string | null): string => (iso ? iso.slice(0, 10) : '—')
const fullName = computed(() =>
  candidate.value ? `${candidate.value.givenName} ${candidate.value.familyName}` : '',
)
// Le titre de cet écran n'existe pas dans la navigation : c'est le nom de la personne, et il
// n'arrive qu'après le chargement. Le repli garde la barre haute occupée en attendant.
usePageTitle(() => fullName.value || '…')

const location = computed(() =>
  candidate.value
    ? [candidate.value.city, candidate.value.country].filter(Boolean).join(' · ')
    : '',
)
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <NuxtLink
          :to="localePath('/candidats')"
          class="inline-flex w-fit items-center gap-1 text-sm text-muted transition-colors hover:text-accent"
        >
          <Icon name="lucide:arrow-left" class="size-4" aria-hidden="true" />
          {{ $t('page.admin.candidats') }}
        </NuxtLink>
        <div class="flex items-center gap-element">
          <UiAvatar
            v-if="candidate"
            :given-name="candidate.givenName"
            :family-name="candidate.familyName"
            :photo-url="candidate.photoUrl"
            :size="64"
          />
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
              <h1 class="sr-only">{{ fullName || '…' }}</h1>
              <Icon
                v-if="candidate?.isStar"
                name="lucide:star"
                class="size-5 shrink-0 text-accent"
                aria-hidden="true"
              />
            </div>
            <p v-if="candidate?.headline" class="text-muted">{{ candidate.headline }}</p>
          </div>
        </div>
      </div>
    </template>

    <!-- C · profil -->
    <div class="flex flex-col gap-block">
      <p v-if="error" class="text-alert" role="alert">{{ $t('error.unexpected.message') }}</p>

      <template v-else-if="candidate">
        <!-- Coordonnées -->
        <section class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <h2 class="text-h3 font-semibold">{{ $t('candidate.contact') }}</h2>
          <div class="flex flex-col gap-element">
            <div class="flex items-center gap-2">
              <Icon name="lucide:map-pin" class="size-4 shrink-0 text-muted" aria-hidden="true" />
              <span>{{ location || '—' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Icon name="lucide:mail" class="size-4 shrink-0 text-muted" aria-hidden="true" />
              <span>{{ candidate.contactEmail || '—' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Icon name="lucide:phone" class="size-4 shrink-0 text-muted" aria-hidden="true" />
              <span>{{ candidate.phone || '—' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Icon
                name="lucide:message-circle"
                class="size-4 shrink-0 text-muted"
                aria-hidden="true"
              />
              <a
                v-if="candidate.whatsappNumber"
                :href="`https://wa.me/${candidate.whatsappNumber.replace('+', '')}`"
                target="_blank"
                rel="noopener noreferrer"
                class="text-accent"
              >
                {{ candidate.whatsappNumber }}
              </a>
              <span v-else>—</span>
            </div>
            <div class="flex items-center gap-2">
              <Icon name="lucide:linkedin" class="size-4 shrink-0 text-muted" aria-hidden="true" />
              <a
                v-if="candidate.linkedinUrl"
                :href="candidate.linkedinUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="break-all text-accent"
              >
                {{ candidate.linkedinUrl }}
              </a>
              <span v-else>—</span>
            </div>
          </div>
        </section>

        <!-- Profil professionnel -->
        <section class="flex flex-col gap-block rounded-lg bg-surface p-block">
          <h2 class="text-h3 font-semibold">{{ $t('candidate.profile') }}</h2>
          <p v-if="candidate.summary" class="whitespace-pre-line">{{ candidate.summary }}</p>
          <div v-if="candidate.skills.length" class="flex flex-col gap-2">
            <span class="text-sm text-muted">{{ $t('candidate.skills') }}</span>
            <div class="flex flex-wrap gap-1.5">
              <UBadge
                color="primary"
                variant="soft"
                v-for="skill in candidate.skills"
                :key="skill"
                >{{ skill }}</UBadge
              >
            </div>
          </div>
          <div v-if="candidate.languages.length" class="flex flex-col gap-2">
            <span class="text-sm text-muted">{{ $t('candidate.languages') }}</span>
            <ul class="flex list-none flex-wrap gap-x-block gap-y-1 p-0">
              <li v-for="(lang, i) in candidate.languages" :key="i">
                {{ lang.language
                }}<span v-if="lang.proficiency" class="text-muted"> · {{ lang.proficiency }}</span>
              </li>
            </ul>
          </div>
        </section>

        <!-- Expériences -->
        <section
          v-if="candidate.workExperiences.length"
          class="flex flex-col gap-element rounded-lg bg-surface p-block"
        >
          <h2 class="text-h3 font-semibold">{{ $t('candidate.experiences') }}</h2>
          <ul class="flex list-none flex-col gap-block p-0">
            <li v-for="(exp, i) in candidate.workExperiences" :key="i">
              <div class="font-semibold">{{ exp.title || '—' }}</div>
              <div class="text-sm text-muted">
                <template v-if="exp.companyName">{{ exp.companyName }}</template>
                <template v-if="exp.startDate">
                  · {{ day(exp.startDate)
                  }}<template v-if="exp.endDate"> → {{ day(exp.endDate) }}</template></template
                >
              </div>
            </li>
          </ul>
        </section>

        <!-- Formations -->
        <section
          v-if="candidate.educations.length"
          class="flex flex-col gap-element rounded-lg bg-surface p-block"
        >
          <h2 class="text-h3 font-semibold">{{ $t('candidate.educations') }}</h2>
          <ul class="flex list-none flex-col gap-2 p-0">
            <li v-for="(edu, i) in candidate.educations" :key="i">
              <span class="font-semibold">{{ edu.title || edu.degree || '—' }}</span>
              <span v-if="edu.school" class="text-muted"> · {{ edu.school }}</span>
            </li>
          </ul>
        </section>

        <!-- Projet Maroc -->
        <section class="flex flex-col gap-block rounded-lg bg-surface p-block">
          <h2 class="text-h3 font-semibold">{{ $t('candidate.morocco') }}</h2>
          <dl class="grid grid-cols-1 gap-x-block gap-y-element sm:grid-cols-2">
            <div>
              <dt class="text-sm text-muted">{{ $t('candidate.availability') }}</dt>
              <dd>{{ candidate.availability || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted">{{ $t('candidate.returnHorizon') }}</dt>
              <dd>{{ candidate.returnHorizon || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted">{{ $t('candidate.piedATerre') }}</dt>
              <dd>
                {{
                  candidate.hasPiedATerre
                    ? candidate.piedATerreCity || $t('candidate.oui')
                    : $t('candidate.non')
                }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-muted">{{ $t('candidate.geoMobility') }}</dt>
              <dd>{{ candidate.geoMobility ? $t('candidate.oui') : $t('candidate.non') }}</dd>
            </div>
          </dl>
          <div v-if="candidate.returnProject" class="flex flex-col gap-1">
            <span class="text-sm text-muted">{{ $t('candidate.returnProject') }}</span>
            <p class="whitespace-pre-line">{{ candidate.returnProject }}</p>
          </div>
          <div v-if="candidate.presencePeriods.length" class="flex flex-col gap-2">
            <span class="text-sm text-muted">{{ $t('candidate.presence') }}</span>
            <ul class="flex list-none flex-col gap-1 p-0">
              <li v-for="(period, i) in candidate.presencePeriods" :key="i">
                {{ day(period.startDate) }} → {{ day(period.endDate)
                }}<span v-if="period.city" class="text-muted"> · {{ period.city }}</span>
              </li>
            </ul>
          </div>
        </section>
      </template>
    </div>

    <!-- D · exploitation -->
    <template v-if="candidate" #aside>
      <div class="flex h-full flex-col gap-block">
        <section class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <h2 class="text-h3 font-semibold">{{ $t('candidate.ops') }}</h2>
          <dl class="flex flex-col gap-element">
            <div class="flex items-center justify-between gap-2">
              <dt class="text-sm text-muted">{{ $t('candidate.visibility') }}</dt>
              <dd>
                <UBadge color="primary" variant="soft">{{ candidate.visibility }}</UBadge>
              </dd>
            </div>
            <div class="flex items-center justify-between gap-2">
              <dt class="text-sm text-muted">{{ $t('candidate.active') }}</dt>
              <dd>{{ candidate.active ? $t('candidate.oui') : $t('candidate.non') }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
              <dt class="text-sm text-muted">{{ $t('candidate.consent') }}</dt>
              <dd>
                {{ candidate.hasConsent ? day(candidate.consentGivenAt) : $t('candidate.non') }}
              </dd>
            </div>
            <div v-if="candidate.consentSource" class="flex items-center justify-between gap-2">
              <dt class="text-sm text-muted">{{ $t('candidate.consentSource') }}</dt>
              <dd>{{ candidate.consentSource }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
              <dt class="text-sm text-muted">{{ $t('candidate.sync') }}</dt>
              <dd>{{ candidate.syncStatus }}</dd>
            </div>
            <div class="flex items-center justify-between gap-2">
              <dt class="text-sm text-muted">{{ $t('candidate.createdAt') }}</dt>
              <dd>{{ day(candidate.createdAt) }}</dd>
            </div>
          </dl>
          <div class="flex flex-col gap-1 border-t border-line pt-element">
            <span class="text-sm text-muted">{{ $t('candidate.jarviId') }}</span>
            <code class="break-all text-xs">{{ candidate.jarviProfileId }}</code>
          </div>
        </section>

        <section
          v-if="candidate.isStar"
          class="flex flex-col gap-element rounded-lg bg-surface p-block"
        >
          <h2 class="flex items-center gap-2 text-h3 font-semibold">
            <Icon name="lucide:star" class="size-4 shrink-0 text-accent" aria-hidden="true" />
            {{ $t('candidate.star') }}
          </h2>
          <p class="text-sm text-muted">{{ day(candidate.starInterviewedAt) }}</p>
          <p v-if="candidate.starInterviewReport" class="whitespace-pre-line">
            {{ candidate.starInterviewReport }}
          </p>
        </section>
      </div>
    </template>
  </DashboardBody>
</template>

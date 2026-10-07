# syntax=docker/dockerfile:1

ARG BUN_VERSION=1.2.22

FROM oven/bun:${BUN_VERSION} AS deps
WORKDIR /app

ENV PYTHON=python3

RUN apt-get update \
  && apt-get install -y --no-install-recommends g++ make python3 \
  && rm -rf /var/lib/apt/lists/*

COPY docs/package.json docs/bun.lock ./docs/
COPY docs/api/package.json ./docs/api/package.json
COPY docs/showcase-recruiter-webapp-saas/package.json ./docs/showcase-recruiter-webapp-saas/package.json
COPY docs/dev-book/package.json ./docs/dev-book/package.json

RUN cd docs && bun install --frozen-lockfile

FROM deps AS builder
WORKDIR /app

ARG DOCS_SITE
ARG NUXT_SITE_URL

ENV NODE_ENV=production
ENV NUXT_TELEMETRY_DISABLED=1
ENV NUXT_SITE_URL=${NUXT_SITE_URL}

COPY docs ./docs
COPY dev-book ./dev-book

RUN test -n "${DOCS_SITE}"
RUN cd docs && bun run build:${DOCS_SITE}

FROM caddy:2-alpine AS runtime

ARG DOCS_SITE

# Sites Docus entièrement prérendus → servis en STATIQUE (pas de serveur SSR Nitro,
# qui crashe au runtime : @nuxt/image → ipx → `ofetch` non tracé par Nitro). On ne
# copie que .output/public et on le sert via Caddy. Cf. docs/Caddyfile.static.
COPY --from=builder /app/docs/${DOCS_SITE}/.output/public /srv
COPY docs/Caddyfile.static /etc/caddy/Caddyfile

EXPOSE 3000

# Nuxt SSR preprod/prod — build multi-stage, runtime minimal (.output autonome).
# Architecture layers : UNE IMAGE PAR ESPACE — builder avec
# `--build-arg SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE=<public|candidat|entreprise|admin>`.
FROM oven/bun:1 AS build

WORKDIR /app

COPY package.json bun.lock ./
RUN bun install --frozen-lockfile

COPY . .

ARG SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE=public
ENV SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE=$SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE
RUN bun run build

FROM oven/bun:1-slim

WORKDIR /app
ENV NODE_ENV=production

COPY --from=build /app/.output ./.output

EXPOSE 3000

CMD ["bun", "run", ".output/server/index.mjs"]

import { mkdir, rm, writeFile } from 'node:fs/promises'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

interface OpenApiDocument {
  openapi: string
  info: OpenApiInfo
  servers?: OpenApiServer[]
  paths: Record<string, PathItem>
  components?: {
    schemas?: Record<string, SchemaObject>
    securitySchemes?: Record<string, unknown>
  }
  tags?: OpenApiTag[]
}

interface OpenApiInfo {
  title: string
  description?: string
  version: string
}

interface OpenApiServer {
  url: string
  description?: string
}

interface OpenApiTag {
  name: string
  description?: string
}

type HttpMethod = 'get' | 'post' | 'put' | 'patch' | 'delete' | 'options' | 'head' | 'trace'

type PathItem = Partial<Record<HttpMethod, OperationObject>>

interface OperationObject {
  operationId?: string
  tags?: string[]
  summary?: string
  description?: string
  parameters?: ParameterObject[]
  requestBody?: RequestBodyObject
  responses?: Record<string, ResponseObject>
  security?: Array<Record<string, string[]>>
}

interface ParameterObject {
  name: string
  in: string
  description?: string
  required?: boolean
  deprecated?: boolean
  schema?: SchemaObject
}

interface RequestBodyObject {
  description?: string
  required?: boolean
  content?: Record<string, MediaTypeObject>
}

interface ResponseObject {
  description?: string
  content?: Record<string, MediaTypeObject>
}

interface MediaTypeObject {
  schema?: SchemaObject
}

interface SchemaObject {
  $ref?: string
  type?: string | string[]
  format?: string
  description?: string
  default?: unknown
  nullable?: boolean
  readOnly?: boolean
  required?: string[]
  properties?: Record<string, SchemaObject>
  items?: SchemaObject
  enum?: string[]
  maxLength?: number
  minLength?: number
  externalDocs?: {
    url?: string
  }
}

interface OperationEntry {
  path: string
  method: HttpMethod
  operation: OperationObject
}

const httpMethods: HttpMethod[] = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace']

const scriptDir = dirname(fileURLToPath(import.meta.url))
const docsRoot = join(scriptDir, '..', 'api', 'content')
const routesDir = join(docsRoot, 'routes')
const schemasDir = join(docsRoot, 'schemas')

const sourceUrl = process.argv.at(2) ?? process.env.OPENAPI_URL ?? 'http://localhost:18082/v1/docs.json'

const markdownTableEscape = (value: string): string => value.replaceAll('|', '\\|').replaceAll('\n', '<br>')

const tableRow = (cells: string[]): string => `| ${cells.map(markdownTableEscape).join(' | ')} |`

const isRecord = (value: unknown): value is Record<string, unknown> =>
  typeof value === 'object' && value !== null && !Array.isArray(value)

const isOpenApiDocument = (value: unknown): value is OpenApiDocument => {
  if (!isRecord(value) || !isRecord(value.info) || !isRecord(value.paths)) {
    return false
  }

  return typeof value.openapi === 'string'
    && typeof value.info.title === 'string'
    && typeof value.info.version === 'string'
}

const normalizeText = (value?: string): string => {
  if (!value) {
    return ''
  }

  return value.trim()
}

const code = (value: string): string => `\`${value}\``

const headingId = (value: string): string => slugify(value)

const slugify = (value: string): string =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')

const schemaNameFromRef = (ref: string): string => ref.split('/').at(-1) ?? ref

const schemaLink = (ref: string): string => {
  const schemaName = schemaNameFromRef(ref)

  return `[${code(schemaName)}](/schemas#${headingId(schemaName)})`
}

const formatSchemaType = (schema?: SchemaObject): string => {
  if (!schema) {
    return '-'
  }

  if (schema.$ref) {
    return schemaLink(schema.$ref)
  }

  const rawType = Array.isArray(schema.type) ? schema.type.join(' | ') : schema.type
  const baseType = rawType ?? (schema.properties ? 'object' : '-')
  const formatted = schema.format ? `${baseType}:${schema.format}` : baseType

  if (schema.items) {
    return `${formatted}<${formatSchemaType(schema.items)}>`
  }

  return formatted
}

const formatSchemaConstraints = (schema: SchemaObject, required: boolean): string => {
  const constraints: string[] = []

  if (required) {
    constraints.push('required')
  }

  if (schema.readOnly) {
    constraints.push('readOnly')
  }

  if (schema.minLength !== undefined) {
    constraints.push(`minLength=${schema.minLength}`)
  }

  if (schema.maxLength !== undefined) {
    constraints.push(`maxLength=${schema.maxLength}`)
  }

  if (schema.enum?.length) {
    constraints.push(`enum=${schema.enum.join(', ')}`)
  }

  return constraints.length ? constraints.map(code).join(', ') : '-'
}

const getPrimarySchema = (content?: Record<string, MediaTypeObject>): SchemaObject | undefined => {
  if (!content) {
    return undefined
  }

  return content['application/json']?.schema ?? Object.values(content).find((mediaType) => mediaType.schema)?.schema
}

const frontMatter = (title: string, description?: string): string => {
  const fields = [`title: ${JSON.stringify(title)}`]

  if (description) {
    fields.push(`description: ${JSON.stringify(description)}`)
  }

  return `---\n${fields.join('\n')}\n---\n\n`
}

const methodLabel = (method: HttpMethod): string => method.toUpperCase()

const routeAnchor = (entry: OperationEntry): string => headingId(`${entry.method}-${entry.path}`)

const collectOperations = (document: OpenApiDocument): OperationEntry[] =>
  Object.entries(document.paths).flatMap(([path, item]) =>
    httpMethods.flatMap((method) => {
      const operation = item[method]

      return operation ? [{ path, method, operation }] : []
    }),
  )

const groupOperationsByTag = (entries: OperationEntry[]): Map<string, OperationEntry[]> => {
  const groups = new Map<string, OperationEntry[]>()

  for (const entry of entries) {
    const tag = entry.operation.tags?.at(0) ?? 'Sans tag'
    const existing = groups.get(tag) ?? []

    existing.push(entry)
    groups.set(tag, existing)
  }

  return groups
}

const renderParameters = (parameters?: ParameterObject[]): string => {
  if (!parameters?.length) {
    return 'Aucun parametre.\n'
  }

  const rows = parameters.map((parameter) =>
    tableRow([
      code(parameter.name),
      parameter.in,
      parameter.required ? 'oui' : 'non',
      formatSchemaType(parameter.schema),
      markdownTableEscape(normalizeText(parameter.description) || '-'),
    ]),
  )

  return ['| Nom | Emplacement | Requis | Type | Description |', '| --- | --- | --- | --- | --- |', ...rows].join('\n')
}

const renderRequestBody = (requestBody?: RequestBodyObject): string => {
  if (!requestBody) {
    return 'Aucun body.\n'
  }

  const schema = getPrimarySchema(requestBody.content)
  const contentTypes = Object.keys(requestBody.content ?? {})

  return [
    requestBody.description ? normalizeText(requestBody.description) : undefined,
    `- Requis : ${requestBody.required ? 'oui' : 'non'}`,
    `- Content-Type : ${contentTypes.length ? contentTypes.map(code).join(', ') : '-'}`,
    `- Schema : ${formatSchemaType(schema)}`,
  ]
    .filter((line): line is string => Boolean(line))
    .join('\n')
}

const renderResponses = (responses?: Record<string, ResponseObject>): string => {
  if (!responses || !Object.keys(responses).length) {
    return 'Aucune reponse documentee.\n'
  }

  const rows = Object.entries(responses).map(([status, response]) =>
    tableRow([
      code(status),
      markdownTableEscape(normalizeText(response.description) || '-'),
      formatSchemaType(getPrimarySchema(response.content)),
    ]),
  )

  return ['| Statut | Description | Schema |', '| --- | --- | --- |', ...rows].join('\n')
}

const renderOperation = (entry: OperationEntry): string => {
  const operation = entry.operation
  const title = `${methodLabel(entry.method)} ${entry.path}`
  const lines: string[] = [
    `## ${title}`,
    '',
    operation.summary ? `**Resume** : ${normalizeText(operation.summary)}` : undefined,
    operation.description ? normalizeText(operation.description) : undefined,
    '',
    `- Operation ID : ${operation.operationId ? code(operation.operationId) : '-'}`,
    `- Securite : ${operation.security?.length ? 'specifique a la route' : 'non declaree sur la route'}`,
    '',
    '### Parametres',
    '',
    renderParameters(operation.parameters),
    '',
    '### Body',
    '',
    renderRequestBody(operation.requestBody),
    '',
    '### Reponses',
    '',
    renderResponses(operation.responses),
    '',
  ].filter((line): line is string => line !== undefined)

  return lines.join('\n')
}

const renderRoutesIndex = (document: OpenApiDocument, entries: OperationEntry[]): string => {
  const rows = entries.map((entry) => {
    const tag = entry.operation.tags?.at(0) ?? 'Sans tag'
    const routeFile = `${slugify(tag)}.md`

    return tableRow([
      methodLabel(entry.method),
      `[${code(entry.path)}](/routes/${routeFile.replace(/\.md$/, '')}#${routeAnchor(entry)})`,
      tag,
      markdownTableEscape(normalizeText(entry.operation.summary) || '-'),
    ])
  })

  return [
    frontMatter('Routes API showcase-recruiter-webapp-saas', `Routes exposees par ${document.info.title} ${document.info.version}.`),
    '# Routes API',
    '',
    `Source OpenAPI : ${code(sourceUrl)}`,
    '',
    '| Methode | Route | Tag | Resume |',
    '| --- | --- | --- | --- |',
    ...rows,
    '',
  ].join('\n')
}

const renderTagPage = (document: OpenApiDocument, tag: string, entries: OperationEntry[]): string => {
  const tagDescription = document.tags?.find((item) => item.name === tag)?.description
  const title = `Routes ${tag}`

  return [
    frontMatter(title, normalizeText(tagDescription) || undefined),
    `# ${title}`,
    '',
    normalizeText(tagDescription),
    '',
    ...entries.map(renderOperation),
  ]
    .filter(Boolean)
    .join('\n')
}

const renderSchemasIndex = (document: OpenApiDocument): string => {
  const schemas = document.components?.schemas ?? {}
  const sections = Object.entries(schemas).map(([name, schema]) => {
    const requiredProperties = new Set(schema.required ?? [])
    const propertyRows = Object.entries(schema.properties ?? {}).map(([propertyName, propertySchema]) =>
      tableRow([
        code(propertyName),
        formatSchemaType(propertySchema),
        formatSchemaConstraints(propertySchema, requiredProperties.has(propertyName)),
        markdownTableEscape(normalizeText(propertySchema.description) || '-'),
      ]),
    )

    return [
      `## ${name}`,
      '',
      normalizeText(schema.description),
      '',
      propertyRows.length
        ? ['| Propriete | Type | Contraintes | Description |', '| --- | --- | --- | --- |', ...propertyRows].join('\n')
        : 'Aucune propriete documentee.',
      '',
    ]
      .filter(Boolean)
      .join('\n')
  })

  return [
    frontMatter('Schemas API showcase-recruiter-webapp-saas', `Schemas OpenAPI exposes par ${document.info.title}.`),
    '# Schemas',
    '',
    `Source OpenAPI : ${code(sourceUrl)}`,
    '',
    ...sections,
  ].join('\n')
}

const renderHome = (document: OpenApiDocument, entries: OperationEntry[]): string => {
  const tags = [...new Set(entries.map((entry) => entry.operation.tags?.at(0) ?? 'Sans tag'))]
  const serverRows = (document.servers ?? []).map((server) =>
    `- ${code(server.url)}${server.description ? ` : ${server.description}` : ''}`,
  )

  return [
    frontMatter(document.info.title, `Documentation generee depuis la specification OpenAPI ${document.info.version}.`),
    `# ${document.info.title}`,
    '',
    normalizeText(document.info.description),
    '',
    '## Vue d ensemble',
    '',
    `- Version OpenAPI : ${code(document.openapi)}`,
    `- Version API : ${code(document.info.version)}`,
    `- Source : ${code(sourceUrl)}`,
    `- Routes documentees : ${entries.length}`,
    `- Schemas documentes : ${Object.keys(document.components?.schemas ?? {}).length}`,
    '',
    '## Serveurs',
    '',
    serverRows.length ? serverRows.join('\n') : '- Aucun serveur declare.',
    '',
    '## Sections',
    '',
    '- [Routes API](/routes)',
    '- [Schemas](/schemas)',
    '',
    '## Domaines',
    '',
    ...tags.map((tag) => `- [${tag}](/routes/${slugify(tag)})`),
    '',
  ]
    .filter(Boolean)
    .join('\n')
}

const loadOpenApiDocument = async (): Promise<OpenApiDocument> => {
  const response = await fetch(sourceUrl)

  if (!response.ok) {
    throw new Error(`Unable to fetch OpenAPI document from ${sourceUrl}: HTTP ${response.status}`)
  }

  const document: unknown = await response.json()

  if (!isOpenApiDocument(document)) {
    throw new Error(`Invalid OpenAPI document from ${sourceUrl}`)
  }

  return document
}

const writeMarkdown = async (path: string, content: string): Promise<void> => {
  await mkdir(dirname(path), { recursive: true })
  await writeFile(path, `${content.trimEnd()}\n`, 'utf8')
}

const generate = async (): Promise<void> => {
  const document = await loadOpenApiDocument()
  const entries = collectOperations(document)
  const groups = groupOperationsByTag(entries)

  await mkdir(docsRoot, { recursive: true })
  await rm(routesDir, { recursive: true, force: true })
  await rm(schemasDir, { recursive: true, force: true })

  await writeMarkdown(join(docsRoot, 'index.md'), renderHome(document, entries))
  await writeMarkdown(join(routesDir, 'index.md'), renderRoutesIndex(document, entries))
  await writeMarkdown(join(schemasDir, 'index.md'), renderSchemasIndex(document))

  for (const [tag, tagEntries] of groups.entries()) {
    await writeMarkdown(join(routesDir, `${slugify(tag)}.md`), renderTagPage(document, tag, tagEntries))
  }

  console.log(`Generated ${entries.length} operations and ${Object.keys(document.components?.schemas ?? {}).length} schemas.`)
}

await generate()

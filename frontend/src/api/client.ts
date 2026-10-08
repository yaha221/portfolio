import createClient from 'openapi-fetch'

import type { components, paths } from './schema'

/** Response and request bodies as described by the backend's OpenAPI document (npm run api:types). */
export type Schemas = components['schemas']

/**
 * Typed client for the backend. URLs are relative: in development Vite proxies /api to nginx,
 * in production the SPA and the API share one origin.
 */
export const api = createClient<paths>({ baseUrl: '' })

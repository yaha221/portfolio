import { api } from './client'

/** Records a page view for the admin analytics. Fire and forget: it must never break or slow down navigation. */
export function trackPageView(path: string): void {
  api.POST('/api/page-views', { body: { path } }).catch(() => {})
}

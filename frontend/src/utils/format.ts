const monthYear = new Intl.DateTimeFormat('ru', { month: 'short', year: 'numeric' })
const plural = new Intl.PluralRules('ru')

/** Picks the Russian word form for a count: plural(5, ['год', 'года', 'лет']) -> 'лет'. */
export function pluralize(count: number, [one, few, many]: [string, string, string]): string {
  const rule = plural.select(count)
  return rule === 'one' ? one : rule === 'few' ? few : many
}

/** "2023-01-18" -> "янв. 2023" */
export function formatMonth(isoDate: string): string {
  return monthYear.format(new Date(`${isoDate}T00:00:00`)).replace(/\s*г\.$/, '')
}

/** Work period, e.g. "янв. 2023 — сейчас · 2 года 3 мес." */
export function formatPeriod(
  startDate: string,
  endDate: string | null | undefined,
  now = new Date(),
): string {
  const start = new Date(`${startDate}T00:00:00`)
  const end = endDate ? new Date(`${endDate}T00:00:00`) : now
  const totalMonths = Math.max(
    1,
    (end.getFullYear() - start.getFullYear()) * 12 + end.getMonth() - start.getMonth() + 1,
  )
  const years = Math.floor(totalMonths / 12)
  const months = totalMonths % 12

  const duration = [
    years > 0 ? `${years} ${pluralize(years, ['год', 'года', 'лет'])}` : '',
    months > 0 ? `${months} мес.` : '',
  ]
    .filter(Boolean)
    .join(' ')

  return `${formatMonth(startDate)} — ${endDate ? formatMonth(endDate) : 'сейчас'} · ${duration}`
}

/** Groups items by category, keeping the order in which categories first appear. */
export function groupByCategory<T extends { category: string }>(
  items: T[],
): { category: string; items: T[] }[] {
  const groups = new Map<string, T[]>()
  for (const item of items) {
    const group = groups.get(item.category)
    if (group) {
      group.push(item)
    } else {
      groups.set(item.category, [item])
    }
  }

  return [...groups].map(([category, grouped]) => ({ category, items: grouped }))
}

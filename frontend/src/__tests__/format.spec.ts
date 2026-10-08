import { describe, expect, it } from 'vitest'

import { formatPeriod, groupByCategory, pluralize } from '@/utils/format'

describe('pluralize', () => {
  it.each([
    [1, 'год'],
    [3, 'года'],
    [5, 'лет'],
    [11, 'лет'],
    [21, 'год'],
  ])('%i -> %s', (count, expected) => {
    expect(pluralize(count, ['год', 'года', 'лет'])).toBe(expected)
  })
})

describe('formatPeriod', () => {
  it('counts both the first and the last month', () => {
    // Short Russian month names keep short months whole: "март", not "мар."
    expect(formatPeriod('2023-01-18', '2025-03-01')).toBe('янв. 2023 — март 2025 · 2 года 3 мес.')
  })

  it('shows a current job up to now', () => {
    expect(formatPeriod('2026-01-10', null, new Date('2026-06-15T00:00:00'))).toBe(
      'янв. 2026 — сейчас · 6 мес.',
    )
  })
})

describe('groupByCategory', () => {
  it('keeps the order in which categories first appear', () => {
    const groups = groupByCategory([
      { name: 'PHP', category: 'Backend' },
      { name: 'Docker', category: 'DevOps' },
      { name: 'Symfony', category: 'Backend' },
    ])

    expect(groups.map((group) => [group.category, group.items.map((item) => item.name)])).toEqual([
      ['Backend', ['PHP', 'Symfony']],
      ['DevOps', ['Docker']],
    ])
  })
})

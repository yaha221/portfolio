import { describe, expect, it } from 'vitest'

import { slugify } from '@/utils/slug'

describe('slugify', () => {
  it.each([
    ['Портфолио', 'portfolio'],
    ['Мой проект 2.0', 'moi-proekt-2-0'],
    ['  Щука и ёж — PHP!  ', 'shchuka-i-ezh-php'],
    ['Symfony API', 'symfony-api'],
  ])('%s -> %s', (title, slug) => {
    expect(slugify(title)).toBe(slug)
  })

  it('matches the slug pattern the API validates', () => {
    expect(slugify('Тест --- Проект')).toMatch(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)
  })
})

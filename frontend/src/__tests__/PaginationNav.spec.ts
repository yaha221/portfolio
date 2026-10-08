import { mount, RouterLinkStub } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import PaginationNav from '@/components/PaginationNav.vue'

function render(page: number, pages: number) {
  return mount(PaginationNav, {
    props: { page, pages, linkTo: (target: number) => ({ query: { page: String(target) } }) },
    global: { stubs: { RouterLink: RouterLinkStub } },
  })
}

function targets(wrapper: ReturnType<typeof render>) {
  return wrapper
    .findAllComponents(RouterLinkStub)
    .map((link) => (link.props('to') as { query: { page: string } }).query.page)
}

describe('PaginationNav', () => {
  it('is hidden when everything fits on one page', () => {
    expect(render(1, 1).find('nav').exists()).toBe(false)
  })

  it('marks the current page and links the others', () => {
    const wrapper = render(2, 3)

    const current = wrapper.find('[aria-current="page"]')
    expect(current.text()).toBe('2')
    expect(current.element.tagName).toBe('SPAN')
    // previous, 1, 3, next
    expect(targets(wrapper)).toEqual(['1', '1', '3', '3'])
  })

  it('has no previous link on the first page and no next link on the last', () => {
    expect(render(1, 3).find('[rel="prev"]').exists()).toBe(false)
    expect(render(1, 3).find('[rel="next"]').exists()).toBe(true)
    expect(render(3, 3).find('[rel="next"]').exists()).toBe(false)
  })
})

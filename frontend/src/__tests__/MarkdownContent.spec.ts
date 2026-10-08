import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import MarkdownContent from '@/components/MarkdownContent.vue'

describe('MarkdownContent', () => {
  it('renders Markdown as HTML', () => {
    const wrapper = mount(MarkdownContent, { props: { source: '## Стек\n\n**Symfony** и `PHP`' } })

    expect(wrapper.find('h2').text()).toBe('Стек')
    expect(wrapper.find('strong').text()).toBe('Symfony')
    expect(wrapper.find('code').text()).toBe('PHP')
  })

  it('never lets HTML in the source run scripts', () => {
    const source = [
      '<script>alert(1)</script>',
      '<img src="x" onerror="alert(2)">',
      '[ссылка](javascript:alert(3))',
      '<iframe src="https://evil.example"></iframe>',
    ].join('\n\n')

    const html = mount(MarkdownContent, { props: { source } }).html()

    expect(html).not.toContain('<script')
    expect(html).not.toContain('onerror')
    expect(html).not.toContain('javascript:')
    expect(html).not.toContain('<iframe')
  })
})

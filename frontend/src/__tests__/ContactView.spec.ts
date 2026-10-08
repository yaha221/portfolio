import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { api } from '@/api/client'
import ContactView from '@/views/ContactView.vue'

import { okResult, problemResult } from './fixtures'

vi.mock('@/api/client', () => ({ api: { POST: vi.fn<() => Promise<unknown>>() } }))

const post = vi.mocked(api.POST)

beforeEach(() => {
  post.mockReset()
})

async function send() {
  const wrapper = mount(ContactView)
  await wrapper.find('#contact-name').setValue('Иван')
  await wrapper.find('#contact-email').setValue('ivan@example.com')
  await wrapper.find('#contact-message').setValue('Хочу обсудить проект.')
  await wrapper.find('form').trigger('submit')
  await flushPromises()
  return wrapper
}

describe('ContactView', () => {
  it('sends the message and shows a confirmation', async () => {
    post.mockResolvedValue(okResult(null, 202))

    const wrapper = await send()

    expect(post).toHaveBeenCalledWith('/api/contact', {
      // The honeypot goes too: the API drops messages where a bot filled it
      body: {
        name: 'Иван',
        email: 'ivan@example.com',
        message: 'Хочу обсудить проект.',
        website: '',
      },
    })
    expect(wrapper.find('[role="status"]').text()).toContain('Сообщение отправлено')

    await wrapper.find('[role="status"] button').trigger('click')
    expect((wrapper.find('#contact-message').element as HTMLTextAreaElement).value).toBe('')
  })

  it('explains invalid fields in Russian instead of the API messages', async () => {
    post.mockResolvedValue(
      problemResult(422, {
        violations: [
          { propertyPath: 'email', message: 'This value is not a valid email address.' },
          { propertyPath: 'phone', message: 'Unexpected.' },
        ],
      }),
    )

    const wrapper = await send()

    expect(wrapper.find('#contact-email-error').text()).toBe(
      'Нужен корректный email, чтобы я мог ответить.',
    )
    expect(wrapper.find('#contact-email').attributes('aria-invalid')).toBe('true')
    expect(wrapper.find('#contact-name-error').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('This value is not a valid email address.')
  })

  it('asks to wait when the address sent too many messages', async () => {
    post.mockResolvedValue(problemResult(429))

    const wrapper = await send()

    expect(wrapper.find('[role="alert"]').text()).toBe(
      'Слишком много сообщений подряд. Попробуйте через несколько минут.',
    )
  })

  it('keeps the text when the network fails, so nothing has to be typed again', async () => {
    post.mockRejectedValue(new TypeError('Failed to fetch'))

    const wrapper = await send()

    expect(wrapper.find('[role="alert"]').text()).toContain('Не получилось отправить')
    expect((wrapper.find('#contact-message').element as HTMLTextAreaElement).value).toBe(
      'Хочу обсудить проект.',
    )
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined()
  })
})

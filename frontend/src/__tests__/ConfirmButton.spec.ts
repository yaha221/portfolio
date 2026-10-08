import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import ConfirmButton from '@/components/admin/ConfirmButton.vue'

afterEach(() => {
  vi.useRealTimers()
})

describe('ConfirmButton', () => {
  it('asks for a second click before confirming', async () => {
    const wrapper = mount(ConfirmButton)

    await wrapper.trigger('click')
    expect(wrapper.text()).toBe('Точно удалить?')
    expect(wrapper.emitted('confirm')).toBeUndefined()

    await wrapper.trigger('click')
    expect(wrapper.emitted('confirm')).toHaveLength(1)
    expect(wrapper.text()).toBe('Удалить')
  })

  it('forgets the first click after a few seconds', async () => {
    vi.useFakeTimers()
    const wrapper = mount(ConfirmButton, {
      props: { label: 'Убрать', confirmLabel: 'Убрать обложку?' },
    })

    await wrapper.trigger('click')
    vi.advanceTimersByTime(4000)
    await wrapper.vm.$nextTick()
    expect(wrapper.text()).toBe('Убрать')

    await wrapper.trigger('click')
    expect(wrapper.emitted('confirm')).toBeUndefined()
  })
})

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import { adminCall } from '@/api/admin'
import { api } from '@/api/client'
import LoadState from '@/components/LoadState.vue'
import { useAsync } from '@/composables/useAsync'

const PERIODS = [7, 30, 90]

// Chart geometry in viewBox units; the SVG scales to the panel width
const WIDTH = 720
const HEIGHT = 200
const GAP = 2

const days = ref(30)
const { data, loading, error, run } = useAsync(() =>
  adminCall((auth) =>
    api.GET('/api/admin/analytics', { ...auth, params: { query: { days: days.value } } }),
  ),
)

watch(days, run, { immediate: true })

const dayLabel = new Intl.DateTimeFormat('ru', { day: 'numeric', month: 'short' })

const chart = computed(() => {
  const series = data.value?.days ?? []
  const max = Math.max(1, ...series.map((day) => day.views))
  const slot = WIDTH / Math.max(1, series.length)

  return {
    max,
    bars: series.map((day, index) => {
      const height = (day.views / max) * (HEIGHT - 4)
      return {
        ...day,
        x: index * slot + GAP / 2,
        y: HEIGHT - height,
        width: Math.max(1, slot - GAP),
        height,
        label: `${dayLabel.format(new Date(`${day.date}T00:00:00`))}: ${day.views} просмотров, ${day.visitors} посетителей`,
      }
    }),
  }
})

const first = computed(() => chart.value.bars[0])
const last = computed(() => chart.value.bars.at(-1))
</script>

<template>
  <div class="admin-page">
    <header class="admin-page__head">
      <h1>Аналитика</h1>
      <div class="periods" role="group" aria-label="Период">
        <button
          v-for="period in PERIODS"
          :key="period"
          type="button"
          class="button button--small"
          :class="{ 'button--ghost': period !== days }"
          :aria-pressed="period === days"
          @click="days = period"
        >
          {{ period }} дней
        </button>
      </div>
    </header>

    <LoadState :loading="loading && !data" :error="error" @retry="run" />

    <template v-if="data">
      <dl class="totals">
        <div class="panel">
          <dt class="muted">Просмотры</dt>
          <dd>{{ data.totalViews }}</dd>
        </div>
        <div class="panel">
          <dt class="muted">Посетители</dt>
          <dd>{{ data.totalVisitors }}</dd>
          <p class="field__hint">Сумма по дням: один человек в разные дни считается заново</p>
        </div>
      </dl>

      <section class="panel" aria-labelledby="chart-title">
        <h2 id="chart-title">Просмотры по дням (UTC)</h2>
        <svg
          :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
          class="chart"
          role="img"
          :aria-label="`Просмотры с ${data.from} по ${data.to}, максимум ${chart.max} в день`"
          preserveAspectRatio="none"
        >
          <line :x1="0" :x2="WIDTH" :y1="HEIGHT - 0.5" :y2="HEIGHT - 0.5" class="chart__axis" />
          <rect
            v-for="bar in chart.bars"
            :key="bar.date"
            :x="bar.x"
            :y="bar.y"
            :width="bar.width"
            :height="bar.height"
            rx="1.5"
            class="chart__bar"
          >
            <title>{{ bar.label }}</title>
          </rect>
        </svg>
        <div class="chart__legend mono muted">
          <span>{{ first ? dayLabel.format(new Date(`${first.date}T00:00:00`)) : '' }}</span>
          <span>макс. {{ chart.max }} в день</span>
          <span>{{ last ? dayLabel.format(new Date(`${last.date}T00:00:00`)) : '' }}</span>
        </div>
      </section>

      <section class="panel" aria-labelledby="paths-title">
        <h2 id="paths-title">Популярные страницы</h2>
        <table v-if="data.topPaths.length" class="table">
          <thead>
            <tr>
              <th>Страница</th>
              <th class="num">Просмотры</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in data.topPaths" :key="row.path">
              <td class="mono">{{ row.path }}</td>
              <td class="num">{{ row.views }}</td>
            </tr>
          </tbody>
        </table>
        <p v-else class="muted">За этот период просмотров нет.</p>
      </section>
    </template>
  </div>
</template>

<style scoped>
.periods {
  display: flex;
  gap: var(--space-2);
}

.totals {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-4);
  margin: 0;
}

.totals dd {
  margin: var(--space-1) 0 0;
  font-size: var(--text-2xl);
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.chart {
  width: 100%;
  height: 200px;
  display: block;
}

.chart__axis {
  stroke: var(--border);
}

.chart__bar {
  fill: var(--accent);
}

.chart__bar:hover {
  fill: var(--accent-hover);
}

.chart__legend {
  display: flex;
  justify-content: space-between;
  margin-top: var(--space-2);
}
</style>

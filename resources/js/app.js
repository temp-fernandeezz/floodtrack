import './bootstrap'
import Alpine from 'alpinejs'
import { initFloodMap } from './map'

window.Alpine = Alpine

window.floodHome = function () {
  return {
    filters: { cidade: '', nivel: '', status: '' },
    statusText: 'Pronto para filtrar.',

    applyFilters() {
      window.dispatchEvent(new CustomEvent('flood:filters', {
        detail: { ...this.filters },
      }))
    },

    resetFilters() {
      this.filters = { cidade: '', nivel: '', status: '' }
      this.applyFilters()
    },
  }
}

Alpine.store('ui', {
  mobileMenuOpen: false,
  legendOpen: false,
  toggleLegend() {
    this.legendOpen = !this.legendOpen
  },
})

Alpine.data('pendingSwiper', (apiUrl) => ({
    apiUrl,
    loading: true,
    items: [],
    metaText: '',

    async init() {
      this.loading = true
      try {
        const res = await fetch(this.apiUrl, { headers: { Accept: 'application/json' } })
        this.items = res.ok ? await res.json() : []
        this.metaText = this.items.length ? `${this.items.length} pendente(s)` : '0 pendentes'
      } catch (e) {
        console.error(e)
        this.items = []
        this.metaText = 'Erro ao carregar'
      } finally {
        this.loading = false
      }
    },

    next() {
      const el = this.$refs.track
      if (!el) return
      el.scrollBy({ left: el.clientWidth * 0.85, behavior: 'smooth' })
    },

    prev() {
      const el = this.$refs.track
      if (!el) return
      el.scrollBy({ left: -el.clientWidth * 0.85, behavior: 'smooth' })
    },

    formatPlace(item) {
      const uf = item.uf ? `, ${item.uf}` : ''
      return `${item.cidade ?? 'Indefinido'}${uf}`
    },

    formatDate(dt) {
      if (!dt) return 'Data não informada'
      try {
        const d = new Date(dt)
        return d.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
      } catch {
        return 'Data inválida'
      }
    },

    badgeClass(nivel) {
      const n = (nivel || '').toLowerCase()
      if (n === 'alto') return 'border-red-200 bg-red-50 text-red-700'
      if (n === 'medio') return 'border-yellow-200 bg-yellow-50 text-yellow-700'
      return 'border-emerald-200 bg-emerald-50 text-emerald-700'
    },
  }))


Alpine.start()

document.addEventListener('DOMContentLoaded', () => {
  initFloodMap()
})

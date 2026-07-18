import './bootstrap'
import Alpine from 'alpinejs'
import { initFloodMap } from './map'

window.Alpine = Alpine

window.floodHome = function () {
  return {
    filters: { cidade: '', uf: '', nivel: '', status: '' },
    statusText: 'Pronto para filtrar.',
    noResults: false,
    foundResults: false,
    foundCount: 0,

    init() {
      window.addEventListener('flood:results', (e) => {
        const { count, hasFilters } = e.detail || {}

        this.noResults = !!hasFilters && count === 0
        this.foundResults = !!hasFilters && count > 0
        this.foundCount = count || 0

        if (!hasFilters) {
          this.statusText = count ? `${count} ocorrência(s) no mapa.` : 'Nenhuma ocorrência ativa no momento.'
        } else {
          this.statusText = ''
        }
      })
    },

    applyFilters() {
      window.dispatchEvent(new CustomEvent('flood:filters', {
        detail: { ...this.filters },
      }))
    },

    resetFilters() {
      this.filters = { cidade: '', uf: '', nivel: '', status: '' }
      this.applyFilters()
    },
  }
}

Alpine.store('ui', {
  mobileMenuOpen: false,
  aboutOpen: false,
  toggleAbout() {
    this.aboutOpen = !this.aboutOpen
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
      if (n === 'alto') return 'border-red-800 bg-red-950 text-red-300'
      if (n === 'medio') return 'border-yellow-800 bg-yellow-950 text-yellow-300'
      return 'border-emerald-800 bg-emerald-950 text-emerald-300'
    },
  }))


Alpine.data('exportForm', (citiesUrl, initialUf) => ({
  uf: initialUf || '',
  cidade: '',
  cidades: [],
  loadingCidades: false,

  init() {
    this.carregarCidades()
  },

  async carregarCidades() {
    this.loadingCidades = true
    try {
      const url = this.uf ? `${citiesUrl}?uf=${encodeURIComponent(this.uf)}` : citiesUrl
      const res = await fetch(url, { headers: { Accept: 'application/json' } })
      this.cidades = res.ok ? await res.json() : []
    } catch (e) {
      console.error(e)
      this.cidades = []
    } finally {
      this.loadingCidades = false
    }
  },
}))

Alpine.data('floodReport', () => ({
  latitude: '',
  longitude: '',
  locating: false,
  located: false,
  locationError: '',

  useMyLocation() {
    if (!navigator.geolocation) {
      this.locationError = 'Geolocalização não suportada neste navegador.'
      return
    }

    this.locating = true
    this.locationError = ''

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        this.latitude = pos.coords.latitude
        this.longitude = pos.coords.longitude
        this.located = true
        this.locating = false
      },
      () => {
        this.locationError = 'Não foi possível obter sua localização. Preencha a cidade manualmente.'
        this.locating = false
      },
      { enableHighAccuracy: true, timeout: 10000 }
    )
  },
}))

Alpine.start()

document.addEventListener('DOMContentLoaded', () => {
  initFloodMap()
})


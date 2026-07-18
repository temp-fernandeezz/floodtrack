import L from 'leaflet'
import { createNivelIcon } from './flood-marker-icon'

function toNumber(v) {
    const n = Number(v)
    return Number.isFinite(n) ? n : null
  }

  function buildPopup(p) {
    const title = `${p.cidade}${p.uf ? ', ' + p.uf : ''}`
    const bairro = p.bairro ? `Bairro: ${p.bairro}` : 'Bairro não informado'
    const logradouro = p.logradouro ? `<div style="font-size:12px;opacity:.85">${p.logradouro}</div>` : ''

    return `
      <div style="min-width:200px">
        <div style="font-weight:700">${title}</div>
        <div style="font-size:12px;opacity:.85">${bairro}</div>
        ${logradouro}
        <div style="font-size:12px;margin-top:6px">
          Nível: <b>${p.nivel}</b> • Status: <b>${p.status}</b>
        </div>
        <div style="font-size:12px;margin-top:8px">
          <a href="/pontos/${p.id}">Ver detalhes</a>
        </div>
      </div>
    `
  }

  async function updateRainWidget(lat, lng) {
    const widget = document.getElementById('rain-widget')
    if (!widget) return

    try {
      const res = await fetch(`/api/chuva?lat=${lat}&lng=${lng}`, { headers: { Accept: 'application/json' } })

      if (res.status !== 200) {
        widget.classList.add('hidden')
        widget.classList.remove('flex')
        return
      }

      const data = await res.json()
      document.getElementById('rain-widget-mm').textContent = `${data.mm_recente} mm`
      document.getElementById('rain-widget-local').textContent = data.local ? `(${data.local})` : ''
      widget.classList.remove('hidden')
      widget.classList.add('flex')
    } catch {
      widget.classList.add('hidden')
      widget.classList.remove('flex')
    }
  }

  function buildUrl(baseUrl, filters = {}) {
    const url = new URL(baseUrl, window.location.origin)
    Object.entries(filters).forEach(([k, v]) => {
      if (v !== null && v !== undefined && String(v).trim() !== '') {
        url.searchParams.set(k, v)
      }
    })
    return url.toString()
  }

  export async function initFloodMap() {
    const el = document.getElementById('map')
    if (!el) return

    const baseApiUrl = el.dataset.apiUrl
    const defaultLat = toNumber(el.dataset.defaultLat) ?? -23.1896
    const defaultLng = toNumber(el.dataset.defaultLng) ?? -45.8841
    const defaultZoom = toNumber(el.dataset.defaultZoom) ?? 10

    const map = L.map(el).setView([defaultLat, defaultLng], defaultZoom)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap',
    }).addTo(map)

    const markersLayer = L.layerGroup().addTo(map)

    async function render(filters = {}) {
      const url = buildUrl(baseApiUrl, filters)
      const hasFilters = Object.values(filters).some(
        (v) => v !== null && v !== undefined && String(v).trim() !== ''
      )

      // limpa markers
      markersLayer.clearLayers()

      const res = await fetch(url, { headers: { Accept: 'application/json' } })
      const points = res.ok ? await res.json() : []

      const bounds = []

      for (const p of points) {
        const lat = toNumber(p.latitude)
        const lng = toNumber(p.longitude)
        if (lat === null || lng === null) continue
        if (lat === 0 && lng === 0) continue

        bounds.push([lat, lng])

        L.marker([lat, lng], { icon: createNivelIcon(p.nivel) })
          .addTo(markersLayer)
          .bindPopup(buildPopup(p))
      }

      window.dispatchEvent(new CustomEvent('flood:results', {
        detail: { count: points.length, withMarkers: bounds.length, hasFilters },
      }))

      // enquadra
      if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [20, 20], maxZoom: 16 })
      } else if (!hasFilters) {
        // sem filtro nenhum e sem pontos: mostra a visão padrão (estado inicial)
        map.setView([defaultLat, defaultLng], defaultZoom)
      }
      // com filtro ativo e zero resultados: mantém a posição atual do mapa —
      // pular pro centro padrão dava a falsa impressão de ter "achado" um lugar
      // (ex.: buscar "Roraima" caía perto de São José dos Campos, sem nenhum marcador)
    }

    // primeira renderização
    await render({})

    // ouve filtros do Alpine
    window.addEventListener('flood:filters', async (e) => {
      await render(e.detail || {})
    })

    // chuva na região — atualiza no centro atual e quando o usuário move o mapa
    const center = map.getCenter()
    await updateRainWidget(center.lat, center.lng)
    map.on('moveend', () => {
      const c = map.getCenter()
      updateRainWidget(c.lat, c.lng)
    })
  }

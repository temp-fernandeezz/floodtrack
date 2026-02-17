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

    if (typeof window.L === 'undefined') {
      console.error('Leaflet (L) não carregou. Verifique o script no layout.')
      return
    }

    const map = window.L.map(el).setView([defaultLat, defaultLng], defaultZoom)

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap',
    }).addTo(map)

    const markersLayer = window.L.layerGroup().addTo(map)

    async function render(filters = {}) {
      const url = buildUrl(baseApiUrl, filters)

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

        window.L.marker([lat, lng]).addTo(markersLayer).bindPopup(buildPopup(p))
      }

      // enquadra
      if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [20, 20], maxZoom: 16 })
      } else {
        map.setView([defaultLat, defaultLng], defaultZoom)
      }
    }

    // primeira renderização
    await render({})

    // ouve filtros do Alpine
    window.addEventListener('flood:filters', async (e) => {
      await render(e.detail || {})
    })
  }

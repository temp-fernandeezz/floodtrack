import L from 'leaflet'
import { createNivelIcon } from './flood-marker-icon'

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('point-map')
  if (!el) return

  const lat = Number(el.dataset.lat)
  const lng = Number(el.dataset.lng)
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return

  const map = L.map(el).setView([lat, lng], 15)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map)

  L.marker([lat, lng], { icon: createNivelIcon(el.dataset.nivel) })
    .addTo(map)
    .bindPopup(el.dataset.popup || '')
    .openPopup()
})

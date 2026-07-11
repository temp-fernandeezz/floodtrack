import L from 'leaflet'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

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

  L.marker([lat, lng]).addTo(map).bindPopup(el.dataset.popup || '').openPopup()
})

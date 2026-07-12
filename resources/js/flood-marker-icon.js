import L from 'leaflet'

const NIVEL_COLORS = {
  baixo: '#10b981',
  medio: '#f59e0b',
  alto: '#ef4444',
}

export function nivelColor(nivel) {
  return NIVEL_COLORS[(nivel || '').toLowerCase()] ?? NIVEL_COLORS.baixo
}

export function createNivelIcon(nivel) {
  const color = nivelColor(nivel)

  return L.divIcon({
    className: 'flood-marker-icon',
    html: `
      <svg width="26" height="36" viewBox="0 0 26 36" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M13 0C5.82 0 0 5.82 0 13c0 9.75 13 23 13 23s13-13.25 13-23C26 5.82 20.18 0 13 0z"
          fill="${color}" stroke="#ffffff" stroke-width="1.5" />
        <circle cx="13" cy="13" r="5" fill="#ffffff" />
      </svg>
    `,
    iconSize: [26, 36],
    iconAnchor: [13, 36],
    popupAnchor: [0, -32],
  })
}

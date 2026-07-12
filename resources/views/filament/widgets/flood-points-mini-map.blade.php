<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Mapa rápido — ocorrências ativas
        </x-slot>

        <x-slot name="description">
            Preview dos pontos ativos e aprovados, coloridos por nível de severidade.
        </x-slot>

        @once
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        @endonce

        <div
            wire:ignore
            x-data="{
                map: null,
                init() {
                    this.map = L.map(this.$refs.mapEl).setView([-23.1896, -45.8841], 4)

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(this.map)

                    const colors = { baixo: '#10b981', medio: '#f59e0b', alto: '#ef4444' }

                    fetch('{{ route('flood-points.api') }}', { headers: { Accept: 'application/json' } })
                        .then(res => res.ok ? res.json() : [])
                        .then(points => {
                            const bounds = []

                            points.forEach(p => {
                                const lat = Number(p.latitude)
                                const lng = Number(p.longitude)
                                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return

                                bounds.push([lat, lng])

                                L.circleMarker([lat, lng], {
                                    radius: 6,
                                    color: '#ffffff',
                                    weight: 1,
                                    fillColor: colors[p.nivel] ?? colors.baixo,
                                    fillOpacity: 0.9,
                                })
                                    .addTo(this.map)
                                    .bindPopup(`${p.cidade}${p.uf ? ', ' + p.uf : ''}`)
                            })

                            if (bounds.length > 0) {
                                this.map.fitBounds(bounds, { padding: [20, 20], maxZoom: 12 })
                            }
                        })
                }
            }"
            x-init="
                if (!window.L) {
                    const script = document.createElement('script')
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
                    script.onload = () => init()
                    document.head.appendChild(script)
                } else {
                    init()
                }
            ">
            <div x-ref="mapEl" style="height: 320px; border-radius: 0.75rem;"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

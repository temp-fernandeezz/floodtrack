@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endonce

<div
    wire:ignore
    x-data="{
        map: null,
        marker: null,
        colors: { baixo: '#10b981', medio: '#f59e0b', alto: '#ef4444' },

        init() {
            this.map = L.map(this.$refs.mapEl).setView([-14.235, -51.9253], 4)

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.map)

            this.render()

            this.$watch('$wire.data.latitude', () => this.render())
            this.$watch('$wire.data.longitude', () => this.render())
            this.$watch('$wire.data.nivel', () => this.render())
        },

        render() {
            const lat = Number(this.$wire.data.latitude)
            const lng = Number(this.$wire.data.longitude)

            if (this.marker) {
                this.map.removeLayer(this.marker)
                this.marker = null
            }

            if (!Number.isFinite(lat) || !Number.isFinite(lng) || (lat === 0 && lng === 0)) {
                this.map.setView([-14.235, -51.9253], 4)
                return
            }

            this.marker = L.circleMarker([lat, lng], {
                radius: 8,
                color: '#ffffff',
                weight: 2,
                fillColor: this.colors[this.$wire.data.nivel] ?? this.colors.baixo,
                fillOpacity: 0.9,
            }).addTo(this.map)

            this.map.setView([lat, lng], 15)
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

    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="!Number.isFinite(Number($wire.data.latitude)) || !Number.isFinite(Number($wire.data.longitude)) || (Number($wire.data.latitude) === 0 && Number($wire.data.longitude) === 0)">
        Preencha latitude e longitude para ver a localização exata no mapa.
    </p>
</div>

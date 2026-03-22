<template>
    <div style="position: relative; height: 400px;">
        <l-map
            ref="map"
            :center="center"
            :zoom="13"
            :use-global-leaflet="false"
            style="height: 100%; width: 100%; border-radius: 8px;"
        >
            <l-polyline
                v-if="latLngs.length > 0"
                :lat-lngs="latLngs"
                color="#3B82F6"
                :weight="3"
            />
            <l-marker v-if="startPoint" :lat-lng="startPoint">
                <l-tooltip>Départ</l-tooltip>
            </l-marker>
            <l-marker v-if="endPoint" :lat-lng="endPoint">
                <l-tooltip>Arrivée</l-tooltip>
            </l-marker>
            <l-circle-marker
                v-if="props.hoveredPoint"
                :lat-lng="[props.hoveredPoint.lat, props.hoveredPoint.lon]"
                :radius="8"
                color="#2563EB"
                fill-color="#3B82F6"
                :fill-opacity="0.9"
                :weight="2"
            />        
        </l-map>
    </div>
</template>

<script setup>
/**
 * GPS track map rendered with Leaflet and OpenStreetMap tiles.
 *
 * Fetches track points from the API, draws the GPX polyline, and places start/end markers.
 * Tiles are cached offline via leaflet.offline. The map auto-fits its bounds to the track.
 * Reactively renders a highlighted circle marker at the `hoveredPoint` coordinates
 * (driven by the ElevationProfile hover event in the parent).
 *
 * @prop {number} activityId - ID of the activity whose track points are fetched.
 * @prop {{ lat: number, lon: number }|null} [hoveredPoint=null] - Coordinates to highlight on the map.
 */
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { LMap, LPolyline, LMarker, LTooltip, LCircleMarker } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';
import { tileLayerOffline } from 'leaflet.offline';

const props = defineProps({
    activityId:   { type: Number, required: true },
    hoveredPoint: { type: Object, default: null },
});

const map     = ref(null);
const points  = ref([]);
const loading = ref(true);

const latLngs = computed(() =>
    points.value.map(p => [p.lat, p.lon])
);

const startPoint = computed(() =>
    points.value.length > 0 ? [points.value[0].lat, points.value[0].lon] : null
);

const endPoint = computed(() =>
    points.value.length > 1
        ? [points.value[points.value.length - 1].lat, points.value[points.value.length - 1].lon]
        : null
);

const center = computed(() => {
    if (points.value.length === 0) return [45.0, 6.0];
    const lats = points.value.map(p => p.lat);
    const lons = points.value.map(p => p.lon);
    return [
        (Math.min(...lats) + Math.max(...lats)) / 2,
        (Math.min(...lons) + Math.max(...lons)) / 2,
    ];
});

/**
 * Fetches raw track points from the API and stores them in `points`.
 *
 * @returns {Promise<void>}
 */
const fetchPoints = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/activities/${props.activityId}/track`);
        points.value = data.data;
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await fetchPoints();
    await nextTick();

    const leafletMap = map.value?.leafletObject;
    if (!leafletMap) return;

    // Tile layer avec cache offline
    const tileLayer = tileLayerOffline(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: "&copy; <a href='https://www.openstreetmap.org/copyright'>OpenStreetMap</a> contributors",
            maxZoom: 19,
        }
    );
    tileLayer.addTo(leafletMap);

    // FitBounds sur le tracé
    if (points.value.length > 0) {
        const lats = points.value.map(p => p.lat);
        const lons = points.value.map(p => p.lon);
        leafletMap.fitBounds([
            [Math.min(...lats), Math.min(...lons)],
            [Math.max(...lats), Math.max(...lons)],
        ]);
    }
});

</script>
export const formatDuration = (seconds) => {
    if (!seconds) return '--';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return `${h}h${String(m).padStart(2, '0')}`;
};

export const formatDistance = (km) => km ? `${parseFloat(km).toFixed(1)} km` : '--';
export const formatElevation = (m) => m ? `${m} m` : '--';
export const formatSpeed = (kmh) => kmh ? `${parseFloat(kmh).toFixed(1)} km/h` : '--';
export const formatDate = (date) => date
    ? new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
    : '--';
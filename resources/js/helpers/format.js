/**
 * Formats a duration in seconds to a human-readable "HhMM" string.
 *
 * @param {number|null} seconds - Duration in seconds.
 * @returns {string} Formatted string (e.g. "2h05"), or '--' if falsy.
 */
export const formatDuration = (seconds) => {
    if (!seconds) return '--';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return `${h}h${String(m).padStart(2, '0')}`;
};

/**
 * Formats a distance in kilometres.
 *
 * @param {number|null} km - Distance in kilometres.
 * @returns {string} Formatted string (e.g. "12.3 km"), or '--' if falsy.
 */
export const formatDistance = (km) => km ? `${parseFloat(km).toFixed(1)} km` : '--';

/**
 * Formats an elevation value in metres.
 *
 * @param {number|null} m - Elevation in metres.
 * @returns {string} Formatted string (e.g. "450 m"), or '--' if falsy.
 */
export const formatElevation = (m) => m ? `${m} m` : '--';

/**
 * Formats a speed in km/h.
 *
 * @param {number|null} kmh - Speed in km/h.
 * @returns {string} Formatted string (e.g. "7.2 km/h"), or '--' if falsy.
 */
export const formatSpeed = (kmh) => kmh ? `${parseFloat(kmh).toFixed(1)} km/h` : '--';

/**
 * Formats a date string to a localized French date (DD/MM/YYYY).
 *
 * @param {string|null} date - ISO date string.
 * @returns {string} Formatted date (e.g. "22/03/2026"), or '--' if falsy.
 */
export const formatDate = (date) => date
    ? new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
    : '--';

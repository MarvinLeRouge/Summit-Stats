import { describe, it, expect } from 'vitest';
import {
    formatDuration,
    formatDistance,
    formatElevation,
    formatSpeed,
    formatDate,
} from '@/helpers/format.js';

describe('formatDuration', () => {
    it('returns "--" for null', () => {
        expect(formatDuration(null)).toBe('--');
    });

    it('returns "--" for 0', () => {
        expect(formatDuration(0)).toBe('--');
    });

    it('formats whole hours correctly', () => {
        expect(formatDuration(3600)).toBe('1h00');
    });

    it('pads minutes with leading zero', () => {
        expect(formatDuration(3660)).toBe('1h01');
    });

    it('formats hours and minutes correctly', () => {
        expect(formatDuration(7500)).toBe('2h05');
    });

    it('formats sub-hour durations', () => {
        expect(formatDuration(1800)).toBe('0h30');
    });
});

describe('formatDistance', () => {
    it('returns "--" for null', () => {
        expect(formatDistance(null)).toBe('--');
    });

    it('returns "--" for 0', () => {
        expect(formatDistance(0)).toBe('--');
    });

    it('formats with one decimal place', () => {
        expect(formatDistance(12.3456)).toBe('12.3 km');
    });

    it('formats integer distances', () => {
        expect(formatDistance(5)).toBe('5.0 km');
    });

    it('handles string numbers', () => {
        expect(formatDistance('8.75')).toBe('8.8 km');
    });
});

describe('formatElevation', () => {
    it('returns "--" for null', () => {
        expect(formatElevation(null)).toBe('--');
    });

    it('returns "--" for 0', () => {
        expect(formatElevation(0)).toBe('--');
    });

    it('appends " m" to the value', () => {
        expect(formatElevation(450)).toBe('450 m');
    });

    it('works with string values', () => {
        expect(formatElevation('1200')).toBe('1200 m');
    });
});

describe('formatSpeed', () => {
    it('returns "--" for null', () => {
        expect(formatSpeed(null)).toBe('--');
    });

    it('returns "--" for 0', () => {
        expect(formatSpeed(0)).toBe('--');
    });

    it('formats with one decimal place', () => {
        expect(formatSpeed(7.234)).toBe('7.2 km/h');
    });

    it('handles string numbers', () => {
        expect(formatSpeed('12.0')).toBe('12.0 km/h');
    });
});

describe('formatDate', () => {
    it('returns "--" for null', () => {
        expect(formatDate(null)).toBe('--');
    });

    it('returns "--" for empty string', () => {
        expect(formatDate('')).toBe('--');
    });

    it('formats an ISO date string to DD/MM/YYYY', () => {
        expect(formatDate('2026-03-22')).toBe('22/03/2026');
    });

    it('formats a full ISO datetime string', () => {
        expect(formatDate('2026-03-22T14:30:00Z')).toMatch(/\d{2}\/\d{2}\/2026/);
    });
});

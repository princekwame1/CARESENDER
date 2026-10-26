// Browser geolocation → nearest CareSenda area. The result is kept in
// localStorage (for the UI) and a small cookie (so the server can sort by distance).
const STORE = 'cs-location';
const COOKIE = 'cs_loc';
const MAX_AGE_MS = 6 * 60 * 60 * 1000;

const listeners = new Set();

export function onLocation(fn) {
    listeners.add(fn);
}

export function savedLocation() {
    try {
        const loc = JSON.parse(localStorage.getItem(STORE) || 'null');
        return loc && Date.now() - loc.at < MAX_AGE_MS ? loc : null;
    } catch {
        return null;
    }
}

function persist(loc) {
    try {
        localStorage.setItem(STORE, JSON.stringify(loc));
    } catch {}
    const value = encodeURIComponent(`${loc.lat.toFixed(5)},${loc.lng.toFixed(5)},${loc.area ?? ''}`);
    document.cookie = `${COOKIE}=${value}; Max-Age=${MAX_AGE_MS / 1000}; Path=/; SameSite=Lax`;
}

export function clearLocation() {
    try {
        localStorage.removeItem(STORE);
    } catch {}
    document.cookie = `${COOKIE}=; Max-Age=0; Path=/`;
}

function position() {
    return new Promise((resolve, reject) => {
        if (!('geolocation' in navigator)) return reject(new Error('Your browser can’t share your location. You can still pick a city.'));
        navigator.geolocation.getCurrentPosition(resolve, (err) => {
            const messages = {
                1: 'Location is turned off for this site. You can still pick a city.',
                2: 'We couldn’t find your location. Try again, or pick a city.',
                3: 'Finding your location took too long. Please try again.',
            };
            reject(new Error(messages[err.code] || 'We couldn’t get your location.'));
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60 * 1000 });
    });
}

export async function detect() {
    const { coords } = await position();
    const res = await fetch(`/api/v1/geo/resolve?lat=${coords.latitude}&lng=${coords.longitude}`, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('We couldn’t work out your area. Please try again.');
    const { data } = await res.json();
    const loc = { lat: data.lat, lng: data.lng, area: data.area, city: data.city, inCoverage: data.in_coverage, inCountry: data.in_country, accuracy: coords.accuracy, at: Date.now() };
    persist(loc);
    listeners.forEach((fn) => fn(loc));
    return loc;
}

export function label(loc) {
    if (!loc) return 'Use my location';
    if (loc.inCoverage) return `Near ${loc.area}`;
    return loc.inCountry === false ? 'Location looks wrong' : 'Outside Accra and Tema';
}

// Why a detected location isn't usable, for toasts.
export function outsideMessage(loc) {
    return loc.inCountry === false
        ? 'Your device says you’re outside Ghana. If you’re on a VPN or office network, turn it off and try again, or pick a city.'
        : 'Looks like you’re outside Accra and Tema';
}

import config from './config';

async function request(path, options = {}) {
    if (!config.apiBaseUrl) {
        throw new Error('VITE_API_BASE_URL is not configured.');
    }

    const response = await fetch(`${config.apiBaseUrl}${path}`, {
        headers: {
            Accept: 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });

    if (!response.ok) {
        throw new Error(`SICORE API request failed with status ${response.status}.`);
    }

    return response.json();
}

window.SICOREApiClient = { request };

export { request };

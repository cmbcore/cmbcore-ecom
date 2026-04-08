import axios from 'axios';

const TOKEN_KEY = 'admin_access_token';

// ── Token helpers ────────────────────────────────────────────────────────────
export function getStoredToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function setStoredToken(token) {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
    } else {
        localStorage.removeItem(TOKEN_KEY);
    }
}

// ── Axios instance ───────────────────────────────────────────────────────────
const api = axios.create({
    baseURL: '/api/admin',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Gắn Bearer token vào mỗi request
api.interceptors.request.use((config) => {
    const token = getStoredToken();

    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }

    return config;
});

// Redirect về login khi token hết hạn / không hợp lệ
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && window.location.pathname !== '/admin/login') {
            setStoredToken(null);
            window.location.href = '/admin/login';
        }

        return Promise.reject(error);
    },
);

export default api;
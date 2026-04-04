import axios from 'axios';

const api = axios.create({
    baseURL: '/api/admin',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

api.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && window.location.pathname !== '/admin/login') {
            window.location.href = '/admin/login';
        }

        return Promise.reject(error);
    },
);

export default api;
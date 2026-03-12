import axios from 'axios';

axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Accept'] = 'application/json';

const token = localStorage.getItem('sanctum_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Intercepteur : redirection si 401
axios.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 401) {
            localStorage.removeItem('sanctum_token');
            window.location.href = '/login';
        }
        return Promise.reject(err);
    }
);

window.axios = axios;
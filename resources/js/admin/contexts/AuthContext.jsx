import axios from 'axios';
import React, { createContext, startTransition, useEffect, useState } from 'react';
import { useLocale } from '../hooks/useLocale';
import api from '../services/api';

export const AuthContext = createContext({
    user: null,
    loading: true,
    loginLoading: false,
    loginError: '',
    login: async () => {},
    logout: async () => {},
});

export function AuthProvider({ children }) {
    const { t } = useLocale();
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [loginLoading, setLoginLoading] = useState(false);
    const [loginError, setLoginError] = useState('');

    useEffect(() => {
        let active = true;

        async function bootstrap() {
            try {
                const response = await api.get('/auth/me');

                if (!active) {
                    return;
                }

                startTransition(() => {
                    setUser(response.data.data);
                });
            } catch {
                if (!active) {
                    return;
                }

                startTransition(() => {
                    setUser(null);
                });
            } finally {
                if (active) {
                    setLoading(false);
                }
            }
        }

        bootstrap();

        return () => {
            active = false;
        };
    }, []);

    const login = async (payload) => {
        setLoginLoading(true);
        setLoginError('');

        try {
            await axios.get('/sanctum/csrf-cookie', {
                baseURL: window.location.origin,
                withCredentials: true,
            });

            const response = await api.post('/auth/login', payload);
            startTransition(() => {
                setUser(response.data.data);
            });
        } catch (error) {
            const nextError = error.response?.data?.message
                ?? error.response?.data?.errors?.email?.[0]
                ?? t('auth.errors.generic');
            setLoginError(nextError);
            throw error;
        } finally {
            setLoginLoading(false);
        }
    };

    const logout = async () => {
        try {
            await api.post('/auth/logout');
        } finally {
            startTransition(() => {
                setUser(null);
            });
        }
    };

    return (
        <AuthContext.Provider value={{ user, loading, loginLoading, loginError, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
}

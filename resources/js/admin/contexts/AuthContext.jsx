import React, { createContext, startTransition, useEffect, useState } from 'react';
import { useLocale } from '../hooks/useLocale';
import api, { getStoredToken, setStoredToken } from '../services/api';

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

    // Bootstrap: nếu đã có token trong localStorage → lấy thông tin user
    useEffect(() => {
        let active = true;

        async function bootstrap() {
            // Không có token → chắc chắn chưa đăng nhập
            if (!getStoredToken()) {
                setLoading(false);
                return;
            }

            try {
                const response = await api.get('/auth/me');

                if (!active) return;

                startTransition(() => {
                    setUser(response.data.data);
                });
            } catch {
                if (!active) return;

                // Token không hợp lệ → xoá
                setStoredToken(null);
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
            // Không cần /sanctum/csrf-cookie — dùng Bearer token
            const response = await api.post('/auth/login', payload);
            const { token, user: userData } = response.data.data;

            // Lưu token vào localStorage
            setStoredToken(token);

            startTransition(() => {
                setUser(userData);
            });
        } catch (error) {
            const nextError =
                error.response?.data?.message ??
                error.response?.data?.errors?.email?.[0] ??
                t('auth.errors.generic');
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
            // Xoá token dù API có lỗi hay không
            setStoredToken(null);
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

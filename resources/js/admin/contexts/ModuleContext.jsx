import React, { createContext, startTransition, useEffect, useState } from 'react';
import api from '../services/api';
import { useAuth } from '../hooks/useAuth';

export const ModuleContext = createContext({
    modules: [],
    menuItems: [],
    pages: {},
    loading: true,
});

export function ModuleProvider({ children }) {
    const { user } = useAuth();
    const [modules, setModules] = useState([]);
    const [menuItems, setMenuItems] = useState([]);
    const [pages, setPages] = useState({});
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let active = true;

        async function fetchModules() {
            if (!user) {
                startTransition(() => {
                    setModules([]);
                    setMenuItems([]);
                    setPages({});
                });
                setLoading(false);
                return;
            }

            try {
                const response = await api.get('/modules');
                const payload = response.data.data;

                if (!active) {
                    return;
                }

                startTransition(() => {
                    setModules(payload.modules ?? []);
                    setMenuItems(payload.menus ?? []);
                    setPages(payload.pages ?? {});
                });
            } catch {
                if (!active) {
                    return;
                }

                startTransition(() => {
                    setModules([]);
                    setMenuItems([]);
                    setPages({});
                });
            } finally {
                if (active) {
                    setLoading(false);
                }
            }
        }

        fetchModules();

        return () => {
            active = false;
        };
    }, [user]);

    return (
        <ModuleContext.Provider value={{ modules, menuItems, pages, loading }}>
            {children}
        </ModuleContext.Provider>
    );
}

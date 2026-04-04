import React from 'react';
import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { Spin } from 'antd';
import { AuthProvider } from './contexts/AuthContext';
import { ModuleProvider } from './contexts/ModuleContext';
import { useAuth } from './hooks/useAuth';
import { useModules } from './hooks/useModules';
import AdminLayout from './layouts/AdminLayout';
import AuthLayout from './layouts/AuthLayout';
import { ModulePageRenderer, normalizeAdminRoute } from './modulePages';
import Dashboard from './pages/Dashboard';
import Login from './pages/Login';
import NotFound from './pages/NotFound';

function RouteGate() {
    const location = useLocation();
    const { loading, user } = useAuth();
    const { loading: modulesLoading } = useModules();

    if (loading || (user && modulesLoading)) {
        return (
            <div className="admin-loading-screen">
                <Spin size="large" />
            </div>
        );
    }

    if (!user) {
        return <Navigate replace to="/admin/login" state={{ from: location.pathname }} />;
    }

    return <AdminLayout><Outlet /></AdminLayout>;
}

function LoginGate() {
    const { loading, user } = useAuth();

    if (loading) {
        return (
            <div className="admin-loading-screen">
                <Spin size="large" />
            </div>
        );
    }

    if (user) {
        return <Navigate replace to="/admin" />;
    }

    return <AuthLayout><Login /></AuthLayout>;
}

function AppRoutes() {
    const { pages } = useModules();

    return (
        <Routes>
            <Route path="/admin/login" element={<LoginGate />} />
            <Route path="/admin/*" element={<RouteGate />}>
                <Route index element={<Dashboard />} />
                {Object.entries(pages).map(([route, componentPath]) => {
                    const routePath = normalizeAdminRoute(route);

                    if (!routePath) {
                        return null;
                    }

                    return (
                        <Route
                            key={route}
                            path={routePath}
                            element={<ModulePageRenderer componentPath={componentPath} />}
                        />
                    );
                })}
                <Route path="*" element={<NotFound />} />
            </Route>
            <Route path="*" element={<Navigate replace to="/admin" />} />
        </Routes>
    );
}

export default function App() {
    return (
        <AuthProvider>
            <ModuleProvider>
                <AppRoutes />
            </ModuleProvider>
        </AuthProvider>
    );
}

import React from 'react';
import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import LoadingScreen from './components/ui/LoadingScreen';
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
import Profile from './pages/Profile';

function RouteGate() {
    const location = useLocation();
    const { loading, user } = useAuth();
    const { loading: modulesLoading } = useModules();

    // Chỉ block toàn màn hình khi auth chưa xác định
    if (loading) {
        return <LoadingScreen />;
    }

    if (!user) {
        return <Navigate replace to="/admin/login" state={{ from: location.pathname }} />;
    }

    // Khi user đã xác thực, render layout ngay — module sẽ lazy load bên trong
    return <AdminLayout><Outlet /></AdminLayout>;
}

function LoginGate() {
    const { loading, user } = useAuth();
    const location = useLocation();
    // Lấy URL mà user đã truy cập trước khi bị redirect về login
    const from = location.state?.from || '/admin';

    if (loading) {
        return <LoadingScreen />;
    }

    if (user) {
        return <Navigate replace to={from} />;
    }

    return <AuthLayout><Login /></AuthLayout>;
}

function AppRoutes() {
    const { pages, loading: modulesLoading } = useModules();

    return (
        <Routes>
            <Route path="/admin/login" element={<LoginGate />} />
            <Route path="/admin/*" element={<RouteGate />}>
                <Route index element={<Dashboard />} />
                <Route path="profile" element={<Profile />} />
                {!modulesLoading && Object.entries(pages).map(([route, componentPath]) => {
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
                <Route path="*" element={modulesLoading ? <LoadingScreen panel /> : <NotFound />} />
            </Route>
            {/* Không redirect wildcard về /admin — để trình duyệt giữ URL gốc */}
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

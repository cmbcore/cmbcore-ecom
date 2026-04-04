import React, { Suspense, lazy } from 'react';
import { Result, Spin } from 'antd';
import { useLocale } from './hooks/useLocale';

const modulePageImports = import.meta.glob([
    '/modules/**/Resources/js/pages/**/*.jsx',
    '/plugins/**/resources/js/pages/**/*.jsx',
]);

const modulePageRegistry = Object.fromEntries(
    Object.entries(modulePageImports).map(([filePath, loader]) => [
        filePath.replace(/^\//, ''),
        lazy(loader),
    ]),
);

export function normalizeAdminRoute(route) {
    if (!route || route === '/admin') {
        return '';
    }

    return route.replace(/^\/admin\/?/, '');
}

export function resolveModulePage(componentPath) {
    if (!componentPath) {
        return null;
    }

    const normalizedPath = componentPath.replace(/\\/g, '/').replace(/^\/+/, '');

    return modulePageRegistry[normalizedPath] ?? null;
}

function ModulePageFallback() {
    return (
        <div className="admin-loading-screen admin-loading-screen--panel">
            <Spin size="large" />
        </div>
    );
}

export function ModulePageRenderer({ componentPath }) {
    const { t } = useLocale();
    const Component = resolveModulePage(componentPath);

    if (!Component) {
        return (
            <Result
                status="warning"
                title={t('system.module_page_missing')}
                subTitle={t('system.module_page_missing_detail', { component: componentPath })}
            />
        );
    }

    return (
        <Suspense fallback={<ModulePageFallback />}>
            <Component />
        </Suspense>
    );
}

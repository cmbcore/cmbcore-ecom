import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import { setChonkyDefaults } from 'chonky';
import { ChonkyIconFA } from 'chonky-icon-fontawesome';
import App from './App';
import { LocalizationProvider } from './contexts/LocalizationContext';
import { useLocale } from './hooks/useLocale';

setChonkyDefaults({
    iconComponent: ChonkyIconFA,
});

const rootElement = document.getElementById('admin-root');

function AdminRoot() {
    const { antdLocale } = useLocale();

    return (
        <BrowserRouter>
            <ConfigProvider
                locale={antdLocale}
                theme={{
                    token: {
                        colorPrimary: '#0f766e',
                        borderRadius: 14,
                    },
                }}
            >
                <App />
            </ConfigProvider>
        </BrowserRouter>
    );
}

if (rootElement) {
    ReactDOM.createRoot(rootElement).render(
        <React.StrictMode>
            <LocalizationProvider>
                <AdminRoot />
            </LocalizationProvider>
        </React.StrictMode>,
    );
}

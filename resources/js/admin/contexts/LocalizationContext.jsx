import React, { createContext, startTransition, useEffect, useState } from 'react';
import enUS from 'antd/locale/en_US';
import viVN from 'antd/locale/vi_VN';
import api from '../services/api';

const initialLocalization = window.__CMBCORE__?.localization ?? {
    current_locale: document.documentElement.lang || 'vi',
    supported_locales: [
        { code: 'vi', name: 'Tiếng Việt', native_name: 'Tiếng Việt', icon: 'fa-solid fa-earth-asia' },
        { code: 'en', name: 'English', native_name: 'English', icon: 'fa-solid fa-earth-americas' },
    ],
    translations: {},
};

const antdLocales = {
    vi: viVN,
    en: enUS,
};

function resolveTranslation(translations, key) {
    return key.split('.').reduce((value, part) => value?.[part], translations);
}

function interpolate(message, replacements = {}) {
    return Object.entries(replacements).reduce(
        (result, [key, value]) => result.replaceAll(`:${key}`, value),
        message,
    );
}

export const LocalizationContext = createContext({
    currentLocale: initialLocalization.current_locale,
    locales: initialLocalization.supported_locales,
    translations: initialLocalization.translations,
    antdLocale: antdLocales.vi,
    t: (key) => key,
    switchLocale: async () => {},
});

export function LocalizationProvider({ children }) {
    const [currentLocale, setCurrentLocale] = useState(initialLocalization.current_locale);
    const [locales, setLocales] = useState(initialLocalization.supported_locales);
    const [translations, setTranslations] = useState(initialLocalization.translations);

    useEffect(() => {
        api.defaults.headers.common['X-Locale'] = currentLocale;
        document.documentElement.lang = currentLocale;
    }, [currentLocale]);

    const t = (key, replacements = {}, fallback = key) => {
        const translation = resolveTranslation(translations, key);

        if (typeof translation !== 'string') {
            return fallback;
        }

        return interpolate(translation, replacements);
    };

    const switchLocale = async (locale) => {
        const response = await api.put('/localization', { locale });
        const payload = response.data.data;

        startTransition(() => {
            setCurrentLocale(payload.current_locale);
            setLocales(payload.supported_locales ?? []);
            setTranslations(payload.translations ?? {});
        });

        return response;
    };

    return (
        <LocalizationContext.Provider
            value={{
                currentLocale,
                locales,
                translations,
                antdLocale: antdLocales[currentLocale] ?? enUS,
                t,
                switchLocale,
            }}
        >
            {children}
        </LocalizationContext.Provider>
    );
}

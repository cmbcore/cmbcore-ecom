import React from 'react';
import { Button, Dropdown, message } from 'antd';
import { useLocale } from '@admin/hooks/useLocale';
import FontIcon from './FontIcon';

export default function LanguageSwitcher({ compact = false }) {
    const { currentLocale, locales, switchLocale, t } = useLocale();
    const currentLocaleOption = locales.find((locale) => locale.code === currentLocale) ?? locales[0];

    const items = locales.map((locale) => ({
        key: locale.code,
        label: (
            <span className="language-switcher__item">
                <FontIcon name={locale.icon} />
                <span>{locale.native_name}</span>
            </span>
        ),
    }));

    async function handleMenuClick({ key }) {
        try {
            const response = await switchLocale(key);
            message.success(response.data.message);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('locale.errors.unsupported'));
        }
    }

    return (
        <Dropdown
            menu={{
                items,
                selectedKeys: currentLocaleOption ? [currentLocaleOption.code] : [],
                onClick: handleMenuClick,
            }}
            trigger={['click']}
        >
            <Button type={compact ? 'text' : 'default'} className="language-switcher">
                <FontIcon name={currentLocaleOption?.icon ?? 'locale'} />
                <span>{currentLocaleOption?.native_name ?? t('locale.label')}</span>
            </Button>
        </Dropdown>
    );
}

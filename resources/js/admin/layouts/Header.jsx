import React from 'react';
import { Button, Layout, Tag } from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import { useAuth } from '../hooks/useAuth';
import { useLocale } from '../hooks/useLocale';

const { Header } = Layout;

export default function HeaderBar() {
    const { logout, user } = useAuth();
    const { t } = useLocale();

    return (
        <Header className="admin-header">
            <div>
                <div className="admin-header__eyebrow">{t('layout.eyebrow')}</div>
                <div className="admin-header__title" style={{ marginBottom: '25px' }}>{t('layout.hello', { name: user?.name ?? t('layout.guest') })}</div>
            </div>
            <div className="admin-header__actions">
                <Tag color="geekblue">{t(`roles.${user?.role ?? 'guest'}`)}</Tag>
                <Button icon={<FontIcon name="logout" />} onClick={logout}>
                    {t('layout.logout')}
                </Button>
            </div>
        </Header>
    );
}

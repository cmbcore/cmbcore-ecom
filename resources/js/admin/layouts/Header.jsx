import React from 'react';
import { Avatar, Button, Dropdown, Layout, Space, Tag } from 'antd';
import {
    LogoutOutlined,
    UserOutlined,
} from '@ant-design/icons';
import FontIcon from '@admin/components/ui/FontIcon';
import { useAuth } from '../hooks/useAuth';
import { useLocale } from '../hooks/useLocale';
import { useNavigate } from 'react-router-dom';

const { Header } = Layout;

const ROLE_COLOR = { admin: 'purple', editor: 'blue', viewer: 'cyan' };
const ROLE_LABEL = { admin: 'Quản trị viên', editor: 'Biên tập viên', viewer: 'Người xem' };

export default function HeaderBar() {
    const { logout, user } = useAuth();
    const { t } = useLocale();
    const navigate = useNavigate();

    const avatarSrc = user?.avatar
        ? (user.avatar.startsWith('http') ? user.avatar : `/storage/${user.avatar}`)
        : null;

    const menuItems = [
        {
            key: 'profile',
            label: 'Hồ sơ cá nhân',
            icon: <UserOutlined />,
            onClick: () => navigate('/admin/profile'),
        },
        { type: 'divider' },
        {
            key: 'logout',
            label: t('layout.logout'),
            icon: <LogoutOutlined />,
            danger: true,
            onClick: logout,
        },
    ];

    return (
        <Header className="admin-header">
            <div>
                <div className="admin-header__eyebrow">{t('layout.eyebrow')}</div>
                <div className="admin-header__title">{t('layout.hello', { name: user?.name ?? t('layout.guest') })}</div>
            </div>
            <div className="admin-header__actions">
                <Tag color={ROLE_COLOR[user?.role] ?? 'default'}>
                    {ROLE_LABEL[user?.role] ?? user?.role}
                </Tag>

                <Dropdown menu={{ items: menuItems }} placement="bottomRight" arrow>
                    <Space style={{ cursor: 'pointer', gap: 8 }}>
                        <Avatar
                            size={36}
                            src={avatarSrc}
                            icon={<UserOutlined />}
                            style={{ background: '#ede9fe', color: '#7c3aed', cursor: 'pointer' }}
                        />
                    </Space>
                </Dropdown>
            </div>
        </Header>
    );
}

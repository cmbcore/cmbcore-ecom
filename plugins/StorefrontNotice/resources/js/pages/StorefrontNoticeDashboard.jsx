import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Descriptions, Empty, Space, Tag, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

export default function StorefrontNoticeDashboard() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [configuration, setConfiguration] = useState(null);
    const [loading, setLoading] = useState(true);

    const fetchConfiguration = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/plugins/storefront-notice/settings');
            setConfiguration(response.data.data);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('plugins.storefront_notice.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [t]);

    useEffect(() => {
        fetchConfiguration();
    }, [fetchConfiguration]);

    return (
        <div>
            <PageHeader
                title={t('plugins.storefront_notice.title')}
                description={t('plugins.storefront_notice.description')}
                extra={[
                    {
                        label: t('plugins.actions.configure'),
                        icon: <FontIcon name="configure" />,
                        onClick: () => navigate('/admin/plugins/storefront-notice/settings'),
                    },
                ]}
            />

            <Card loading={loading} bordered={false}>
                {!loading && !configuration ? (
                    <Empty description={t('plugins.storefront_notice.empty')} />
                ) : (
                    <Space direction="vertical" size={24} style={{ display: 'flex' }}>
                        <Descriptions bordered column={1} size="small">
                            <Descriptions.Item label={t('plugins.storefront_notice.fields.status')}>
                                <Tag color={configuration?.plugin?.is_active ? 'success' : 'default'}>
                                    {configuration?.plugin?.is_active ? t('plugins.status.active') : t('plugins.status.inactive')}
                                </Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label={t('plugins.storefront_notice.fields.headline')}>
                                {configuration?.settings?.headline ?? '-'}
                            </Descriptions.Item>
                            <Descriptions.Item label={t('plugins.storefront_notice.fields.message')}>
                                {configuration?.settings?.message ?? '-'}
                            </Descriptions.Item>
                            <Descriptions.Item label={t('plugins.storefront_notice.fields.tone')}>
                                {configuration?.settings?.tone === 'neutral'
                                    ? t('plugins.storefront_notice.tones.neutral')
                                    : t('plugins.storefront_notice.tones.accent')}
                            </Descriptions.Item>
                            <Descriptions.Item label={t('plugins.storefront_notice.fields.icon')}>
                                <Space>
                                    <FontIcon name={configuration?.settings?.icon ?? 'fa-solid fa-bolt'} />
                                    <span>{configuration?.settings?.icon ?? 'fa-solid fa-bolt'}</span>
                                </Space>
                            </Descriptions.Item>
                        </Descriptions>

                        <Card type="inner" title={t('plugins.storefront_notice.preview_title')}>
                            <div className="plugin-settings-preview">
                                <Space>
                                    <FontIcon name={configuration?.settings?.icon ?? 'fa-solid fa-bolt'} />
                                    <strong>{configuration?.settings?.headline}</strong>
                                </Space>
                                <p>{configuration?.settings?.message}</p>
                            </div>
                        </Card>

                        <Button icon={<FontIcon name="refresh" />} onClick={fetchConfiguration}>
                            {t('common.refresh')}
                        </Button>
                    </Space>
                )}
            </Card>
        </div>
    );
}

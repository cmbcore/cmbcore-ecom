import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Empty, Form, Input, InputNumber, Select, Space, Switch, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

function renderField(field) {
    switch (field.type) {
        case 'number':
            return <InputNumber min={field.min} max={field.max} style={{ width: '100%' }} />;
        case 'boolean':
            return <Switch />;
        case 'select':
            return (
                <Select
                    options={(field.options ?? []).map((option) => ({
                        value: option.value,
                        label: option.label,
                    }))}
                />
            );
        default:
            return <Input />;
    }
}

export default function PluginSettings() {
    const navigate = useNavigate();
    const { alias } = useParams();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [configuration, setConfiguration] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);

    const fetchConfiguration = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get(`/plugins/${alias}/settings`);
            const payload = response.data.data;
            setConfiguration(payload);
            form.setFieldsValue({
                settings: payload.settings ?? {},
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('plugins.messages.settings_failed'));
            navigate('/admin/plugins');
        } finally {
            setLoading(false);
        }
    }, [alias, form, navigate, t]);

    async function handleSubmit(values) {
        setSubmitting(true);

        try {
            const response = await api.put(`/plugins/${alias}/settings`, values);
            const payload = response.data.data;
            setConfiguration(payload);
            form.setFieldsValue({
                settings: payload.settings ?? {},
            });
            message.success(t('plugins.messages.updated'));
        } catch (error) {
            message.error(error.response?.data?.message ?? t('plugins.messages.update_failed'));
        } finally {
            setSubmitting(false);
        }
    }

    useEffect(() => {
        fetchConfiguration();
    }, [fetchConfiguration]);

    return (
        <div>
            <PageHeader
                title={t('plugins.settings_title', { name: configuration?.plugin?.name ?? alias })}
                description={t('plugins.settings_description')}
                extra={[
                    { label: t('plugins.actions.back_to_list'), icon: <FontIcon name="refresh" />, onClick: () => navigate('/admin/plugins') },
                ]}
            />

            <Card loading={loading} bordered={false}>
                {!loading && (configuration?.settings_schema ?? []).length === 0 ? (
                    <Empty description={t('plugins.settings_empty')} />
                ) : (
                    <Form form={form} layout="vertical" onFinish={handleSubmit}>
                        <Space direction="vertical" size={24} style={{ display: 'flex' }}>
                            {(configuration?.settings_schema ?? []).map((group) => (
                                <Card key={group.group} type="inner" title={group.label}>
                                    {group.fields?.map((field) => (
                                        <Form.Item
                                            key={field.key}
                                            label={field.label}
                                            name={['settings', field.key]}
                                            valuePropName={field.type === 'boolean' ? 'checked' : 'value'}
                                        >
                                            {renderField(field)}
                                        </Form.Item>
                                    ))}
                                </Card>
                            ))}

                            <div className="category-form-card__actions">
                                <Button onClick={() => navigate('/admin/plugins')}>{t('common.cancel')}</Button>
                                <Button
                                    type="primary"
                                    htmlType="submit"
                                    loading={submitting}
                                    icon={<FontIcon name="save" />}
                                >
                                    {t('plugins.actions.save_settings')}
                                </Button>
                            </div>
                        </Space>
                    </Form>
                )}
            </Card>
        </div>
    );
}

import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Card, Col, Empty, Modal, Popconfirm, Row, Space, Tag, Upload, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

function firstPageRoute(plugin) {
    return Object.keys(plugin.admin?.pages ?? {})[0] ?? '';
}

function statusTag(plugin, t) {
    if (!plugin.requirements_satisfied) {
        return <Tag color="warning">{t('plugins.status.unavailable')}</Tag>;
    }

    return (
        <Tag color={plugin.is_active ? 'success' : 'default'}>
            {plugin.is_active ? t('plugins.status.active') : t('plugins.status.inactive')}
        </Tag>
    );
}

export default function PluginList() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [plugins, setPlugins] = useState([]);
    const [loading, setLoading] = useState(true);
    const [processingAlias, setProcessingAlias] = useState('');
    const [installing, setInstalling] = useState(false);
    const pendingFileRef = useRef(null);

    const fetchPlugins = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/plugins');
            setPlugins(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('plugins.messages.list_failed'));
        } finally {
            setLoading(false);
        }
    }, [t]);

    async function handleToggle(plugin) {
        setProcessingAlias(plugin.alias);

        try {
            const endpoint = plugin.is_active ? 'disable' : 'enable';
            await api.put(`/plugins/${plugin.alias}/${endpoint}`);
            message.success(t(plugin.is_active ? 'plugins.messages.disabled' : 'plugins.messages.enabled'));
            await fetchPlugins();
        } catch (error) {
            message.error(
                error.response?.data?.message
                ?? t(plugin.is_active ? 'plugins.messages.disable_failed' : 'plugins.messages.enable_failed'),
            );
        } finally {
            setProcessingAlias('');
        }
    }

    async function submitInstall(file, force = false) {
        const formData = new FormData();
        formData.append('package', file);

        if (force) {
            formData.append('force', '1');
        }

        setInstalling(true);

        try {
            await api.post('/plugins/install', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            message.success(t('plugins.messages.installed'));
            await fetchPlugins();
        } catch (error) {
            const duplicateErrors = error.response?.data?.errors?.duplicate;

            if (error.response?.status === 422 && duplicateErrors?.length > 0) {
                let duplicateInfo;

                try {
                    duplicateInfo = JSON.parse(duplicateErrors[0]);
                } catch {
                    duplicateInfo = null;
                }

                if (duplicateInfo) {
                    pendingFileRef.current = file;

                    Modal.confirm({
                        title: t('plugins.messages.duplicate_title', {}, 'Plugin đã tồn tại'),
                        content: t('plugins.messages.duplicate_description', {
                            name: duplicateInfo.name,
                            version: duplicateInfo.version,
                        }, `Plugin "${duplicateInfo.name}" (v${duplicateInfo.version}) đã tồn tại. Bạn có muốn ghi đè không?`),
                        okText: t('plugins.messages.duplicate_confirm', {}, 'Ghi đè'),
                        cancelText: t('common.cancel'),
                        okButtonProps: { danger: true },
                        onOk: async () => {
                            if (pendingFileRef.current) {
                                await submitInstall(pendingFileRef.current, true);
                                pendingFileRef.current = null;
                            }
                        },
                        onCancel: () => {
                            pendingFileRef.current = null;
                        },
                    });

                    return;
                }
            }

            message.error(error.response?.data?.message ?? t('plugins.messages.install_failed'));
            throw error;
        } finally {
            setInstalling(false);
        }
    }

    async function handleInstall(file) {
        await submitInstall(file, false);
    }

    async function handleDelete(alias) {
        try {
            await api.delete(`/plugins/${alias}`);
            message.success(t('plugins.messages.deleted', {}, 'Đã xóa plugin.'));
            await fetchPlugins();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('plugins.messages.delete_failed', {}, 'Không xóa được plugin.'));
        }
    }

    useEffect(() => {
        fetchPlugins();
    }, [fetchPlugins]);

    return (
        <div>
            <PageHeader
                title={t('plugins.title')}
                description={t('plugins.description')}
                extra={[
                    { label: t('common.refresh'), icon: <FontIcon name="refresh" />, onClick: fetchPlugins },
                ]}
            />

            <Space style={{ marginBottom: 16 }}>
                <Upload
                    accept=".zip"
                    showUploadList={false}
                    maxCount={1}
                    customRequest={async ({ file, onSuccess, onError }) => {
                        try {
                            await handleInstall(file);
                            onSuccess?.({});
                        } catch (error) {
                            onError?.(error);
                        }
                    }}
                >
                    <Button icon={<FontIcon name="upload" />} loading={installing}>
                        {t('plugins.actions.install')}
                    </Button>
                </Upload>
            </Space>

            {plugins.length === 0 && !loading ? (
                <Empty description={t('plugins.empty')} />
            ) : (
                <Row gutter={[16, 16]}>
                    {plugins.map((plugin) => {
                        const pluginPageRoute = firstPageRoute(plugin);

                        return (
                            <Col key={plugin.alias} xs={24} lg={12}>
                                <Card loading={loading} title={plugin.name}>
                                    <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                                        <div>{plugin.description}</div>
                                        <Space wrap>
                                            {statusTag(plugin, t)}
                                            <Tag>{plugin.version}</Tag>
                                            {(plugin.requires?.modules ?? []).map((moduleAlias) => (
                                                <Tag key={moduleAlias}>{moduleAlias}</Tag>
                                            ))}
                                        </Space>
                                        <Space wrap>
                                            <Button
                                                type={plugin.is_active ? 'default' : 'primary'}
                                                icon={<FontIcon name={plugin.is_active ? 'disable' : 'activate'} />}
                                                loading={processingAlias === plugin.alias}
                                                disabled={!plugin.requirements_satisfied && !plugin.is_active}
                                                onClick={() => handleToggle(plugin)}
                                            >
                                                {plugin.is_active ? t('plugins.actions.disable') : t('plugins.actions.enable')}
                                            </Button>
                                            <Button
                                                icon={<FontIcon name="configure" />}
                                                onClick={() => navigate(`/admin/plugins/${plugin.alias}/settings`)}
                                            >
                                                {t('plugins.actions.configure')}
                                            </Button>
                                            {plugin.is_active && pluginPageRoute ? (
                                                <Button
                                                    icon={<FontIcon name="preview" />}
                                                    onClick={() => navigate(pluginPageRoute)}
                                                >
                                                    {t('plugins.actions.open')}
                                                </Button>
                                            ) : null}
                                            <Popconfirm
                                                {...deletePopconfirmProps(
                                                    () => handleDelete(plugin.alias),
                                                    {
                                                        title: t('plugins.confirm_delete.title', {}, 'Xóa plugin?'),
                                                        description: plugin.is_active
                                                            ? t('plugins.confirm_delete.active_warning', {}, 'Hãy tắt plugin trước khi xóa.')
                                                            : t('plugins.confirm_delete.description', { name: plugin.name }, `Plugin ${plugin.name} sẽ bị xóa khỏi hệ thống.`),
                                                    },
                                                )}
                                                disabled={plugin.is_active}
                                            >
                                                <Button
                                                    danger
                                                    icon={<FontIcon name="delete" />}
                                                    disabled={plugin.is_active}
                                                    title={plugin.is_active ? t('plugins.confirm_delete.active_warning', {}, 'Hãy tắt plugin trước khi xóa.') : undefined}
                                                >
                                                    {t('common.delete')}
                                                </Button>
                                            </Popconfirm>
                                        </Space>
                                    </Space>
                                </Card>
                            </Col>
                        );
                    })}
                </Row>
            )}
        </div>
    );
}

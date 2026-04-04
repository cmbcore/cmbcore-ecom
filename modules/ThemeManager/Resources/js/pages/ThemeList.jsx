import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Card, Col, Empty, Modal, Popconfirm, Row, Space, Tag, Upload, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

export default function ThemeList() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [themes, setThemes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activatingAlias, setActivatingAlias] = useState('');
    const [installing, setInstalling] = useState(false);
    const pendingFileRef = useRef(null);

    const fetchThemes = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/themes');
            setThemes(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('themes.messages.list_failed'));
        } finally {
            setLoading(false);
        }
    }, [t]);

    async function handleActivate(alias) {
        setActivatingAlias(alias);

        try {
            await api.put(`/themes/${alias}/activate`);
            message.success(t('themes.messages.activated'));
            await fetchThemes();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('themes.messages.activate_failed'));
        } finally {
            setActivatingAlias('');
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
            await api.post('/themes/install', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            message.success(t('themes.messages.installed'));
            await fetchThemes();
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

                    const activeWarning = duplicateInfo.is_active
                        ? `\n⚠️ Theme này đang được kích hoạt.`
                        : '';

                    Modal.confirm({
                        title: t('themes.messages.duplicate_title', {}, 'Theme đã tồn tại'),
                        content: t('themes.messages.duplicate_description', {
                            name: duplicateInfo.name,
                            version: duplicateInfo.version,
                        }, `Theme "${duplicateInfo.name}" (v${duplicateInfo.version}) đã tồn tại. Bạn có muốn ghi đè không?${activeWarning}`),
                        okText: t('themes.messages.duplicate_confirm', {}, 'Ghi đè'),
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

            message.error(error.response?.data?.message ?? t('themes.messages.install_failed'));
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
            await api.delete(`/themes/${alias}`);
            message.success(t('themes.messages.deleted', {}, 'Đã xóa theme.'));
            await fetchThemes();
        } catch (error) {
            message.error(error.response?.data?.message ?? t('themes.messages.delete_failed', {}, 'Không xóa được theme.'));
        }
    }

    useEffect(() => {
        fetchThemes();
    }, [fetchThemes]);

    return (
        <div>
            <PageHeader
                title={t('themes.title')}
                description={t('themes.description')}
                extra={[
                    { label: t('common.refresh'), icon: <FontIcon name="refresh" />, onClick: fetchThemes },
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
                        {t('themes.actions.install')}
                    </Button>
                </Upload>
            </Space>

            {themes.length === 0 && !loading ? (
                <Empty description={t('themes.empty')} />
            ) : (
                <Row gutter={[16, 16]}>
                    {themes.map((theme) => (
                        <Col key={theme.alias} xs={24} lg={12}>
                            <Card loading={loading} title={theme.name}>
                                <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                                    <div>{theme.description}</div>
                                    <Space wrap>
                                        <Tag color={theme.is_active ? 'success' : 'default'}>
                                            {theme.is_active ? t('themes.status.active') : t('themes.status.inactive')}
                                        </Tag>
                                        <Tag>{theme.version}</Tag>
                                        {theme.supports?.map((feature) => (
                                            <Tag key={feature}>{feature}</Tag>
                                        ))}
                                    </Space>
                                    <Space wrap>
                                        {!theme.is_active ? (
                                            <Button
                                                type="primary"
                                                icon={<FontIcon name="activate" />}
                                                loading={activatingAlias === theme.alias}
                                                onClick={() => handleActivate(theme.alias)}
                                            >
                                                {t('themes.actions.activate')}
                                            </Button>
                                        ) : null}
                                        <Button
                                            icon={<FontIcon name="configure" />}
                                            onClick={() => navigate(`/admin/themes/${theme.alias}/settings`)}
                                        >
                                            {t('themes.actions.configure')}
                                        </Button>
                                        <Button
                                            icon={<FontIcon name="preview" />}
                                            onClick={() => navigate(`/admin/themes/${theme.alias}/preview`)}
                                        >
                                            {t('themes.actions.preview')}
                                        </Button>
                                        <Popconfirm
                                            {...deletePopconfirmProps(
                                                () => handleDelete(theme.alias),
                                                {
                                                    title: t('themes.confirm_delete.title', {}, 'Xóa theme?'),
                                                    description: theme.is_active
                                                        ? t('themes.confirm_delete.active_warning', {}, 'Hãy kích hoạt theme khác trước khi xóa theme này.')
                                                        : t('themes.confirm_delete.description', { name: theme.name }, `Theme ${theme.name} sẽ bị xóa khỏi hệ thống.`),
                                                },
                                            )}
                                            disabled={theme.is_active}
                                        >
                                            <Button
                                                danger
                                                icon={<FontIcon name="delete" />}
                                                disabled={theme.is_active}
                                                title={theme.is_active ? t('themes.confirm_delete.active_warning', {}, 'Hãy kích hoạt theme khác trước khi xóa.') : undefined}
                                            >
                                                {t('common.delete')}
                                            </Button>
                                        </Popconfirm>
                                    </Space>
                                </Space>
                            </Card>
                        </Col>
                    ))}
                </Row>
            )}
        </div>
    );
}

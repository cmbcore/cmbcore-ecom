import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    Alert,
    Badge,
    Button,
    Card,
    Col,
    Divider,
    Form,
    Row,
    Select,
    Space,
    Tabs,
    Tag,
    Tooltip,
    Typography,
    message,
} from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import ThemeMenuEditor from '../components/ThemeMenuEditor';
import ThemeSettingField from '../components/ThemeSettingField';
import { buildThemeSettingsPayload } from '../components/themeSettingsPayload';

const { Paragraph, Text, Title } = Typography;

// ─── Tab icon mapping ────────────────────────────────────────────────────────

const GROUP_ICONS = {
    general: 'settings',
    branding: 'palette',
    header: 'web',
    footer: 'dock_to_bottom',
    hero: 'image',
    homepage: 'home',
    colors: 'color_lens',
    typography: 'text_fields',
    layout: 'dashboard',
    social: 'share',
    seo: 'search',
    advanced: 'code',
    menus: 'menu',
};

function getGroupIcon(groupKey) {
    const normalized = (groupKey || '').toLowerCase().replace(/[\s_-]/g, '');

    for (const [key, icon] of Object.entries(GROUP_ICONS)) {
        if (normalized.includes(key)) {
            return icon;
        }
    }

    return 'tune';
}

// ─── Preview target options ──────────────────────────────────────────────────

const PREVIEW_TARGETS = [
    { label: 'Trang chủ', value: 'home' },
    { label: 'Trang sản phẩm', value: 'product' },
    { label: 'Danh mục', value: 'category' },
    { label: 'Blog', value: 'blog' },
];

// ─── Viewport sizes ──────────────────────────────────────────────────────────

const VIEWPORTS = [
    { label: 'Desktop', icon: 'desktop_windows', width: '100%' },
    { label: 'Tablet', icon: 'tablet', width: '768px' },
    { label: 'Mobile', icon: 'smartphone', width: '375px' },
];

// ─── Hook: debounced preview sync ────────────────────────────────────────────

function usePreviewSync(alias, form, enabled) {
    const [previewUrl, setPreviewUrl] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const debounceRef = useRef(null);
    const tokenRef = useRef(null);
    const [previewTarget, setPreviewTarget] = useState('home');

    const syncPreview = useCallback(async (target) => {
        if (!enabled || !alias) return;

        setPreviewLoading(true);

        try {
            const values = form.getFieldsValue(true);
            const payload = buildThemeSettingsPayload(values);

            const response = await api.post(`/themes/${alias}/preview-session`, {
                settings: payload.settings,
                menus: payload.menus,
                preview_target: target ?? previewTarget,
            });

            tokenRef.current = response.data.token;
            setPreviewUrl(response.data.preview_url);
        } catch {
            // Silent – preview errors should not interrupt editing
        } finally {
            setPreviewLoading(false);
        }
    }, [alias, enabled, form, previewTarget]);

    // Debounced trigger on form values change
    const triggerSync = useCallback((target) => {
        if (debounceRef.current) {
            window.clearTimeout(debounceRef.current);
        }

        debounceRef.current = window.setTimeout(() => {
            syncPreview(target);
        }, 600);
    }, [syncPreview]);

    // Initial sync when preview is first opened
    const initPreview = useCallback(() => {
        syncPreview(previewTarget);
    }, [syncPreview, previewTarget]);

    const handleTargetChange = useCallback((target) => {
        setPreviewTarget(target);
        triggerSync(target);
    }, [triggerSync]);

    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                window.clearTimeout(debounceRef.current);
            }
        };
    }, []);

    return {
        previewUrl,
        previewLoading,
        previewTarget,
        initPreview,
        triggerSync,
        handleTargetChange,
    };
}

// ─── PreviewPanel component ──────────────────────────────────────────────────

function PreviewPanel({ alias, form, onClose }) {
    const iframeRef = useRef(null);
    const [viewport, setViewport] = useState(0); // index into VIEWPORTS

    const {
        previewUrl,
        previewLoading,
        previewTarget,
        initPreview,
        triggerSync,
        handleTargetChange,
    } = usePreviewSync(alias, form, true);

    // Load initial preview when panel opens
    useEffect(() => {
        initPreview();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const currentViewport = VIEWPORTS[viewport];

    return (
        <div className="theme-preview-panel">
            {/* Toolbar */}
            <div className="theme-preview-panel__toolbar">
                <div className="theme-preview-panel__toolbar-left">
                    <Select
                        value={previewTarget}
                        onChange={handleTargetChange}
                        options={PREVIEW_TARGETS}
                        size="small"
                        style={{ width: 160 }}
                    />
                    <Tooltip title="Tải lại preview">
                        <Button
                            size="small"
                            icon={<FontIcon name="refresh" />}
                            loading={previewLoading}
                            onClick={() => triggerSync(previewTarget)}
                        />
                    </Tooltip>
                </div>

                <div className="theme-preview-panel__viewport-switcher">
                    {VIEWPORTS.map((vp, i) => (
                        <Tooltip key={vp.label} title={vp.label}>
                            <Button
                                size="small"
                                type={viewport === i ? 'primary' : 'default'}
                                icon={<FontIcon name={vp.icon} />}
                                onClick={() => setViewport(i)}
                            />
                        </Tooltip>
                    ))}
                </div>

                <Tooltip title="Đóng preview">
                    <Button size="small" icon={<FontIcon name="close" />} onClick={onClose} />
                </Tooltip>
            </div>

            {/* iframe area */}
            <div className="theme-preview-panel__stage">
                <div
                    className="theme-preview-panel__frame-wrapper"
                    style={{ width: currentViewport.width }}
                >
                    {previewUrl ? (
                        <iframe
                            ref={iframeRef}
                            key={previewUrl}
                            src={previewUrl}
                            title="Xem trước theme"
                            className="theme-preview-panel__frame"
                            sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                        />
                    ) : (
                        <div className="theme-preview-panel__placeholder">
                            <FontIcon name="preview" />
                            <Text type="secondary">
                                {previewLoading ? 'Đang tải preview...' : 'Bấm tải lại để xem preview.'}
                            </Text>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

// ─── Main component ──────────────────────────────────────────────────────────

export default function ThemeSettings() {
    const navigate = useNavigate();
    const { alias } = useParams();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [configuration, setConfiguration] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [activeTab, setActiveTab] = useState(null);
    const [showPreview, setShowPreview] = useState(false);

    const stats = useMemo(() => ({
        groups: configuration?.settings_schema?.length ?? 0,
        fields: (configuration?.settings_schema ?? []).reduce(
            (total, group) => total + (group.fields?.length ?? 0),
            0,
        ),
        menus: configuration?.menus?.length ?? 0,
    }), [configuration]);

    const tRef = useRef(t);
    tRef.current = t;

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        async function fetchConfiguration() {
            try {
                const response = await api.get(`/themes/${alias}/settings`);
                if (cancelled) return;

                const payload = response.data.data;
                setConfiguration(payload);
                form.setFieldsValue({
                    settings: payload.settings ?? {},
                    menus: payload.menus ?? [],
                });

                if (payload.settings_schema?.length > 0) {
                    setActiveTab((prev) => prev ?? payload.settings_schema[0].group);
                }
            } catch (error) {
                if (cancelled) return;
                message.error(error.response?.data?.message ?? tRef.current('themes.messages.settings_failed'));
                navigate('/admin/themes');
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        fetchConfiguration();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [alias]);

    async function handleSubmit(values) {
        setSubmitting(true);

        try {
            const payload = buildThemeSettingsPayload(values);
            const formData = new FormData();

            formData.append('_method', 'PUT');
            formData.append('settings', JSON.stringify(payload.settings));
            formData.append('menus', JSON.stringify(payload.menus));
            payload.uploads.forEach(({ token, file }) => {
                formData.append(`uploads[${token}]`, file);
            });

            const response = await api.post(`/themes/${alias}/settings`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            const nextConfiguration = response.data.data;
            setConfiguration(nextConfiguration);
            form.setFieldsValue({
                settings: nextConfiguration.settings ?? {},
                menus: nextConfiguration.menus ?? [],
            });
            message.success(t('themes.messages.updated'));
        } catch (error) {
            message.error(error.response?.data?.message ?? t('themes.messages.update_failed'));
        } finally {
            setSubmitting(false);
        }
    }

    // Build tab items from settings schema
    const tabItems = useMemo(() => {
        if (!configuration) return [];

        const schemaGroups = (configuration.settings_schema ?? []).map((group) => ({
            key: group.group,
            label: (
                <span className="theme-settings-tab__label">
                    <FontIcon name={getGroupIcon(group.group)} />
                    <span>{group.label}</span>
                    <Badge
                        count={group.fields?.length ?? 0}
                        size="small"
                        className="theme-settings-tab__badge"
                    />
                </span>
            ),
            children: (
                <div className="theme-settings-tab__content">
                    {group.description && (
                        <Alert
                            type="info"
                            showIcon
                            message={group.description}
                            className="theme-settings-tab__description"
                        />
                    )}

                    <Row gutter={[24, 24]}>
                        {(group.fields ?? []).map((field) => (
                            <Col
                                key={field.key}
                                xs={24}
                                lg={field.span ?? (['textarea', 'object', 'repeater'].includes(field.type) ? 24 : 12)}
                            >
                                <ThemeSettingField field={field} name={['settings', field.key]} />
                            </Col>
                        ))}
                    </Row>
                </div>
            ),
        }));

        // Add Menus as the last tab
        if ((configuration.menus ?? []).length > 0) {
            schemaGroups.push({
                key: '__menus__',
                label: (
                    <span className="theme-settings-tab__label">
                        <FontIcon name="menu" />
                        <span>{t('themes.editor.menu_section')}</span>
                        <Badge
                            count={configuration.menus.length}
                            size="small"
                            className="theme-settings-tab__badge"
                        />
                    </span>
                ),
                children: (
                    <div className="theme-settings-tab__content">
                        <Space direction="vertical" size={20} style={{ display: 'flex' }}>
                            {(configuration.menus ?? []).map((menu, index) => (
                                <ThemeMenuEditor
                                    key={menu.alias}
                                    menu={menu}
                                    menuIndex={index}
                                />
                            ))}
                        </Space>
                    </div>
                ),
            });
        }

        return schemaGroups;
    }, [configuration, t]);

    // Determine layout: if preview is open, editor takes xl=11, preview takes xl=13
    const editorSpan = showPreview ? { xs: 24, xl: 12 } : { xs: 24, xl: 18 };
    const sidebarSpan = showPreview ? { xs: 24, xl: 4 } : { xs: 24, xl: 6 };

    return (
        <div className="theme-settings-page">
            <PageHeader
                title={t('themes.settings_title', { name: configuration?.theme?.name ?? alias })}
                description={t('themes.settings_description')}
                extra={[
                    {
                        label: showPreview ? 'Ẩn preview' : 'Xem trước',
                        icon: <FontIcon name={showPreview ? 'preview_off' : 'preview'} />,
                        onClick: () => setShowPreview((v) => !v),
                        type: showPreview ? 'default' : 'primary',
                    },
                ]}
            />

            <Form form={form} layout="vertical" onFinish={handleSubmit}>
                <Row gutter={[24, 24]} align="start">
                    {/* ── Editor column ── */}
                    <Col {...editorSpan}>
                        {/* Theme info header */}
                        <Card className="theme-settings-hero" loading={loading} bordered={false}>
                            <div className="theme-settings-hero__inner">
                                <div className="theme-settings-hero__info">
                                    <Space size={8} align="center">
                                        <Tag color={configuration?.theme?.is_active ? 'green' : 'default'}>
                                            {configuration?.theme?.is_active ? t('themes.status.active') : t('themes.status.inactive')}
                                        </Tag>
                                        <Text type="secondary">v{configuration?.theme?.version ?? '1.0.0'}</Text>
                                    </Space>
                                    <Title level={3} style={{ margin: '4px 0' }}>
                                        {configuration?.theme?.name ?? alias}
                                    </Title>
                                    <Paragraph type="secondary" style={{ marginBottom: 0 }}>
                                        {configuration?.theme?.description ?? t('themes.settings_description')}
                                    </Paragraph>
                                </div>

                                <div className="theme-settings-hero__stats">
                                    <Tooltip title={t('themes.editor.stats.groups')}>
                                        <div className="theme-settings-hero__stat">
                                            <FontIcon name="category" />
                                            <strong>{stats.groups}</strong>
                                        </div>
                                    </Tooltip>
                                    <Tooltip title={t('themes.editor.stats.fields')}>
                                        <div className="theme-settings-hero__stat">
                                            <FontIcon name="tune" />
                                            <strong>{stats.fields}</strong>
                                        </div>
                                    </Tooltip>
                                    <Tooltip title={t('themes.editor.stats.menus')}>
                                        <div className="theme-settings-hero__stat">
                                            <FontIcon name="menu" />
                                            <strong>{stats.menus}</strong>
                                        </div>
                                    </Tooltip>
                                </div>
                            </div>
                        </Card>

                        {/* Tabbed settings */}
                        <Card className="theme-settings-tabs" loading={loading} bordered={false}>
                            <Tabs
                                activeKey={activeTab}
                                onChange={setActiveTab}
                                type="card"
                                size="middle"
                                items={tabItems}
                                tabPosition="left"
                                className="theme-settings-tabs__inner"
                            />
                        </Card>
                    </Col>

                    {/* ── Inline preview column (visible when toggled) ── */}
                    {showPreview && (
                        <Col xs={24} xl={showPreview ? 12 : 0}>
                            <div className="theme-preview-panel-wrapper">
                                <PreviewPanel
                                    alias={alias}
                                    form={form}
                                    onClose={() => setShowPreview(false)}
                                />
                            </div>
                        </Col>
                    )}

                    {/* ── Sidebar ── */}
                    <Col {...sidebarSpan}>
                        <div className="theme-settings-sidebar-wrapper">
                            <Card className="theme-settings-sidebar" loading={loading} bordered={false}>
                                <Space direction="vertical" size={14} style={{ display: 'flex' }}>
                                    <div>
                                        <Text strong>{t('themes.editor.guidelines_title')}</Text>
                                        <Divider style={{ margin: '10px 0 0' }} />
                                    </div>

                                    <div className="theme-settings-guidelines">
                                        <div className="theme-settings-guideline">
                                            <FontIcon name="cloud_upload" />
                                            <Text type="secondary">{t('themes.editor.guidelines_upload')}</Text>
                                        </div>
                                        <div className="theme-settings-guideline">
                                            <FontIcon name="photo_size_select_large" />
                                            <Text type="secondary">{t('themes.editor.guidelines_banner')}</Text>
                                        </div>
                                        <div className="theme-settings-guideline">
                                            <FontIcon name="navigation" />
                                            <Text type="secondary">{t('themes.editor.guidelines_navigation')}</Text>
                                        </div>
                                    </div>
                                </Space>
                            </Card>

                            <Card className="theme-settings-submit" bordered={false}>
                                <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                                    <Button
                                        type="primary"
                                        htmlType="submit"
                                        loading={submitting}
                                        icon={<FontIcon name="save" />}
                                        block
                                        size="large"
                                    >
                                        {t('themes.actions.save_settings')}
                                    </Button>
                                    <Button onClick={() => navigate('/admin/themes')} block>
                                        {t('common.cancel')}
                                    </Button>
                                </Space>
                            </Card>
                        </div>
                    </Col>
                </Row>
            </Form>
        </div>
    );
}

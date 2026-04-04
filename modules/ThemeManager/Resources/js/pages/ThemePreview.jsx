import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Select, Space, Tag, Tooltip, Typography, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

const { Text } = Typography;

const PREVIEW_TARGETS = [
    { label: 'Trang chủ', value: 'home' },
    { label: 'Sản phẩm', value: 'product' },
    { label: 'Danh mục', value: 'category' },
    { label: 'Blog', value: 'blog' },
];

// ─── Device mockup components ────────────────────────────────────────────────

function LaptopMockup({ src, loading }) {
    return (
        <div className="device-mockup device-mockup--laptop">
            <div className="device-mockup__screen-area">
                <div className="device-mockup__screen">
                    {loading ? (
                        <div className="device-mockup__loading">
                            <span className="device-mockup__spinner" />
                        </div>
                    ) : src ? (
                        <iframe
                            src={src}
                            title="Xem trước desktop"
                            className="device-mockup__iframe"
                            sandbox="allow-scripts allow-same-origin allow-forms"
                        />
                    ) : (
                        <div className="device-mockup__placeholder">
                            <FontIcon name="desktop_windows" />
                            <Text>Đang tải preview…</Text>
                        </div>
                    )}
                </div>
            </div>
            <div className="device-mockup__hinge" />
            <div className="device-mockup__base" />
        </div>
    );
}

function PhoneMockup({ src, loading }) {
    return (
        <div className="device-mockup device-mockup--phone">
            <div className="device-mockup__shell">
                <div className="device-mockup__notch" />
                <div className="device-mockup__screen">
                    {loading ? (
                        <div className="device-mockup__loading">
                            <span className="device-mockup__spinner" />
                        </div>
                    ) : src ? (
                        <iframe
                            src={src}
                            title="Xem trước điện thoại"
                            className="device-mockup__iframe"
                            sandbox="allow-scripts allow-same-origin allow-forms"
                        />
                    ) : (
                        <div className="device-mockup__placeholder">
                            <FontIcon name="smartphone" />
                            <Text>Đang tải…</Text>
                        </div>
                    )}
                </div>
                <div className="device-mockup__home-bar" />
            </div>
        </div>
    );
}

// ─── Main page ───────────────────────────────────────────────────────────────

export default function ThemePreview() {
    const navigate = useNavigate();
    const { alias } = useParams();
    const { t } = useLocale();

    const [themeInfo, setThemeInfo] = useState(null);
    const [previewUrl, setPreviewUrl] = useState(null);
    const [loadingSession, setLoadingSession] = useState(false);
    const [iframesReady, setIframesReady] = useState(false);
    const [previewTarget, setPreviewTarget] = useState('home');
    const debounceRef = useRef(null);

    // Load theme info (without needing settings — just name/status)
    useEffect(() => {
        let cancelled = false;

        async function loadThemeInfo() {
            try {
                const response = await api.get(`/themes/${alias}/settings`);
                if (!cancelled) {
                    setThemeInfo(response.data.data?.theme ?? null);
                }
            } catch (error) {
                if (!cancelled) {
                    message.error(error.response?.data?.message ?? t('themes.messages.settings_failed'));
                    navigate('/admin/themes');
                }
            }
        }

        loadThemeInfo();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [alias]);

    // Create a preview session (using persisted settings — no draft)
    const createPreviewSession = useCallback(async (target) => {
        setLoadingSession(true);
        setIframesReady(false);

        try {
            const response = await api.post(`/themes/${alias}/preview-session`, {
                preview_target: target,
            });
            setPreviewUrl(response.data.preview_url);

            // Give iframes a moment to start loading
            window.setTimeout(() => setIframesReady(true), 300);
        } catch {
            // Fallback: use the generic preview URL without draft session
            setPreviewUrl(`/preview-theme/${alias}?target=${target}`);
            setIframesReady(true);
        } finally {
            setLoadingSession(false);
        }
    }, [alias]);

    // Load initial preview session
    useEffect(() => {
        createPreviewSession(previewTarget);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleTargetChange = (target) => {
        setPreviewTarget(target);
        if (debounceRef.current) window.clearTimeout(debounceRef.current);
        debounceRef.current = window.setTimeout(() => createPreviewSession(target), 200);
    };

    const handleRefresh = () => createPreviewSession(previewTarget);

    return (
        <div className="theme-preview-page">

            {/* ── Top bar ── */}
            <div className="theme-preview-page__topbar">
                <div className="theme-preview-page__topbar-left">
                    <Tooltip title="Quay lại">
                        <Button
                            shape="circle"
                            icon={<FontIcon name="arrow_back" />}
                            onClick={() => navigate('/admin/themes')}
                            className="theme-preview-page__back-btn"
                        />
                    </Tooltip>
                    <div className="theme-preview-page__theme-info">
                        <span className="theme-preview-page__theme-name">
                            {themeInfo?.name ?? alias}
                        </span>
                        {themeInfo?.is_active && (
                            <Tag color="green" className="theme-preview-page__active-tag">
                                Đang hoạt động
                            </Tag>
                        )}
                        {themeInfo?.version && (
                            <Text type="secondary" className="theme-preview-page__version">
                                v{themeInfo.version}
                            </Text>
                        )}
                    </div>
                </div>

                <div className="theme-preview-page__topbar-center">
                    <Select
                        value={previewTarget}
                        onChange={handleTargetChange}
                        options={PREVIEW_TARGETS}
                        size="middle"
                        style={{ width: 180 }}
                        className="theme-preview-page__target-select"
                    />
                    <Tooltip title="Tải lại preview">
                        <Button
                            icon={<FontIcon name="refresh" />}
                            onClick={handleRefresh}
                            loading={loadingSession}
                            size="middle"
                        />
                    </Tooltip>
                </div>

                <div className="theme-preview-page__topbar-right">
                    <Space>
                        <Button
                            icon={<FontIcon name="configure" />}
                            onClick={() => navigate(`/admin/themes/${alias}/settings`)}
                        >
                            Cấu hình
                        </Button>
                        {!themeInfo?.is_active && (
                            <Button
                                type="primary"
                                icon={<FontIcon name="activate" />}
                                onClick={async () => {
                                    try {
                                        await api.put(`/themes/${alias}/activate`);
                                        message.success(t('themes.messages.activated'));
                                        navigate('/admin/themes');
                                    } catch (error) {
                                        message.error(error.response?.data?.message ?? t('themes.messages.activate_failed'));
                                    }
                                }}
                            >
                                {t('themes.actions.activate')}
                            </Button>
                        )}
                    </Space>
                </div>
            </div>

            {/* ── Preview stage ── */}
            <div className="theme-preview-page__stage">

                {/* Background blur blobs */}
                <div className="theme-preview-page__bg-blob theme-preview-page__bg-blob--1" />
                <div className="theme-preview-page__bg-blob theme-preview-page__bg-blob--2" />

                {/* Laptop mockup */}
                <div className="theme-preview-page__device-group">
                    <div className="theme-preview-page__device-label">
                        <FontIcon name="desktop_windows" />
                        <span>Desktop</span>
                    </div>
                    <LaptopMockup src={previewUrl} loading={loadingSession || !iframesReady} />
                </div>

                {/* Phone mockup */}
                <div className="theme-preview-page__device-group theme-preview-page__device-group--phone">
                    <div className="theme-preview-page__device-label">
                        <FontIcon name="smartphone" />
                        <span>Mobile</span>
                    </div>
                    <PhoneMockup src={previewUrl} loading={loadingSession || !iframesReady} />
                </div>
            </div>

            {/* ── Bottom legend ── */}
            <div className="theme-preview-page__legend">
                <Text type="secondary">
                    Xem trước sử dụng cấu hình đã lưu. Để thấy thay đổi ngay lập tức, hãy dùng preview tích hợp trong trang cấu hình.
                </Text>
            </div>
        </div>
    );
}

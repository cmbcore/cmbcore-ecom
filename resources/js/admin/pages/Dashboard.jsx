import React, { startTransition, useCallback, useEffect, useState } from 'react';
import { Alert, Button, Card, Col, Empty, Row, Skeleton, Space, Tag, Typography } from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import api from '../services/api';

const { Paragraph, Text, Title } = Typography;
const WIDGET_ZONE_ORDER = ['primary', 'secondary', 'extensions'];

function DashboardCard({ card, onOpen }) {
    return (
        <Card
            bordered={false}
            hoverable={Boolean(card.route)}
            className={`dashboard-metric dashboard-metric--${card.tone ?? 'default'}`}
            onClick={card.route ? () => onOpen(card.route) : undefined}
        >
            <div className="dashboard-metric__eyebrow">
                <span className="dashboard-metric__icon">
                    <FontIcon name={card.icon ?? 'dashboard'} />
                </span>
                <Text>{card.label}</Text>
            </div>
            <div className="dashboard-metric__value">{card.value ?? 0}</div>
            {card.meta ? <Text type="secondary">{card.meta}</Text> : null}
        </Card>
    );
}

function DashboardWidget({ widget }) {
    if (widget.type === 'highlights') {
        return (
            <Card bordered={false} className="dashboard-widget dashboard-widget--highlights">
                <div className="dashboard-widget__header">
                    <div>
                        <Title level={4}>{widget.title}</Title>
                        {widget.description ? <Paragraph>{widget.description}</Paragraph> : null}
                    </div>
                </div>
                <div className="dashboard-highlight-grid">
                    {(widget.items ?? []).map((item) => (
                        <div key={item.label} className={`dashboard-highlight dashboard-highlight--${item.tone ?? 'default'}`}>
                            <span className="dashboard-highlight__icon">
                                <FontIcon name={item.icon ?? 'dashboard'} />
                            </span>
                            <strong>{item.value ?? 0}</strong>
                            <span>{item.label}</span>
                        </div>
                    ))}
                </div>
            </Card>
        );
    }

    if (widget.type === 'list') {
        return (
            <Card bordered={false} className="dashboard-widget">
                <div className="dashboard-widget__header">
                    <div>
                        <Title level={4}>{widget.title}</Title>
                        {widget.description ? <Paragraph>{widget.description}</Paragraph> : null}
                    </div>
                </div>
                <div className="dashboard-list">
                    {(widget.items ?? []).map((item) => (
                        <div key={item.label} className="dashboard-list__item">
                            <div>
                                <Text strong>{item.label}</Text>
                                {item.meta ? <div><Text type="secondary">{item.meta}</Text></div> : null}
                            </div>
                            <Text className="dashboard-list__value">{item.value}</Text>
                        </div>
                    ))}
                </div>
            </Card>
        );
    }

    if (widget.type === 'placeholder') {
        return (
            <Card bordered={false} className="dashboard-widget dashboard-widget--placeholder">
                <div className="dashboard-widget__header">
                    <div>
                        <Title level={4}>{widget.title}</Title>
                        {widget.description ? <Paragraph>{widget.description}</Paragraph> : null}
                    </div>
                </div>
                <div className="dashboard-placeholder">
                    <div className="dashboard-placeholder__grid" />
                    <div className="dashboard-placeholder__content">
                        <Text>{widget.message}</Text>
                        <Space wrap size={[8, 8]}>
                            {(widget.badges ?? []).map((badge) => (
                                <Tag key={badge} bordered={false} color="cyan">
                                    {badge}
                                </Tag>
                            ))}
                        </Space>
                    </div>
                </div>
            </Card>
        );
    }

    return null;
}

export default function Dashboard() {
    const { t } = useLocale();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [payload, setPayload] = useState({ overview: {}, cards: [], widgets: [] });
    const [error, setError] = useState(false);

    const fetchDashboard = useCallback(async () => {
        setLoading(true);
        setError(false);

        try {
            const response = await api.get('/dashboard');
            startTransition(() => {
                setPayload(response.data.data ?? { overview: {}, cards: [], widgets: [] });
            });
        } catch {
            setError(true);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchDashboard();
    }, [fetchDashboard]);

    const widgetsByZone = Object.fromEntries(
        WIDGET_ZONE_ORDER.map((zone) => [
            zone,
            (payload.widgets ?? []).filter((widget) => (widget.zone ?? 'primary') === zone),
        ]),
    );

    if (loading) {
        return <Skeleton active paragraph={{ rows: 10 }} />;
    }

    return (
        <div className="dashboard-screen">
            <section className="dashboard-hero">
                <div>
                    <Text className="dashboard-hero__eyebrow">{t('dashboard.eyebrow')}</Text>
                    <Title level={2}>
                        <FontIcon name="dashboard" className="page-title__icon" />
                        {payload.overview?.title ?? t('dashboard.title')}
                    </Title>
                    <Paragraph>{payload.overview?.description ?? t('dashboard.description')}</Paragraph>
                </div>
                <div className="dashboard-hero__actions">
                    <Button icon={<FontIcon name="refresh" />} onClick={fetchDashboard}>
                        {t('common.refresh')}
                    </Button>
                </div>
            </section>

            {error ? (
                <Alert
                    className="dashboard-alert"
                    type="warning"
                    showIcon
                    message={t('dashboard.messages.load_failed')}
                />
            ) : null}

            <Row gutter={[16, 16]} className="dashboard-grid">
                {(payload.cards ?? []).map((card) => (
                    <Col key={card.key} xs={24} sm={12} xl={6}>
                        <DashboardCard card={card} onOpen={navigate} />
                    </Col>
                ))}
            </Row>

            <div className="dashboard-zone-grid">
                <div className="dashboard-zone dashboard-zone--primary">
                    {widgetsByZone.primary?.length
                        ? widgetsByZone.primary.map((widget) => <DashboardWidget key={widget.key} widget={widget} />)
                        : <Empty />}
                </div>

                <div className="dashboard-zone dashboard-zone--secondary">
                    {widgetsByZone.secondary?.map((widget) => <DashboardWidget key={widget.key} widget={widget} />)}
                </div>
            </div>

            {widgetsByZone.extensions?.length ? (
                <div className="dashboard-zone dashboard-zone--extensions">
                    {widgetsByZone.extensions.map((widget) => <DashboardWidget key={widget.key} widget={widget} />)}
                </div>
            ) : null}
        </div>
    );
}

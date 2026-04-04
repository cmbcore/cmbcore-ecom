import React, { useCallback, useEffect, useState } from 'react';
import { Card, Col, Row, Table, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

export default function ReportsDashboard() {
    const [loading, setLoading] = useState(true);
    const [payload, setPayload] = useState({
        revenue_by_day: [],
        orders_by_status: [],
        top_products: [],
        top_customers: [],
        conversion: { views_to_orders: 0 },
    });

    const fetchReports = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/reports');
            setPayload(response.data.data ?? payload);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được báo cáo.');
        } finally {
            setLoading(false);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        fetchReports();
    }, [fetchReports]);

    return (
        <div>
            <PageHeader
                title="Báo cáo"
                description="Tổng hợp doanh thu, top sản phẩm, top khách hàng và tỷ lệ chuyển đổi."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: fetchReports },
                ]}
            />

            <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
                <Col xs={24} md={8}>
                    <Card loading={loading} title="Tỷ lệ chuyển đổi">
                        <strong>{payload.conversion.views_to_orders}%</strong>
                        <p>Tỉ lệ từ lượt xem sang đơn hàng.</p>
                    </Card>
                </Col>
                <Col xs={24} md={8}>
                    <Card loading={loading} title="Số ngày có doanh thu">
                        <strong>{payload.revenue_by_day.length}</strong>
                    </Card>
                </Col>
                <Col xs={24} md={8}>
                    <Card loading={loading} title="Trạng thái đơn hàng">
                        <strong>{payload.orders_by_status.length}</strong>
                    </Card>
                </Col>
            </Row>

            <Card loading={loading} title="Doanh thu theo ngày" style={{ marginBottom: 24 }}>
                <Table
                    rowKey="date"
                    dataSource={payload.revenue_by_day}
                    pagination={false}
                    columns={[
                        { title: 'Ngày', dataIndex: 'date' },
                        { title: 'Doanh thu', dataIndex: 'revenue', render: (v) => v ? Number(v).toLocaleString('vi-VN') + '₫' : '0₫' },
                        { title: 'Số đơn', dataIndex: 'orders_count' },
                    ]}
                />
            </Card>

            <Row gutter={[16, 16]}>
                <Col xs={24} lg={12}>
                    <Card loading={loading} title="Top sản phẩm bán chạy">
                        <Table
                            rowKey={(row) => `${row.product_id}-${row.product_name}`}
                            dataSource={payload.top_products}
                            pagination={false}
                            columns={[
                                { title: 'Sản phẩm', dataIndex: 'product_name' },
                                { title: 'Số lượng bán', dataIndex: 'sold_quantity' },
                                { title: 'Doanh thu', dataIndex: 'revenue', render: (v) => v ? Number(v).toLocaleString('vi-VN') + '₫' : '—' },
                            ]}
                        />
                    </Card>
                </Col>
                <Col xs={24} lg={12}>
                    <Card loading={loading} title="Top khách hàng">
                        <Table
                            rowKey={(row) => `${row.customer_email}-${row.customer_name}`}
                            dataSource={payload.top_customers}
                            pagination={false}
                            columns={[
                                { title: 'Khách hàng', dataIndex: 'customer_name' },
                                { title: 'Email', dataIndex: 'customer_email' },
                                { title: 'Số đơn', dataIndex: 'orders_count' },
                                { title: 'Chi tiêu', dataIndex: 'total_spent', render: (v) => v ? Number(v).toLocaleString('vi-VN') + '₫' : '—' },
                            ]}
                        />
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

import React, { useCallback, useEffect, useState } from 'react';
import { Button, Form, Input, InputNumber, Modal, Select, Space, Switch, Table, Tag, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

const STATUS_OPTIONS = [
    { label: 'Chờ duyệt', value: 'pending' },
    { label: 'Đã duyệt', value: 'approved' },
    { label: 'Từ chối', value: 'rejected' },
];

const STATUS_COLORS = { pending: 'orange', approved: 'green', rejected: 'red' };

function ReviewCreateModal({ open, onCancel, onSubmit, productOptions, onSearchProduct, submitting }) {
    const [form] = Form.useForm();

    useEffect(() => {
        if (!open) {
            form.resetFields();
            return;
        }

        form.setFieldsValue({
            rating: 5,
            status: 'approved',
            is_verified_purchase: false,
        });
    }, [form, open]);

    return (
        <Modal
            open={open}
            title="Thêm đánh giá thủ công"
            onCancel={onCancel}
            onOk={() => form.submit()}
            okText="Tạo đánh giá"
            cancelText="Hủy"
            confirmLoading={submitting}
            destroyOnHidden
            width={720}
        >
            <Form form={form} layout="vertical" onFinish={onSubmit}>
                <Form.Item
                    name="product_id"
                    label="Sản phẩm"
                    rules={[{ required: true, message: 'Vui lòng chọn sản phẩm.' }]}
                >
                    <Select
                        showSearch
                        filterOption={false}
                        placeholder="Tìm kiếm sản phẩm..."
                        options={productOptions}
                        onSearch={onSearchProduct}
                    />
                </Form.Item>

                <Space style={{ display: 'flex' }} align="start">
                    <Form.Item
                        name="reviewer_name"
                        label="Tên người đánh giá"
                        rules={[{ required: true, message: 'Vui lòng nhập tên.' }]}
                        style={{ flex: 1 }}
                    >
                        <Input />
                    </Form.Item>

                    <Form.Item
                        name="reviewer_email"
                        label="Email (tùy chọn)"
                        style={{ flex: 1 }}
                    >
                        <Input placeholder="Để trống để tạo email tự động" />
                    </Form.Item>
                </Space>

                <Space style={{ display: 'flex' }} align="start">
                    <Form.Item
                        name="rating"
                        label="Điểm đánh giá (1–5)"
                        rules={[{ required: true, message: 'Vui lòng nhập điểm.' }]}
                    >
                        <InputNumber min={1} max={5} style={{ width: 160 }} />
                    </Form.Item>

                    <Form.Item name="status" label="Trạng thái">
                        <Select style={{ width: 180 }} options={STATUS_OPTIONS} />
                    </Form.Item>

                    <Form.Item name="is_verified_purchase" label="Đã mua hàng" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                </Space>

                <Form.Item
                    name="title"
                    label="Tiêu đề"
                    rules={[{ required: true, message: 'Vui lòng nhập tiêu đề.' }]}
                >
                    <Input />
                </Form.Item>

                <Form.Item
                    name="content"
                    label="Nội dung đánh giá"
                    rules={[{ required: true, message: 'Vui lòng nhập nội dung.' }]}
                >
                    <Input.TextArea rows={5} />
                </Form.Item>
            </Form>
        </Modal>
    );
}

export default function ReviewModeration() {
    const [loading, setLoading] = useState(true);
    const [reviews, setReviews] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, per_page: 20, total: 0 });
    const [filters, setFilters] = useState({ search: '', status: undefined });
    const [createOpen, setCreateOpen] = useState(false);
    const [creating, setCreating] = useState(false);
    const [productOptions, setProductOptions] = useState([]);

    const fetchReviews = useCallback(async (page = 1) => {
        setLoading(true);

        try {
            const response = await api.get('/reviews', { params: { page, ...filters } });
            setReviews(response.data.data ?? []);
            setMeta(response.data.meta ?? { current_page: 1, per_page: 20, total: 0 });
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được danh sách đánh giá.');
        } finally {
            setLoading(false);
        }
    }, [filters]);

    const fetchProductOptions = useCallback(async (search = '') => {
        try {
            const response = await api.get('/products', { params: { search, per_page: 20 } });

            setProductOptions((response.data.data ?? []).map((product) => ({
                label: product.name,
                value: product.id,
            })));
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tìm được sản phẩm.');
        }
    }, []);

    useEffect(() => {
        fetchReviews();
    }, [fetchReviews]);

    useEffect(() => {
        if (createOpen) {
            fetchProductOptions();
        }
    }, [createOpen, fetchProductOptions]);

    async function updateReview(review, status) {
        try {
            await api.put(`/reviews/${review.id}`, { status, admin_reply: review.admin_reply ?? '' });
            message.success('Đã cập nhật trạng thái đánh giá.');
            fetchReviews(meta.current_page);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không cập nhật được đánh giá.');
        }
    }

    async function handleCreateReview(values) {
        setCreating(true);

        try {
            await api.post('/reviews', values);
            message.success('Đã tạo đánh giá.');
            setCreateOpen(false);
            fetchReviews(1);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tạo được đánh giá.');
        } finally {
            setCreating(false);
        }
    }

    return (
        <div>
            <PageHeader
                title="Đánh giá sản phẩm"
                description="Duyệt đánh giá từ khách mua hàng và thêm đánh giá thủ công."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: () => fetchReviews(meta.current_page) },
                    { label: 'Thêm đánh giá', icon: <FontIcon name="create" />, type: 'primary', onClick: () => setCreateOpen(true) },
                ]}
            />

            <Space style={{ marginBottom: 16 }}>
                <Input
                    allowClear
                    placeholder="Tìm theo nội dung, người dùng, sản phẩm..."
                    value={filters.search}
                    onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))}
                    style={{ width: 300 }}
                />
                <Select
                    allowClear
                    placeholder="Lọc trạng thái"
                    value={filters.status}
                    onChange={(value) => setFilters((current) => ({ ...current, status: value }))}
                    options={STATUS_OPTIONS}
                    style={{ width: 180 }}
                />
                <Button onClick={() => fetchReviews(1)}>Lọc</Button>
            </Space>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={reviews}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => fetchReviews(page),
                }}
                columns={[
                    { title: 'Sản phẩm', render: (_, review) => review.product?.name ?? '—' },
                    { title: 'Khách hàng', render: (_, review) => review.user?.name ?? review.reviewer_name ?? '—' },
                    { title: 'Điểm', dataIndex: 'rating' },
                    { title: 'Tiêu đề', dataIndex: 'title', ellipsis: true },
                    {
                        title: 'Trạng thái',
                        dataIndex: 'status',
                        render: (value) => <Tag color={STATUS_COLORS[value] ?? 'default'}>{STATUS_OPTIONS.find((o) => o.value === value)?.label ?? value}</Tag>,
                    },
                    {
                        title: 'Thao tác',
                        render: (_, review) => (
                            <Space>
                                <Button size="small" onClick={() => updateReview(review, 'approved')}>Duyệt</Button>
                                <Button size="small" danger onClick={() => updateReview(review, 'rejected')}>Từ chối</Button>
                            </Space>
                        ),
                    },
                ]}
            />

            <ReviewCreateModal
                open={createOpen}
                onCancel={() => setCreateOpen(false)}
                onSubmit={handleCreateReview}
                productOptions={productOptions}
                onSearchProduct={fetchProductOptions}
                submitting={creating}
            />
        </div>
    );
}

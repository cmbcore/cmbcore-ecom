import React from 'react';
import { Button, Card, Form, Input, InputNumber, Select, Space } from 'antd';
import CategoryTreeSelect from '@admin/components/CategoryTreeSelect';
import RichTextEditor from '@admin/components/RichTextEditor';
import SingleImageUploader from '@admin/components/SingleImageUploader';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';

export function normalizeCategoryFormValues(category) {
    if (!category) {
        return {
            parent_id: undefined,
            position: 0,
            status: 'active',
        };
    }

    return {
        ...category,
        parent_id: category.parent_id ?? undefined,
        position: category.position ?? 0,
        status: category.status ?? 'active',
    };
}

export default function CategoryForm({
    form,
    initialValues,
    parentOptions = [],
    loading = false,
    onFinish,
    onCancel,
    submitLabel,
}) {
    const { t } = useLocale();

    return (
        <Card className="category-form-card" bordered={false}>
            <Form
                form={form}
                layout="vertical"
                initialValues={initialValues}
                onFinish={onFinish}
            >
                <Form.Item label={t('categories.fields.name')} name="name" rules={[{ required: true, message: t('categories.validation.name_prompt') }]}>
                    <Input placeholder={t('categories.placeholders.name')} />
                </Form.Item>

                <Form.Item label={t('categories.fields.slug')} name="slug" extra={t('categories.help.slug')}>
                    <Input placeholder={t('categories.placeholders.slug')} />
                </Form.Item>

                <Form.Item label={t('categories.fields.parent_id')} name="parent_id">
                    <CategoryTreeSelect
                        categories={parentOptions}
                        placeholder={t('categories.placeholders.parent_id')}
                    />
                </Form.Item>

                <Form.Item label={t('categories.fields.description')} name="description">
                    <RichTextEditor minHeight={240} placeholder={t('categories.placeholders.description')} />
                </Form.Item>

                <Space size={16} style={{ display: 'flex' }} align="start">
                    <Form.Item label={t('categories.fields.position')} name="position" style={{ width: 160 }}>
                        <InputNumber min={0} style={{ width: '100%' }} />
                    </Form.Item>

                    <Form.Item label={t('categories.fields.status')} name="status" style={{ minWidth: 180 }}>
                        <Select
                            options={[
                                { label: t('categories.status_options.active'), value: 'active' },
                                { label: t('categories.status_options.inactive'), value: 'inactive' },
                            ]}
                        />
                    </Form.Item>
                </Space>

                <Form.Item label={t('categories.fields.icon')} name="icon">
                    <Input placeholder={t('categories.placeholders.icon')} />
                </Form.Item>

                <Form.Item label={t('categories.fields.image')} name="image_file" extra={t('categories.help.image')}>
                    <SingleImageUploader existingUrl={initialValues?.image} size={800} />
                </Form.Item>

                <Form.Item label={t('categories.fields.meta_title')} name="meta_title">
                    <Input placeholder={t('categories.placeholders.meta_title')} />
                </Form.Item>

                <Form.Item label={t('categories.fields.meta_description')} name="meta_description">
                    <Input.TextArea rows={3} placeholder={t('categories.placeholders.meta_description')} />
                </Form.Item>

                <div className="category-form-card__actions">
                    <Button onClick={onCancel}>{t('common.cancel')}</Button>
                    <Button type="primary" htmlType="submit" loading={loading} icon={<FontIcon name="save" />}>
                        {submitLabel}
                    </Button>
                </div>
            </Form>
        </Card>
    );
}

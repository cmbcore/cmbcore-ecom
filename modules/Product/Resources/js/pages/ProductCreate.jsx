import React, { useCallback, useEffect, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import ProductForm from '../components/ProductForm';
import {
    applyProductFormErrors,
    normalizeProductFormValues,
    toProductFormData,
} from '../components/productFormUtils';

export default function ProductCreate() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [categories, setCategories] = useState([]);

    const fetchCategories = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/categories/tree');
            setCategories(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? t('products.messages.load_categories_failed'));
        } finally {
            setLoading(false);
        }
    }, [t]);

    async function handleSubmit(values) {
        setSubmitting(true);

        try {
            await api.post('/products', toProductFormData(values), {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            message.success(t('products.messages.created'));
            navigate('/admin/products');
        } catch (error) {
            applyProductFormErrors(form, error);
            message.error(error.response?.data?.message ?? t('products.messages.create_failed'));
        } finally {
            setSubmitting(false);
        }
    }

    useEffect(() => {
        fetchCategories();
    }, [fetchCategories]);

    return (
        <div>
            <PageHeader
                title={t('products.create_title')}
                description={t('products.create_description')}
            />
            <ProductForm
                form={form}
                initialValues={normalizeProductFormValues(null)}
                categories={categories}
                loading={loading || submitting}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/products')}
                submitLabel={t('products.actions.save')}
            />
        </div>
    );
}

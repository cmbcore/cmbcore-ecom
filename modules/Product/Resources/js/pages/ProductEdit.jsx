import React, { useEffect, useRef, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import ProductForm from '../components/ProductForm';
import {
    applyProductFormErrors,
    normalizeProductFormValues,
    toProductFormData,
} from '../components/productFormUtils';

export default function ProductEdit() {
    const navigate = useNavigate();
    const { t } = useLocale();
    const { id } = useParams();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [product, setProduct] = useState(null);
    const [categories, setCategories] = useState([]);

    // Use a ref for `t` so changing locale won't re-trigger the effect
    const tRef = useRef(t);
    tRef.current = t;

    useEffect(() => {
        // `id` is the only real trigger - locale/t changes must NOT cause a re-fetch
        let cancelled = false;
        setLoading(true);

        async function bootstrap() {
            try {
                const [productResponse, categoryResponse] = await Promise.all([
                    api.get(`/products/${id}`),
                    api.get('/categories/tree'),
                ]);

                if (cancelled) return;

                const nextProduct = productResponse.data.data;
                setProduct(nextProduct);
                setCategories(categoryResponse.data.data ?? []);
                form.setFieldsValue(normalizeProductFormValues(nextProduct));
            } catch (error) {
                if (cancelled) return;

                const errMsg = error.response?.data?.message ?? tRef.current('products.messages.load_failed');
                message.error(errMsg);

                // Only redirect on 404 (product not found) - for auth errors let RouteGate handle it
                if (error.response?.status === 404) {
                    navigate('/admin/products');
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        }

        bootstrap();

        return () => {
            cancelled = true;
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]); // Only re-fetch when product ID changes, NOT when t/navigate change

    async function handleSubmit(values) {
        setSubmitting(true);

        try {
            await api.post(`/products/${id}`, toProductFormData(values, 'PUT'), {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            message.success(t('products.messages.updated'));
            navigate('/admin/products');
        } catch (error) {
            applyProductFormErrors(form, error);
            message.error(error.response?.data?.message ?? t('products.messages.update_failed'));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div>
            <PageHeader
                title={product ? t('products.edit_title', { name: product.name }) : t('products.edit_fallback')}
                description={t('products.edit_description')}
            />
            <ProductForm
                form={form}
                initialValues={normalizeProductFormValues(product)}
                categories={categories}
                loading={loading || submitting}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/products')}
                submitLabel={t('products.actions.update')}
            />
        </div>
    );
}

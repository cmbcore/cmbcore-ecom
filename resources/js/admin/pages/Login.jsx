import React from 'react';
import { Alert, Button, Checkbox, Form, Input } from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { useAuth } from '../hooks/useAuth';

export default function Login() {
    const { login, loginError, loginLoading } = useAuth();
    const { t } = useLocale();
    const [form] = Form.useForm();

    const handleSubmit = async (values) => {
        try {
            await login(values);
        } catch {
            // handled by auth context
        }
    };

    return (
        <div className="admin-login-container">
            {/* Brand mark */}
            <div className="admin-login-brand">
                <span className="admin-login-brand__dot" />
                <span className="admin-login-brand__name">CMBCORE Admin</span>
            </div>

            <div className="admin-login-header">
                <h2 className="admin-login-title">{t('auth.heading')}</h2>
                <p className="admin-login-subtitle">
                    {t('auth.seed_credentials', { email: 'admin@cmbcore.test', password: 'password' })}
                </p>
            </div>

            {loginError ? (
                <Alert
                    type="error"
                    message={loginError}
                    showIcon
                    className="admin-login-error"
                />
            ) : null}

            <Form
                form={form}
                layout="vertical"
                onFinish={handleSubmit}
                initialValues={{ remember: true }}
                size="large"
                className="admin-login-form"
            >
                <Form.Item
                    label={t('auth.email')}
                    name="email"
                    rules={[{ required: true, type: 'email' }]}
                >
                    <Input
                        autoComplete="email"
                        placeholder="admin@cmbcore.test"
                        prefix={<FontIcon name="mail" className="login-input-icon" />}
                    />
                </Form.Item>

                <Form.Item
                    label={t('auth.password')}
                    name="password"
                    rules={[{ required: true }]}
                >
                    <Input.Password
                        autoComplete="current-password"
                        placeholder="••••••••"
                        prefix={<FontIcon name="lock" className="login-input-icon" />}
                    />
                </Form.Item>

                <div className="admin-login-row">
                    <Form.Item name="remember" valuePropName="checked" noStyle>
                        <Checkbox>{t('auth.remember')}</Checkbox>
                    </Form.Item>
                </div>

                <Button
                    type="primary"
                    htmlType="submit"
                    loading={loginLoading}
                    block
                    size="large"
                    className="admin-login-button"
                    icon={!loginLoading ? <FontIcon name="login" /> : null}
                >
                    {t('auth.login_button')}
                </Button>
            </Form>

            <div className="admin-login-footer">
                <span>© 2026 CMBCORE Commerce</span>
            </div>
        </div>
    );
}

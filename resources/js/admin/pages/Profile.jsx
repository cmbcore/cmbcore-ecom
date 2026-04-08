import React, { useEffect, useRef, useState } from 'react';
import {
    Alert,
    Avatar,
    Button,
    Card,
    Col,
    Divider,
    Form,
    Input,
    Row,
    Tag,
    Typography,
    Upload,
    message,
} from 'antd';
import {
    CameraOutlined,
    KeyOutlined,
    LockOutlined,
    MailOutlined,
    PhoneOutlined,
    SaveOutlined,
    UserOutlined,
} from '@ant-design/icons';
import PageHeader from '@admin/components/ui/PageHeader';
import { useAuth } from '@admin/hooks/useAuth';
import api from '@admin/services/api';

const { Text, Title } = Typography;

const ROLE_COLOR = {
    admin: 'purple',
    editor: 'blue',
    viewer: 'cyan',
};

const ROLE_LABEL = {
    admin: 'Quản trị viên',
    editor: 'Biên tập viên',
    viewer: 'Người xem',
};

/* ── Avatar Upload ─────────────────────────────────────────── */
function AvatarSection({ user, onAvatarChanged }) {
    const [loading, setLoading] = useState(false);
    const inputRef = useRef(null);

    const handleFileChange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            message.error('Chỉ chấp nhận file ảnh.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            message.error('Ảnh không được vượt quá 2MB.');
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);

        setLoading(true);
        try {
            const res = await api.post('/profile/avatar', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            onAvatarChanged(res.data.data?.avatar_url ?? null);
            message.success('Đã cập nhật ảnh đại diện!');
        } catch (err) {
            message.error(err.response?.data?.message ?? 'Upload thất bại.');
        } finally {
            setLoading(false);
        }
    };

    const avatarSrc = user?.avatar
        ? (user.avatar.startsWith('http') ? user.avatar : `/storage/${user.avatar}`)
        : null;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 16 }}>
            <div style={{ position: 'relative', display: 'inline-block' }}>
                <Avatar
                    size={110}
                    src={avatarSrc}
                    icon={<UserOutlined />}
                    style={{
                        border: '3px solid #e5e7eb',
                        boxShadow: '0 4px 20px rgba(0,0,0,0.1)',
                        background: '#f3f4f6',
                        color: '#9ca3af',
                        fontSize: 40,
                    }}
                />
                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    disabled={loading}
                    style={{
                        position: 'absolute',
                        bottom: 2,
                        right: 2,
                        width: 30,
                        height: 30,
                        borderRadius: '50%',
                        border: '2px solid #fff',
                        background: '#4f46e5',
                        color: '#fff',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        cursor: 'pointer',
                        fontSize: 13,
                        boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                        transition: 'background 0.2s',
                    }}
                    title="Đổi ảnh đại diện"
                >
                    <CameraOutlined />
                </button>
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/*"
                    style={{ display: 'none' }}
                    onChange={handleFileChange}
                />
            </div>

            <div style={{ textAlign: 'center' }}>
                <Title level={4} style={{ margin: 0 }}>{user?.name}</Title>
                <Text type="secondary" style={{ fontSize: 13 }}>{user?.email}</Text>
                <div style={{ marginTop: 8 }}>
                    <Tag color={ROLE_COLOR[user?.role] ?? 'default'}>
                        {ROLE_LABEL[user?.role] ?? user?.role}
                    </Tag>
                </div>
            </div>
        </div>
    );
}

/* ── Main Page ──────────────────────────────────────────────── */
export default function ProfilePage() {
    const { user, login } = useAuth();
    const [infoForm] = Form.useForm();
    const [passForm] = Form.useForm();
    const [infoLoading, setInfoLoading] = useState(false);
    const [passLoading, setPassLoading] = useState(false);
    const [infoSuccess, setInfoSuccess] = useState('');
    const [passSuccess, setPassSuccess] = useState('');
    const [avatarUrl, setAvatarUrl] = useState(user?.avatar ?? null);

    // Đọc profile mới nhất từ /auth/me
    const [profile, setProfile] = useState(user ?? null);

    useEffect(() => {
        api.get('/auth/me')
            .then((res) => {
                const u = res.data.data;
                setProfile(u);
                infoForm.setFieldsValue({
                    name: u.name,
                    email: u.email,
                    phone: u.phone ?? '',
                });
            })
            .catch(() => {});
    }, []);

    const handleSaveInfo = async (values) => {
        setInfoLoading(true);
        setInfoSuccess('');
        try {
            const res = await api.put('/profile', values);
            setProfile((prev) => ({ ...prev, ...res.data.data }));
            setInfoSuccess('Đã cập nhật thông tin thành công!');
            message.success('Lưu thành công!');
        } catch (err) {
            const msg = err.response?.data?.message ?? 'Cập nhật thất bại.';
            message.error(msg);
        } finally {
            setInfoLoading(false);
        }
    };

    const handleChangePassword = async (values) => {
        setPassLoading(true);
        setPassSuccess('');
        try {
            await api.put('/profile/password', {
                current_password: values.current_password,
                password: values.password,
                password_confirmation: values.password_confirmation,
            });
            passForm.resetFields();
            setPassSuccess('Đã đổi mật khẩu thành công!');
            message.success('Đổi mật khẩu thành công!');
        } catch (err) {
            const msg = err.response?.data?.message
                ?? err.response?.data?.errors?.current_password?.[0]
                ?? 'Đổi mật khẩu thất bại.';
            message.error(msg);
        } finally {
            setPassLoading(false);
        }
    };

    return (
        <div>
            <PageHeader
                title="Hồ sơ cá nhân"
                description="Cập nhật thông tin tài khoản và bảo mật của bạn."
            />

            <Row gutter={[24, 24]}>
                {/* ── Left: Avatar + info ── */}
                <Col xs={24} lg={8}>
                    <Card bordered={false} style={{ textAlign: 'center', padding: '8px 0' }}>
                        <AvatarSection
                            user={{ ...profile, avatar: avatarUrl }}
                            onAvatarChanged={(url) => setAvatarUrl(url)}
                        />

                        <Divider style={{ margin: '24px 0 16px' }} />

                        <div style={{ textAlign: 'left', display: 'inline-block', minWidth: 200 }}>
                            {[
                                { icon: <UserOutlined />, label: 'ID', value: `#${profile?.id}` },
                                { icon: <MailOutlined />, label: 'Email', value: profile?.email },
                                { icon: <PhoneOutlined />, label: 'Điện thoại', value: profile?.phone || '—' },
                            ].map(({ icon, label, value }) => (
                                <div key={label} style={{ display: 'flex', gap: 10, marginBottom: 10, alignItems: 'center' }}>
                                    <span style={{ color: '#9ca3af', width: 16, textAlign: 'center' }}>{icon}</span>
                                    <Text type="secondary" style={{ fontSize: 12, minWidth: 60 }}>{label}:</Text>
                                    <Text style={{ fontSize: 13 }}>{value}</Text>
                                </div>
                            ))}
                        </div>
                    </Card>
                </Col>

                {/* ── Right: forms ── */}
                <Col xs={24} lg={16}>
                    {/* Info form */}
                    <Card
                        bordered={false}
                        style={{ marginBottom: 20 }}
                        title={
                            <span>
                                <UserOutlined style={{ marginRight: 8, color: '#4f46e5' }} />
                                Thông tin cơ bản
                            </span>
                        }
                    >
                        {infoSuccess && (
                            <Alert
                                type="success"
                                message={infoSuccess}
                                showIcon
                                closable
                                style={{ marginBottom: 20 }}
                                onClose={() => setInfoSuccess('')}
                            />
                        )}

                        <Form
                            form={infoForm}
                            layout="vertical"
                            onFinish={handleSaveInfo}
                            size="large"
                        >
                            <Row gutter={16}>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Họ và tên"
                                        name="name"
                                        rules={[{ required: true, message: 'Vui lòng nhập họ tên.' }]}
                                    >
                                        <Input
                                            prefix={<UserOutlined style={{ color: '#d1d5db' }} />}
                                            placeholder="Nguyễn Văn A"
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Số điện thoại"
                                        name="phone"
                                    >
                                        <Input
                                            prefix={<PhoneOutlined style={{ color: '#d1d5db' }} />}
                                            placeholder="0912345678"
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xs={24}>
                                    <Form.Item
                                        label="Email"
                                        name="email"
                                        rules={[
                                            { required: true, message: 'Vui lòng nhập email.' },
                                            { type: 'email', message: 'Email không hợp lệ.' },
                                        ]}
                                    >
                                        <Input
                                            prefix={<MailOutlined style={{ color: '#d1d5db' }} />}
                                            placeholder="admin@cmbcore.test"
                                        />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                <Button
                                    type="primary"
                                    htmlType="submit"
                                    icon={<SaveOutlined />}
                                    loading={infoLoading}
                                >
                                    Lưu thông tin
                                </Button>
                            </div>
                        </Form>
                    </Card>

                    {/* Password form */}
                    <Card
                        bordered={false}
                        title={
                            <span>
                                <LockOutlined style={{ marginRight: 8, color: '#dc2626' }} />
                                Đổi mật khẩu
                            </span>
                        }
                    >
                        {passSuccess && (
                            <Alert
                                type="success"
                                message={passSuccess}
                                showIcon
                                closable
                                style={{ marginBottom: 20 }}
                                onClose={() => setPassSuccess('')}
                            />
                        )}

                        <Form
                            form={passForm}
                            layout="vertical"
                            onFinish={handleChangePassword}
                            size="large"
                        >
                            <Form.Item
                                label="Mật khẩu hiện tại"
                                name="current_password"
                                rules={[{ required: true, message: 'Vui lòng nhập mật khẩu hiện tại.' }]}
                            >
                                <Input.Password
                                    prefix={<KeyOutlined style={{ color: '#d1d5db' }} />}
                                    placeholder="••••••••"
                                />
                            </Form.Item>

                            <Row gutter={16}>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Mật khẩu mới"
                                        name="password"
                                        rules={[
                                            { required: true, message: 'Vui lòng nhập mật khẩu mới.' },
                                            { min: 8, message: 'Mật khẩu tối thiểu 8 ký tự.' },
                                        ]}
                                    >
                                        <Input.Password
                                            prefix={<LockOutlined style={{ color: '#d1d5db' }} />}
                                            placeholder="••••••••"
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Xác nhận mật khẩu"
                                        name="password_confirmation"
                                        dependencies={['password']}
                                        rules={[
                                            { required: true, message: 'Vui lòng xác nhận mật khẩu.' },
                                            ({ getFieldValue }) => ({
                                                validator(_, value) {
                                                    if (!value || getFieldValue('password') === value) {
                                                        return Promise.resolve();
                                                    }
                                                    return Promise.reject(new Error('Mật khẩu xác nhận không khớp.'));
                                                },
                                            }),
                                        ]}
                                    >
                                        <Input.Password
                                            prefix={<LockOutlined style={{ color: '#d1d5db' }} />}
                                            placeholder="••••••••"
                                        />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                <Button
                                    type="primary"
                                    danger
                                    htmlType="submit"
                                    icon={<LockOutlined />}
                                    loading={passLoading}
                                >
                                    Đổi mật khẩu
                                </Button>
                            </div>
                        </Form>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

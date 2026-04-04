import React from 'react';
import { Layout } from 'antd';
import { useNavigate } from 'react-router-dom';
import HeaderBar from './Header';
import Sidebar from './Sidebar';

const { Content, Sider } = Layout;

export default function AdminLayout({ children }) {
    const navigate = useNavigate();

    return (
        <Layout className="admin-shell">
            <Sider breakpoint="lg" collapsedWidth="0" width={280} className="admin-shell__sider">
                <Sidebar onNavigate={navigate} />
            </Sider>
            <Layout style={{ background: 'transparent' }}>
                <HeaderBar />
                <Content className="admin-shell__content">
                    <div className="admin-shell__panel">{children}</div>
                </Content>
            </Layout>
        </Layout>
    );
}
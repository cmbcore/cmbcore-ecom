import React, { useEffect, useRef } from 'react';

function AnimatedCanvas() {
    const canvasRef = useRef(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let raf;
        let w, h;

        const particles = Array.from({ length: 55 }, () => ({
            x: Math.random(),
            y: Math.random(),
            r: Math.random() * 2.5 + 0.8,
            dx: (Math.random() - 0.5) * 0.0004,
            dy: (Math.random() - 0.5) * 0.0004,
            opacity: Math.random() * 0.45 + 0.1,
        }));

        const resize = () => {
            w = canvas.width = canvas.offsetWidth;
            h = canvas.height = canvas.offsetHeight;
        };

        const draw = () => {
            ctx.clearRect(0, 0, w, h);
            particles.forEach((p) => {
                p.x = (p.x + p.dx + 1) % 1;
                p.y = (p.y + p.dy + 1) % 1;
                ctx.beginPath();
                ctx.arc(p.x * w, p.y * h, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(167,139,250,${p.opacity})`;
                ctx.fill();
            });

            // Soft connection lines
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = (particles[i].x - particles[j].x) * w;
                    const dy = (particles[i].y - particles[j].y) * h;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 130) {
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x * w, particles[i].y * h);
                        ctx.lineTo(particles[j].x * w, particles[j].y * h);
                        ctx.strokeStyle = `rgba(167,139,250,${0.12 * (1 - dist / 130)})`;
                        ctx.lineWidth = 0.8;
                        ctx.stroke();
                    }
                }
            }
            raf = requestAnimationFrame(draw);
        };

        resize();
        draw();
        window.addEventListener('resize', resize);
        return () => {
            cancelAnimationFrame(raf);
            window.removeEventListener('resize', resize);
        };
    }, []);

    return <canvas ref={canvasRef} className="auth-canvas" />;
}

export default function AuthLayout({ children }) {
    return (
        <div className="admin-auth-layout">
            {/* Left panel */}
            <div className="admin-auth-layout__brand">
                <AnimatedCanvas />
                <div className="admin-auth-layout__brand-content">
                    <div className="auth-brand-logo">
                        <span className="auth-brand-logo__icon">⬡</span>
                        <span className="auth-brand-logo__text">CMBCORE</span>
                    </div>
                    <h1 className="auth-brand-headline">
                        Nền tảng thương mại<br />
                        <span className="auth-brand-headline--accent">thế hệ mới</span>
                    </h1>
                    <p className="auth-brand-desc">
                        Hệ thống quản trị mạnh mẽ, hiện đại được xây dựng trên ReactJS và Laravel — tùy chỉnh linh hoạt, mở rộng không giới hạn.
                    </p>
                    <div className="auth-brand-stats">
                        <div className="auth-brand-stat">
                            <strong>26+</strong>
                            <span>Modules</span>
                        </div>
                        <div className="auth-brand-stat">
                            <strong>5+</strong>
                            <span>Plugins</span>
                        </div>
                        <div className="auth-brand-stat">
                            <strong>∞</strong>
                            <span>Themes</span>
                        </div>
                    </div>
                    <div className="auth-brand-footer">
                        <a href="https://cmbcore.com" target="_blank" rel="noreferrer">cmbcore.com</a>
                        <span>·</span>
                        <span>0966.281.850</span>
                    </div>
                </div>
            </div>

            {/* Right panel */}
            <div className="admin-auth-layout__form-wrapper">
                <div className="admin-auth-layout__form-inner">
                    {children}
                </div>
            </div>
        </div>
    );
}

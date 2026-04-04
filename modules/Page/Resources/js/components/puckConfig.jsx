/**
 * Puck editor configuration.
 *
 * Converts backend block definitions (from /pages/templates API) into
 * a Puck-compatible config object, plus registers built-in preview
 * components for each block type.
 */

// ─── Built-in block preview components ──────────────────────────────────────

function HeroPreview({ eyebrow, title, body, primary_label, secondary_label, image }) {
    return (
        <section style={{ padding: '48px 32px', background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: '#fff', borderRadius: 8 }}>
            {eyebrow && <span style={{ fontSize: 13, textTransform: 'uppercase', letterSpacing: 1.5, opacity: 0.85 }}>{eyebrow}</span>}
            {title && <h2 style={{ fontSize: 32, margin: '8px 0 12px', fontWeight: 700 }}>{title}</h2>}
            {body && <p style={{ fontSize: 16, opacity: 0.9, maxWidth: 560, marginBottom: 20 }}>{body}</p>}
            <div style={{ display: 'flex', gap: 12 }}>
                {primary_label && <span style={{ padding: '10px 24px', background: '#fff', color: '#764ba2', borderRadius: 6, fontWeight: 600, fontSize: 14 }}>{primary_label}</span>}
                {secondary_label && <span style={{ padding: '10px 24px', border: '1.5px solid #fff', borderRadius: 6, fontWeight: 600, fontSize: 14 }}>{secondary_label}</span>}
            </div>
        </section>
    );
}

function MediaTextPreview({ title, body, image_position, link_label }) {
    const isRight = image_position === 'right';
    return (
        <section style={{ display: 'flex', flexDirection: isRight ? 'row-reverse' : 'row', gap: 24, padding: '32px 24px', alignItems: 'center' }}>
            <div style={{ flex: '0 0 40%', background: '#e8e8e8', borderRadius: 8, height: 200, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#999' }}>Hình ảnh</div>
            <div style={{ flex: 1 }}>
                {title && <h3 style={{ fontSize: 22, marginBottom: 8 }}>{title}</h3>}
                {body && <p style={{ fontSize: 15, color: '#555', lineHeight: 1.6 }}>{body}</p>}
                {link_label && <span style={{ color: '#1677ff', fontWeight: 600 }}>{link_label} →</span>}
            </div>
        </section>
    );
}

function CTAPreview({ title, body, primary_label, secondary_label }) {
    return (
        <section style={{ textAlign: 'center', padding: '48px 32px', background: '#f0f5ff', borderRadius: 8 }}>
            {title && <h2 style={{ fontSize: 28, fontWeight: 700, marginBottom: 12 }}>{title}</h2>}
            {body && <p style={{ fontSize: 16, color: '#555', maxWidth: 480, margin: '0 auto 24px' }}>{body}</p>}
            <div style={{ display: 'flex', gap: 12, justifyContent: 'center' }}>
                {primary_label && <span style={{ padding: '10px 28px', background: '#1677ff', color: '#fff', borderRadius: 6, fontWeight: 600 }}>{primary_label}</span>}
                {secondary_label && <span style={{ padding: '10px 28px', border: '1.5px solid #1677ff', color: '#1677ff', borderRadius: 6, fontWeight: 600 }}>{secondary_label}</span>}
            </div>
        </section>
    );
}

function RichContentPreview({ content }) {
    return (
        <section style={{ padding: '24px 16px', lineHeight: 1.7 }}>
            <div dangerouslySetInnerHTML={{ __html: content || '<p style="color:#999">Nhập nội dung...</p>' }} />
        </section>
    );
}

function HeadingPreview({ text, level }) {
    const Tag = `h${level || 2}`;
    const sizes = { 1: 36, 2: 28, 3: 22, 4: 18, 5: 16, 6: 14 };
    return <Tag style={{ fontSize: sizes[level || 2], padding: '16px 0' }}>{text || 'Tiêu đề'}</Tag>;
}

function SpacerPreview({ height }) {
    return <div style={{ height: height || 40, background: 'repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(0,0,0,0.03) 5px, rgba(0,0,0,0.03) 10px)', borderRadius: 4 }} />;
}

function ColumnsPreview({ column_count }) {
    const count = column_count || 2;
    return (
        <div style={{ display: 'grid', gridTemplateColumns: `repeat(${count}, 1fr)`, gap: 16, padding: '24px 0' }}>
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} style={{ background: '#f5f5f5', borderRadius: 8, padding: 24, minHeight: 100, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#999', border: '1px dashed #ccc' }}>
                    Cột {i + 1}
                </div>
            ))}
        </div>
    );
}

function ImagePreview({ src, alt, caption }) {
    return (
        <figure style={{ textAlign: 'center', padding: '16px 0' }}>
            {src
                ? <img src={src} alt={alt || ''} style={{ maxWidth: '100%', borderRadius: 8 }} />
                : <div style={{ background: '#e8e8e8', borderRadius: 8, height: 200, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#999' }}>Chọn hình ảnh</div>
            }
            {caption && <figcaption style={{ marginTop: 8, fontSize: 14, color: '#666' }}>{caption}</figcaption>}
        </figure>
    );
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const RADIUS_MAP  = { none: '0', sm: '4px', md: '8px', lg: '16px', pill: '9999px' };
const SHADOW_MAP  = { none: 'none', sm: '0 1px 4px rgba(0,0,0,.08)', md: '0 4px 16px rgba(0,0,0,.12)', lg: '0 8px 32px rgba(0,0,0,.16)' };
const PADDING_MAP = { sm: '24px 20px', md: '40px 32px', lg: '56px 48px', xl: '72px 64px' };
const WIDTH_MAP   = { full: '100%', contained: '800px', narrow: '520px' };
const ANIM_LABELS = { 'fade-in': 'Fade In', 'slide-up': 'Slide Up', 'slide-left': 'Slide ←', 'zoom-in': 'Zoom In' };

function ContactFormPreview(props) {
    const {
        form_id,
        layout_width = 'contained',
        padding = 'md',
        bg_color = '#ffffff',
        text_color = '#1f2937',
        label_color = '#374151',
        input_bg = '#f9fafb',
        input_border = '#d1d5db',
        btn_color = '#1677ff',
        btn_text_color = '#ffffff',
        btn_style = 'filled',
        border_radius = 'md',
        shadow = 'none',
        animation = 'none',
    } = props;

    const radius  = RADIUS_MAP[border_radius] ?? '8px';
    const maxWidth = WIDTH_MAP[layout_width] ?? '800px';
    const pad     = PADDING_MAP[padding] ?? '40px 32px';
    const shdw    = SHADOW_MAP[shadow] ?? 'none';

    const inputStyle = {
        display: 'block', width: '100%', padding: '10px 14px', boxSizing: 'border-box',
        border: `1.5px solid ${input_border}`, borderRadius: radius,
        background: input_bg, color: '#9ca3af', fontSize: 14,
    };

    let btnStyle = {
        padding: '11px 28px', borderRadius: radius, fontWeight: 600, fontSize: 14,
        cursor: 'default', display: 'inline-block', marginTop: 4,
    };
    if (btn_style === 'outline') {
        btnStyle = { ...btnStyle, background: 'transparent', color: btn_color, border: `2px solid ${btn_color}` };
    } else if (btn_style === 'ghost') {
        btnStyle = { ...btnStyle, background: 'transparent', color: btn_color, border: 'none' };
    } else {
        btnStyle = { ...btnStyle, background: btn_color, color: btn_text_color, border: `2px solid ${btn_color}` };
    }

    const MOCK_FIELDS = [
        { label: 'Họ tên', type: 'text', required: true, width: 'half' },
        { label: 'Email', type: 'email', required: true, width: 'half' },
        { label: 'Điện thoại', type: 'phone', required: false, width: 'half' },
        { label: 'Chủ đề', type: 'text', required: false, width: 'half' },
        { label: 'Nội dung', type: 'textarea', required: true, width: 'full' },
    ];

    return (
        <section style={{ background: bg_color, color: text_color, position: 'relative' }}>
            {/* Animation badge */}
            {animation !== 'none' && (
                <span style={{
                    position: 'absolute', top: 6, right: 8, fontSize: 11, padding: '2px 8px',
                    background: '#e0f2fe', color: '#0369a1', borderRadius: 99, fontWeight: 600,
                }}>
                    ✨ {ANIM_LABELS[animation] ?? animation}
                </span>
            )}

            {/* Form ID badge */}
            {form_id && (
                <span style={{
                    position: 'absolute', top: 6, left: 8, fontSize: 11, padding: '2px 8px',
                    background: '#f0fdf4', color: '#15803d', borderRadius: 99, fontWeight: 600,
                }}>
                    Form #{form_id}
                </span>
            )}

            <div style={{
                maxWidth, margin: '0 auto', padding: pad,
                boxShadow: shdw, borderRadius: radius, background: bg_color,
            }}>
                {!form_id && (
                    <p style={{ color: '#9ca3af', textAlign: 'center', padding: '8px 0 16px', fontSize: 13 }}>
                        ← Chọn form ở thanh bên trái
                    </p>
                )}

                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12 }}>
                    {MOCK_FIELDS.map((f, i) => (
                        <div key={i} style={{ flex: f.width === 'half' ? '1 1 calc(50% - 6px)' : '1 1 100%', minWidth: 160 }}>
                            <label style={{ display: 'block', marginBottom: 5, fontSize: 13, color: label_color, fontWeight: 600 }}>
                                {f.label}{f.required && <span style={{ color: '#ef4444', marginLeft: 2 }}>*</span>}
                            </label>
                            {f.type === 'textarea'
                                ? <textarea style={{ ...inputStyle, minHeight: 72, resize: 'none' }} disabled placeholder={`Nhập ${f.label.toLowerCase()}...`} />
                                : <input style={inputStyle} disabled placeholder={`Nhập ${f.label.toLowerCase()}...`} />
                            }
                        </div>
                    ))}
                </div>

                <div style={{ marginTop: 16 }}>
                    <span style={btnStyle}>Gửi</span>
                </div>
            </div>
        </section>
    );
}


// ─── Preview map ─────────────────────────────────────────────────────────────

const PREVIEW_MAP = {
    Hero: HeroPreview,
    MediaText: MediaTextPreview,
    CTA: CTAPreview,
    RichContent: RichContentPreview,
    Heading: HeadingPreview,
    Spacer: SpacerPreview,
    Columns: ColumnsPreview,
    Image: ImagePreview,
    ContactForm: ContactFormPreview,
};

// ─── Built-in block definitions ─────────────────────────────────────────────

const BUILT_IN_BLOCKS = [
    {
        type: 'Hero',
        label: 'Hero',
        category: 'Layout',
        fields: [
            { key: 'eyebrow', label: 'Dòng trên', type: 'text' },
            { key: 'title', label: 'Tiêu đề', type: 'text' },
            { key: 'body', label: 'Nội dung', type: 'textarea' },
            { key: 'image', label: 'Hình ảnh', type: 'text' },
            { key: 'primary_label', label: 'Nút chính', type: 'text' },
            { key: 'primary_url', label: 'URL chính', type: 'text' },
            { key: 'secondary_label', label: 'Nút phụ', type: 'text' },
            { key: 'secondary_url', label: 'URL phụ', type: 'text' },
        ],
    },
    {
        type: 'MediaText',
        label: 'Hình + Nội dung',
        category: 'Layout',
        fields: [
            { key: 'title', label: 'Tiêu đề', type: 'text' },
            { key: 'body', label: 'Nội dung', type: 'textarea' },
            { key: 'image', label: 'Hình ảnh', type: 'text' },
            { key: 'image_position', label: 'Vị trí ảnh', type: 'select', options: [{ value: 'left', label: 'Trái' }, { value: 'right', label: 'Phải' }] },
            { key: 'link_label', label: 'Link label', type: 'text' },
            { key: 'link_url', label: 'Link URL', type: 'text' },
        ],
    },
    {
        type: 'CTA',
        label: 'Call to Action',
        category: 'Layout',
        fields: [
            { key: 'title', label: 'Tiêu đề', type: 'text' },
            { key: 'body', label: 'Nội dung', type: 'textarea' },
            { key: 'primary_label', label: 'Nút chính', type: 'text' },
            { key: 'primary_url', label: 'URL chính', type: 'text' },
            { key: 'secondary_label', label: 'Nút phụ', type: 'text' },
            { key: 'secondary_url', label: 'URL phụ', type: 'text' },
            { key: 'background_image', label: 'Ảnh nền', type: 'text' },
        ],
    },
    {
        type: 'RichContent',
        label: 'Nội dung Rich Text',
        category: 'Nội dung',
        fields: [
            { key: 'content', label: 'HTML nội dung', type: 'textarea' },
        ],
    },
    {
        type: 'Heading',
        label: 'Tiêu đề',
        category: 'Nội dung',
        fields: [
            { key: 'text', label: 'Nội dung', type: 'text' },
            { key: 'level', label: 'Cấp tiêu đề', type: 'select', options: [
                { value: 1, label: 'H1' }, { value: 2, label: 'H2' }, { value: 3, label: 'H3' },
                { value: 4, label: 'H4' }, { value: 5, label: 'H5' }, { value: 6, label: 'H6' },
            ] },
        ],
    },
    {
        type: 'Spacer',
        label: 'Khoảng trắng',
        category: 'Layout',
        fields: [
            { key: 'height', label: 'Chiều cao (px)', type: 'number' },
        ],
    },
    {
        type: 'Columns',
        label: 'Dạng cột',
        category: 'Layout',
        fields: [
            { key: 'column_count', label: 'Số cột', type: 'select', options: [
                { value: 2, label: '2 cột' }, { value: 3, label: '3 cột' }, { value: 4, label: '4 cột' },
            ] },
            { key: 'content', label: 'Nội dung HTML', type: 'textarea' },
        ],
    },
    {
        type: 'Image',
        label: 'Hình ảnh',
        category: 'Nội dung',
        fields: [
            { key: 'src', label: 'URL hình', type: 'text' },
            { key: 'alt', label: 'Mô tả alt', type: 'text' },
            { key: 'caption', label: 'Chú thích', type: 'text' },
        ],
    },
];

// ─── Field type mapping ──────────────────────────────────────────────────────

function mapFieldType(field) {
    switch (field.type) {
        case 'textarea':
            return { type: 'textarea' };
        case 'select':
            return {
                type: 'select',
                options: (field.options ?? []).map((option) => ({
                    value: option.value,
                    label: option.label,
                })),
            };
        case 'number':
            return { type: 'number', min: field.min ?? 0 };
        case 'image':
        case 'text':
        default:
            return { type: 'text' };
    }
}

// ─── Build Puck config from block definitions ────────────────────────────────

/**
 * Build a Puck-compatible `config` object from an array of block definitions.
 *
 * @param {Array} pluginBlocks - Additional block definitions from plugins (API response)
 * @returns {object} Puck config with `components` and `categories`
 */
export function buildPuckConfig(pluginBlocks = []) {
    const allBlocks = [...BUILT_IN_BLOCKS, ...pluginBlocks];
    const components = {};
    const categorySet = new Set();

    for (const block of allBlocks) {
        const fields = {};
        const defaultProps = {};

        for (const field of block.fields ?? []) {
            fields[field.key] = {
                label: field.label,
                ...mapFieldType(field),
            };
            if (field.default !== undefined) {
                defaultProps[field.key] = field.default;
            }
        }

        const PreviewComponent = PREVIEW_MAP[block.type];

        components[block.type] = {
            label: block.label,
            fields,
            defaultProps,
            render: PreviewComponent
                ? (props) => <PreviewComponent {...props} />
                : (props) => (
                    <div style={{ padding: 24, background: '#fafafa', borderRadius: 8, border: '1px dashed #d9d9d9' }}>
                        <strong>{block.label}</strong>
                        <pre style={{ fontSize: 12, color: '#666', marginTop: 8, whiteSpace: 'pre-wrap' }}>
                            {JSON.stringify(props, null, 2)}
                        </pre>
                    </div>
                ),
        };

        if (block.category) {
            categorySet.add(block.category);
        }
    }

    // Build categories from unique category values
    const categories = {};
    for (const cat of categorySet) {
        categories[cat] = {
            title: cat,
            components: allBlocks
                .filter((b) => b.category === cat)
                .map((b) => b.type),
        };
    }

    // "Uncategorized" for blocks without a category
    const uncategorized = allBlocks.filter((b) => !b.category).map((b) => b.type);
    if (uncategorized.length > 0) {
        categories['Plugin'] = {
            title: 'Plugin',
            components: uncategorized,
        };
    }

    return { components, categories };
}

/**
 * Empty default puck data for new pages.
 */
export const EMPTY_PUCK_DATA = { content: [], root: {} };

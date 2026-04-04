@php
    use Plugins\ContactForm\Models\ContactForm;

    $formId   = (int) ($props['form_id'] ?? 0);
    $cfgForm  = $formId ? ContactForm::find($formId) : null;
    $fields   = $cfgForm ? $cfgForm->resolvedFields() : [];

    // Style: map select values to CSS
    $radiusMap  = ['none' => '0', 'sm' => '4px', 'md' => '8px', 'lg' => '16px', 'pill' => '999px'];
    $shadowMap  = ['none' => 'none', 'sm' => '0 1px 4px rgba(0,0,0,.07)', 'md' => '0 4px 16px rgba(0,0,0,.1)', 'lg' => '0 8px 32px rgba(0,0,0,.14)'];
    $paddingMap = ['sm' => '24px 20px', 'md' => '40px 32px', 'lg' => '56px 48px', 'xl' => '72px 64px'];
    $widthMap   = ['full' => '100%', 'contained' => '800px', 'narrow' => '520px'];

    $bgColor      = e($props['bg_color']      ?? '#ffffff');
    $textColor    = e($props['text_color']    ?? '#1f2937');
    $labelColor   = e($props['label_color']   ?? '#374151');
    $inputBg      = e($props['input_bg']      ?? '#f9fafb');
    $inputBorder  = e($props['input_border']  ?? '#d1d5db');
    $btnColor     = e($props['btn_color']     ?? '#1677ff');
    $btnTextColor = e($props['btn_text_color'] ?? '#ffffff');
    $btnStyle     = $props['btn_style']     ?? 'filled';
    $borderRadius = $radiusMap[$props['border_radius'] ?? 'md'] ?? '8px';
    $shadow       = $shadowMap[$props['shadow']         ?? 'none'] ?? 'none';
    $padding      = $paddingMap[$props['padding']       ?? 'md']   ?? '40px 32px';
    $maxWidth     = $widthMap[$props['layout_width']    ?? 'contained'] ?? '800px';
    $animation    = e($props['animation'] ?? 'none');

    $inputRadius = $radiusMap[$props['border_radius'] ?? 'md'] ?? '8px';

    // Button CSS based on style
    if ($btnStyle === 'outline') {
        $btnCss = "background:transparent; color:{$btnColor}; border:2px solid {$btnColor};";
    } elseif ($btnStyle === 'ghost') {
        $btnCss = "background:transparent; color:{$btnColor}; border:none;";
    } else {
        $btnCss = "background:{$btnColor}; color:{$btnTextColor}; border:2px solid {$btnColor};";
    }

    $sectionId  = 'cf-' . $formId . '-' . uniqid();
    $successKey = 'contact_success_' . $formId;
@endphp

<section
    id="{{ $sectionId }}"
    class="cmbcore-contact-form cmbcore-cf-animate-{{ $animation }}"
    data-plugin="contact-form"
    style="background: {{ $bgColor }}; color: {{ $textColor }};"
>
    <style>
        #{{ $sectionId }} { box-sizing: border-box; }
        #{{ $sectionId }} .cf-inner {
            max-width: {{ $maxWidth }};
            margin: 0 auto;
            padding: {{ $padding }};
            box-shadow: {{ $shadow }};
            border-radius: {{ $borderRadius }};
            background: {{ $bgColor }};
        }
        #{{ $sectionId }} label { color: {{ $labelColor }}; font-size: 14px; font-weight: 600; display: block; margin-bottom: 6px; }
        #{{ $sectionId }} .required-mark { color: #ef4444; }
        #{{ $sectionId }} input,
        #{{ $sectionId }} textarea,
        #{{ $sectionId }} select {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid {{ $inputBorder }};
            border-radius: {{ $inputRadius }};
            background: {{ $inputBg }};
            color: {{ $textColor }};
            font-size: 15px;
            transition: border-color .2s;
            box-sizing: border-box;
        }
        #{{ $sectionId }} input:focus,
        #{{ $sectionId }} textarea:focus,
        #{{ $sectionId }} select:focus { outline: none; border-color: {{ $btnColor }}; }
        #{{ $sectionId }} .cf-field { margin-bottom: 18px; }
        #{{ $sectionId }} .cf-field-error { color: #ef4444; font-size: 13px; margin-top: 4px; display: block; }
        #{{ $sectionId }} .cf-row { display: flex; gap: 16px; flex-wrap: wrap; }
        #{{ $sectionId }} .cf-row .cf-field-full { flex: 1 1 100%; }
        #{{ $sectionId }} .cf-row .cf-field-half { flex: 1 1 calc(50% - 8px); min-width: 200px; }
        #{{ $sectionId }} .cf-submit {
            {{ $btnCss }}
            padding: 12px 32px; border-radius: {{ $inputRadius }};
            font-size: 15px; font-weight: 600; cursor: pointer;
            transition: opacity .2s, transform .15s;
        }
        #{{ $sectionId }} .cf-submit:hover { opacity: .88; transform: translateY(-1px); }
        #{{ $sectionId }} .cf-success {
            padding: 18px 24px; background: #f0fdf4; border: 1.5px solid #86efac;
            border-radius: {{ $borderRadius }}; color: #16a34a; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        /* Animations */
        @keyframes cfFadeIn   { from { opacity:0 } to { opacity:1 } }
        @keyframes cfSlideUp  { from { opacity:0; transform:translateY(24px) } to { opacity:1; transform:none } }
        @keyframes cfSlideLeft{ from { opacity:0; transform:translateX(-24px)} to { opacity:1; transform:none } }
        @keyframes cfZoomIn   { from { opacity:0; transform:scale(.96)       } to { opacity:1; transform:none } }
        #{{ $sectionId }}.cmbcore-cf-animate-fade-in    .cf-inner { animation: cfFadeIn    .5s ease both }
        #{{ $sectionId }}.cmbcore-cf-animate-slide-up   .cf-inner { animation: cfSlideUp   .5s ease both }
        #{{ $sectionId }}.cmbcore-cf-animate-slide-left .cf-inner { animation: cfSlideLeft .5s ease both }
        #{{ $sectionId }}.cmbcore-cf-animate-zoom-in    .cf-inner { animation: cfZoomIn    .5s ease both }
    </style>

    <div class="cf-inner">
        @if (session($successKey))
            <div class="cf-success" role="alert">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                {{ $cfgForm?->success_message ?? 'Cảm ơn bạn đã liên hệ!' }}
            </div>
        @elseif (!$cfgForm)
            <p style="color:#9ca3af; text-align:center; padding: 24px 0;">Chưa có form nào được chọn.</p>
        @else
            @if ($cfgForm->description)
                <p class="cf-description">{{ $cfgForm->description }}</p>
            @endif

            <form method="POST" action="{{ route('contact-form.store') }}" novalidate>
                @csrf
                <input type="hidden" name="_form_id" value="{{ $formId }}">

                <div class="cf-row">
                @foreach ($fields as $field)
                    @php
                        $fieldName  = $field['name']        ?? '';
                        $fieldLabel = $field['label']       ?? $fieldName;
                        $fieldType  = $field['type']        ?? 'text';
                        $required   = ! empty($field['required']);
                        $placeholder = $field['placeholder'] ?? '';
                        $width      = ($field['width'] ?? 'full') === 'half' ? 'cf-field-half' : 'cf-field-full';
                        $fieldId    = $sectionId . '-' . $fieldName;
                        $options    = $field['options'] ?? [];
                    @endphp

                    <div class="{{ $width }}">
                        <div class="cf-field">
                            <label for="{{ $fieldId }}">
                                {{ $fieldLabel }}
                                @if ($required) <span class="required-mark">*</span> @endif
                            </label>

                            @if ($fieldType === 'textarea')
                                <textarea
                                    id="{{ $fieldId }}"
                                    name="{{ $fieldName }}"
                                    rows="5"
                                    {{ $required ? 'required' : '' }}
                                    placeholder="{{ $placeholder }}"
                                >{{ old($fieldName) }}</textarea>

                            @elseif ($fieldType === 'select')
                                <select id="{{ $fieldId }}" name="{{ $fieldName }}" {{ $required ? 'required' : '' }}>
                                    <option value="">{{ $placeholder ?: '-- Chọn --' }}</option>
                                    @foreach ($options as $opt)
                                        <option value="{{ $opt['value'] }}" {{ old($fieldName) === $opt['value'] ? 'selected' : '' }}>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                </select>

                            @elseif ($fieldType === 'radio')
                                @foreach ($options as $opt)
                                    <label style="font-weight:400; display:flex; align-items:center; gap:6px; cursor:pointer;">
                                        <input type="radio" name="{{ $fieldName }}" value="{{ $opt['value'] }}"
                                            {{ old($fieldName) === $opt['value'] ? 'checked' : '' }}
                                            style="width:auto; margin:0;">
                                        {{ $opt['label'] }}
                                    </label>
                                @endforeach

                            @elseif ($fieldType === 'checkbox')
                                <label style="font-weight:400; display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="{{ $fieldName }}" value="1"
                                        {{ old($fieldName) ? 'checked' : '' }}
                                        style="width:auto; margin:0;" {{ $required ? 'required' : '' }}>
                                    {{ $placeholder ?: $fieldLabel }}
                                </label>

                            @else
                                <input
                                    type="{{ $fieldType === 'phone' ? 'tel' : ($fieldType === 'number' ? 'number' : ($fieldType === 'email' ? 'email' : 'text')) }}"
                                    id="{{ $fieldId }}"
                                    name="{{ $fieldName }}"
                                    value="{{ old($fieldName) }}"
                                    {{ $required ? 'required' : '' }}
                                    placeholder="{{ $placeholder }}"
                                >
                            @endif

                            @error($fieldName)
                                <span class="cf-field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endforeach
                </div>

                <button type="submit" class="cf-submit">
                    {{ $cfgForm->settings['button_label'] ?? 'Gửi' }}
                </button>
            </form>
        @endif
    </div>
</section>

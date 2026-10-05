// Translations are exposed per component via <x-js-i18n :keys="[...]" />, keyed by the English source string.
export function t(key, replace = {}) {
    let text = window.i18n?.[key] ?? key;
    for (const [name, value] of Object.entries(replace)) {
        text = text.replaceAll(':' + name, value);
    }
    return text;
}

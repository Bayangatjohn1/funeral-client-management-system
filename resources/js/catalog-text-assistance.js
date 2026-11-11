const WORD_PATTERN = /[\p{L}][\p{L}\p{M}\p{N}]*/gu;
const SENTENCE_START_PATTERN = /(^\s*|[.!?]+["'”’)]*\s+)([\p{L}][\p{L}\p{M}\p{N}]*)/gu;

function preserveIntentionalMixedCase(word) {
    const letters = [...word].filter((character) => /\p{L}/u.test(character));
    const hasInternalUppercase = letters.slice(1).some((character) => character !== character.toLowerCase());
    const hasLowercase = letters.some((character) => character !== character.toUpperCase());
    return hasInternalUppercase && hasLowercase;
}

function capitalizeWord(word) {
    if (!word || preserveIntentionalMixedCase(word)) return word;
    const [first, ...rest] = [...word];
    return first.toUpperCase() + rest.join('');
}

export function capitalizeWords(value) {
    return String(value).replace(WORD_PATTERN, capitalizeWord);
}

export function capitalizeSentences(value) {
    return String(value).replace(SENTENCE_START_PATTERN, (match, prefix, word) => prefix + capitalizeWord(word));
}

export function normalizeCatalogText(value, mode) {
    if (mode === 'title-words') return String(value).replace(/\s+/g,' ').trim().replace(WORD_PATTERN, word => { const [first,...rest]=[...word];return first.toLocaleUpperCase()+rest.join(''); });
    if (mode === 'words') return capitalizeWords(value);
    if (mode === 'sentences') return capitalizeSentences(value);
    return String(value);
}

export function installCatalogTextAssistance(documentObject) {
    const originalValues = new WeakMap();
    const dirtyFields = new WeakSet();

    const getField = (event) => {
        const field = event.target;
        if (!field || typeof field.getAttribute !== 'function') return null;
        if (!['INPUT', 'TEXTAREA'].includes(field.tagName)) return null;
        if (field.tagName === 'INPUT' && field.type !== 'text') return null;
        return field.getAttribute('data-text-case') ? field : null;
    };

    const normalizeField = (field) => {
        const initialValue = originalValues.get(field);
        if (!dirtyFields.has(field) || field.value === initialValue) return;
        field.value = normalizeCatalogText(field.value, field.getAttribute('data-text-case'));
        originalValues.set(field, field.value);
        dirtyFields.delete(field);
    };

    documentObject.addEventListener('focusin', (event) => {
        const field = getField(event);
        if (field && !originalValues.has(field)) originalValues.set(field, field.value);
    }, true);

    documentObject.addEventListener('input', (event) => {
        const field = getField(event);
        if (field && !event.isComposing) dirtyFields.add(field);
    }, true);

    documentObject.addEventListener('focusout', (event) => {
        const field = getField(event);
        if (field) normalizeField(field);
    }, true);

    documentObject.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form || typeof form.querySelectorAll !== 'function') return;
        form.querySelectorAll('input[data-text-case], textarea[data-text-case]').forEach(normalizeField);
    }, true);
}

if (typeof document !== 'undefined') installCatalogTextAssistance(document);

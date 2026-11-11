import test from 'node:test';
import assert from 'node:assert/strict';
import {
    capitalizeSentences,
    capitalizeWords,
    installCatalogTextAssistance,
    normalizeCatalogText,
} from '../resources/js/catalog-text-assistance.js';

test('directory title words keeps intentional spelling and normalizes word initials',()=>{
    assert.equal(normalizeCatalogText("  de la cruz o'neil BGC McArthur  ",'title-words'),"De La Cruz O'Neil BGC McArthur");
    assert.equal(normalizeCatalogText('test@example.com','unchanged'),'test@example.com');
});

test('capitalizes lowercase words while preserving acronyms, mixed case, and separators', () => {
    assert.equal(capitalizeWords('first class'), 'First Class');
    assert.equal(capitalizeWords("o'connor memorial-set"), "O'Connor Memorial-Set");
    assert.equal(capitalizeWords('NASA iPhone eBay'), 'NASA iPhone eBay');
    assert.equal(capitalizeWords('ñora de la paz 東京 funeral'), 'Ñora De La Paz 東京 Funeral');
});

test('capitalizes sentence starts without rewriting the rest of the text', () => {
    assert.equal(
        capitalizeSentences('dignified service. available for viewing! ask staff?'),
        'Dignified service. Available for viewing! Ask staff?',
    );
    assert.equal(capitalizeSentences('already Mixed-case details. second sentence.'), 'Already Mixed-case details. Second sentence.');
});

function fakeField(value, mode = 'words') {
    return {
        tagName: 'INPUT',
        type: 'text',
        value,
        getAttribute(name) {
            return name === 'data-text-case' ? mode : null;
        },
    };
}

test('does not normalize existing edit values until the user changes them', () => {
    const handlers = {};
    installCatalogTextAssistance({ addEventListener: (name, callback) => { handlers[name] = callback; } });
    const field = fakeField('first class');

    handlers.focusin({ target: field });
    handlers.focusout({ target: field });
    assert.equal(field.value, 'first class');

    handlers.focusin({ target: field });
    field.value = 'new package name';
    handlers.input({ target: field, isComposing: false });
    handlers.focusout({ target: field });
    assert.equal(field.value, 'New Package Name');
});

test('normalizes edited fields on submit and ignores non-text inputs', () => {
    const handlers = {};
    installCatalogTextAssistance({ addEventListener: (name, callback) => { handlers[name] = callback; } });
    const field = fakeField('Existing Package');
    const numeric = { ...fakeField('123', 'words'), type: 'number' };

    handlers.focusin({ target: field });
    field.value = 'new package name';
    handlers.input({ target: field, isComposing: false });
    handlers.submit({ target: { querySelectorAll: () => [field, numeric] } });

    assert.equal(field.value, 'New Package Name');
    assert.equal(numeric.value, '123');
});

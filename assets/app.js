'use strict';
// HTML constraints work without JavaScript; PHP validates every submission too.
document.querySelectorAll('.item-form input[type="text"], .item-form textarea').forEach(field => {
    const validate = () => field.setCustomValidity(!field.required || field.value.trim() ? '' : 'Please enter a value.');
    field.addEventListener('input', validate);
    field.addEventListener('change', validate);
});
document.querySelector('[data-delete-form]')?.addEventListener('submit', event => {
    if (!window.confirm('Permanently delete this item report?')) event.preventDefault();
});

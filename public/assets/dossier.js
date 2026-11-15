'use strict';

const questionForm = document.querySelector('[data-question-form]');
if (questionForm) {
    const type = questionForm.querySelector('[data-question-type]');
    const options = questionForm.querySelector('[data-question-options]');
    const updateOptionsVisibility = () => {
        options.hidden = type.value !== 'choice';
    };

    type.addEventListener('change', updateOptionsVisibility);
    updateOptionsVisibility();
}

const dialog = document.querySelector('[data-confirm-dialog]');
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (typeof dialog.showModal !== 'function') {
            if (window.confirm(form.dataset.confirm)) {
                form.submit();
            }
            return;
        }

        dialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        dialog.returnValue = '';
        dialog.addEventListener('close', () => {
            if (dialog.returnValue === 'delete') {
                form.submit();
            }
        }, { once: true });
        dialog.showModal();
    });
});

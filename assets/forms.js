// Mirror server constraints without requiring JavaScript for submission.
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', () => {
        const files = [...input.files];
        const verification = input.hasAttribute('data-verification');
        const allowed = ['image/jpeg', 'image/png', 'image/webp', ...(verification ? ['application/pdf'] : [])];
        let message = '';
        if (verification && files.length > 3) message = 'Attach up to three supporting files.';
        else if (files.some(file => file.size > 5 * 1024 * 1024 || (file.type && !allowed.includes(file.type)))) {
            message = verification ? 'Use JPG, PNG, WebP or PDF files up to 5 MB each.' : 'Choose a JPG, PNG or WebP image up to 5 MB.';
        }
        input.setCustomValidity(message);
        input.reportValidity();
    });
});

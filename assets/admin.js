(() => {
    const layout = document.querySelector('#admin-layout');
    const dialog = document.querySelector('#admin-dialog');
    if (!layout || !dialog) return;
    const content = dialog.querySelector('#admin-dialog-content');
    const title = dialog.querySelector('#admin-dialog-title');
    let controller;
    function closeMenus(except) { document.querySelectorAll('.row-menu[open]').forEach(menu => { if (menu !== except) menu.open = false; }); }
    function toast(message) {
        const node = document.querySelector('#admin-toast'); node.textContent = message; node.hidden = false;
        clearTimeout(toast.timer); toast.timer = setTimeout(() => { node.hidden = true; }, 6500);
    }
    async function review(kind, id) {
        controller?.abort(); controller = new AbortController(); closeMenus();
        title.textContent = {item: 'Review item', claim: 'Review ownership claim', user: 'Edit account'}[kind] || 'Review details';
        content.replaceChildren(); const loading = document.createElement('p'); loading.className = 'modal-help'; loading.textContent = 'Loading details…'; content.append(loading);
        if (!dialog.open) dialog.showModal();
        try {
            const response = await fetch(`admin_detail.php?kind=${encodeURIComponent(kind)}&id=${encodeURIComponent(id)}`, {signal: controller.signal, cache: 'no-store'});
            if (response.redirected && new URL(response.url).pathname.endsWith('/login.php')) { location.href = response.url; return; }
            const html = await response.text();
            if (!response.ok) throw new Error(response.status === 404 ? 'This record is no longer available.' : 'Details could not be loaded. Please try again.');
            // This admin-only endpoint renders escaped server templates, never raw user HTML.
            content.innerHTML = html;
        } catch (error) {
            if (error.name === 'AbortError') return;
            content.replaceChildren(); const message = document.createElement('p'); message.className = 'modal-feedback'; message.textContent = error.message; content.append(message);
        }
    }
    async function refreshWorkspace() {
        const response = await fetch(location.href, {cache: 'no-store'});
        if (response.redirected && new URL(response.url).pathname.endsWith('/login.php')) { location.href = response.url; return; }
        if (!response.ok) throw new Error('The action was saved, but the table could not refresh. Refresh this page to see it.');
        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        const updated = doc.querySelector('#admin-workspace');
        if (!updated) throw new Error('The action was saved. Refresh this page to update the table.');
        document.querySelector('#admin-workspace').replaceChildren(...updated.childNodes);
        const heading = document.querySelector('#admin-workspace h2');
        if (heading) { heading.tabIndex = -1; heading.focus({preventScroll: true}); }
        doc.querySelectorAll('[data-count]').forEach(source => document.querySelectorAll(`[data-count="${source.dataset.count}"]`).forEach(target => { target.textContent = source.textContent; }));
        ['.profile-menu summary strong', '.profile-menu summary small', '.profile-avatar'].forEach(selector => {
            const source = doc.querySelector(selector), target = document.querySelector(selector); if (source && target) target.textContent = source.textContent;
        });
    }
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-review]');
        if (trigger) { event.preventDefault(); review(trigger.dataset.review, trigger.dataset.id); return; }
        if (event.target.closest('[data-modal-close]')) { dialog.close(); return; }
        const archive = event.target.closest('[data-confirm-action]');
        if (archive) {
            closeMenus(); title.textContent = 'Archive item'; content.replaceChildren();
            const message = document.createElement('p'); message.className = 'modal-description'; message.textContent = `Archive “${archive.dataset.title}”? It will leave the noticeboard and any pending claims will be rejected. Its history is preserved.`;
            const form = document.createElement('form'); form.method = 'post'; form.action = 'admin_action.php'; form.dataset.adminAsync = ''; form.className = 'modal-archive-form';
            const csrf = document.querySelector('input[name="csrf"]')?.value || '';
            Object.entries({csrf, action: archive.dataset.confirmAction, id: archive.dataset.id}).forEach(([name, value]) => { const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; form.append(input); });
            const feedback = document.createElement('p'); feedback.className = 'modal-feedback'; feedback.hidden = true; feedback.setAttribute('role', 'alert');
            const actions = document.createElement('div'); actions.className = 'modal-actions';
            const submit = document.createElement('button'); submit.className = 'btn'; submit.textContent = 'Archive item';
            const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'btn secondary'; cancel.textContent = 'Cancel'; cancel.dataset.modalClose = '';
            actions.append(submit, cancel); form.append(feedback, actions); content.append(message, form); dialog.showModal(); return;
        }
        if (!event.target.closest('.row-menu')) closeMenus();
    });
    document.addEventListener('toggle', event => {
        const menu = event.target;
        if (!menu.matches?.('.row-menu') || !menu.open) return;
        closeMenus(menu); const rect = menu.querySelector('summary').getBoundingClientRect(); const panel = menu.querySelector('div');
        panel.style.left = `${Math.max(8, Math.min(rect.right - 185, innerWidth - 193))}px`;
        panel.style.top = `${rect.bottom + 5}px`;
        if (rect.bottom + 5 + panel.offsetHeight > innerHeight - 8) panel.style.top = `${Math.max(8, rect.top - panel.offsetHeight - 5)}px`;
    }, true);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenus(); });
    document.addEventListener('scroll', () => closeMenus(), true);
    window.addEventListener('resize', () => closeMenus());
    dialog.addEventListener('close', () => controller?.abort());
    document.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-admin-async]'); if (!form) return; event.preventDefault();
        const data = new FormData(form); const buttons = [...content.querySelectorAll('button')]; buttons.forEach(button => { button.disabled = true; });
        const feedback = content.querySelector('.modal-feedback'); if (feedback) feedback.hidden = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: data, headers: {Accept: 'application/json'}});
            if (!response.headers.get('Content-Type')?.includes('application/json')) throw new Error('Your session or form has expired. Refresh the page and sign in again.');
            const result = await response.json(); if (!response.ok || !result.ok) throw new Error(result.message || 'The action could not be saved.');
            dialog.close(); await refreshWorkspace(); toast(result.message);
        } catch (error) {
            if (dialog.open && feedback) { feedback.hidden = false; feedback.textContent = error.message; } else toast(error.message);
        } finally { buttons.forEach(button => { button.disabled = false; }); }
    });
})();

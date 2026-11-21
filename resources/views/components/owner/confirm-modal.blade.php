<div id="owner-confirm-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/50 px-4 py-6"
    aria-hidden="true">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true"
        aria-labelledby="owner-confirm-modal-title" aria-describedby="owner-confirm-modal-message">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <i data-feather="alert-triangle" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h2 id="owner-confirm-modal-title" class="text-lg font-bold text-slate-900">Konfirmasi tindakan</h2>
                <p id="owner-confirm-modal-message" class="mt-2 text-sm leading-6 text-slate-600">
                    Apakah kamu yakin ingin melanjutkan tindakan ini?
                </p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="owner-confirm-modal-cancel"
                class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Batal
            </button>
            <button type="button" id="owner-confirm-modal-accept"
                class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                Ya, lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
(() => {
    if (window.ownerConfirmModalInitialized) return;
    window.ownerConfirmModalInitialized = true;

    const modal = document.getElementById('owner-confirm-modal');
    if (!modal) return;

    const title = document.getElementById('owner-confirm-modal-title');
    const message = document.getElementById('owner-confirm-modal-message');
    const cancelButton = document.getElementById('owner-confirm-modal-cancel');
    const acceptButton = document.getElementById('owner-confirm-modal-accept');
    let pendingForm = null;

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
    };

    const openModal = (form) => {
        pendingForm = form;
        title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
        message.textContent = form.dataset.confirmMessage || 'Apakah kamu yakin ingin melanjutkan tindakan ini?';
        acceptButton.textContent = form.dataset.confirmAccept || 'Ya, lanjutkan';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        cancelButton.focus();
    };

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm-modal]');
        if (!form) return;

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        const statusSelect = form.querySelector('[data-status-select]');
        if (statusSelect && statusSelect.value === statusSelect.dataset.initialStatus) {
            return;
        }

        event.preventDefault();
        openModal(form);
    });

    acceptButton.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        closeModal();
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });

    cancelButton.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
</script>

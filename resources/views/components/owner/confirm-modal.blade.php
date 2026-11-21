<div id="owner-confirm-modal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/50 px-4 py-6 backdrop-blur-[2px]"
    aria-hidden="true">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true"
        aria-labelledby="owner-confirm-modal-title" aria-describedby="owner-confirm-modal-message">
        <div class="flex items-start gap-3">
            <div id="owner-confirm-modal-icon"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                <i data-feather="help-circle" class="h-5 w-5"></i>
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
                class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Batal
            </button>
            <button type="button" id="owner-confirm-modal-accept"
                class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
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
        const icon = document.getElementById('owner-confirm-modal-icon');
        const cancelButton = document.getElementById('owner-confirm-modal-cancel');
        const acceptButton = document.getElementById('owner-confirm-modal-accept');
        let pendingForm = null;
        let previousFocus = null;

        const variants = {
            primary: {
                icon: 'help-circle',
                iconClass: 'bg-blue-100 text-blue-700',
                buttonClass: 'bg-blue-900 hover:bg-blue-800 focus:ring-blue-300'
            },
            warning: {
                icon: 'alert-triangle',
                iconClass: 'bg-amber-100 text-amber-700',
                buttonClass: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-300'
            },
            danger: {
                icon: 'trash-2',
                iconClass: 'bg-red-100 text-red-700',
                buttonClass: 'bg-red-600 hover:bg-red-700 focus:ring-red-300'
            }
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
            if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
            previousFocus = null;
        };

        const openModal = (form) => {
            pendingForm = form;
            previousFocus = document.activeElement;
            const variant = variants[form.dataset.confirmVariant] || variants.primary;

            title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
            message.textContent = form.dataset.confirmMessage || 'Apakah kamu yakin ingin melanjutkan tindakan ini?';
            acceptButton.textContent = form.dataset.confirmAccept || 'Ya, lanjutkan';

            icon.className = `flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${variant.iconClass}`;
            icon.innerHTML = `<i data-feather="${variant.icon}" class="h-5 w-5"></i>`;
            acceptButton.className = `rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-offset-2 ${variant.buttonClass}`;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            if (window.feather) window.feather.replace();
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
            if (statusSelect && statusSelect.value === statusSelect.dataset.initialStatus) return;

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
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    })();
</script>

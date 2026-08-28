document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.getElementById('flashToast');
    if (toastEl) {
        new bootstrap.Toast(toastEl).show();
    }

    document.querySelectorAll('.btn-qty-plus, .btn-qty-minus').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = btn.closest('form');
            if (!form) return;
            const input = form.querySelector('input[name="qty"]');
            if (!input) return;
            const step = btn.classList.contains('btn-qty-plus') ? 1 : -1;
            let val = parseInt(input.value, 10) || 0;
            val = Math.min(val + step, parseInt(input.max, 10) || 999999);
            val = Math.max(val, parseInt(input.min, 10) || 1);
            input.value = val;
        });
    });

    document.querySelectorAll('.btn-loading-on-submit').forEach(function (btn) {
        btn.closest('form').addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerHTML = btn.dataset.loadingText || 'Memproses...';
        });
    });

    const modal = document.getElementById('confirmDeleteModal');
    const modalBody = document.getElementById('confirmDeleteMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    let pendingForm = null;

    document.querySelectorAll('form.form-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            pendingForm = form;
            if (modalBody) modalBody.textContent = form.dataset.message || 'Apakah Anda yakin ingin menghapus data ini?';
            if (modal) new bootstrap.Modal(modal).show();
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (pendingForm) {
                const button = pendingForm.querySelector('button[type="submit"]');
                if (button) button.disabled = true;
                pendingForm.submit();
            }
            if (modal) bootstrap.Modal.getInstance(modal).hide();
        });
    }
});
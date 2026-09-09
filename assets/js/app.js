document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.getElementById('flashToast');
    if (toastEl && window.bootstrap) {
        try { new bootstrap.Toast(toastEl).show(); } catch(e) {}
    }
    // MessageBox top-tier — close & auto-dismiss
    document.querySelectorAll('.message-box').forEach(function (box) {
        const closeBtn = box.querySelector('.message-box-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                box.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                box.style.opacity = '0';
                box.style.transform = 'translateY(-8px) scale(0.98)';
                setTimeout(function () { box.remove(); }, 260);
            });
        }
        if (box.querySelector('.message-box-progress')) {
            setTimeout(function () {
                box.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                box.style.opacity = '0';
                box.style.transform = 'translateY(-8px)';
                setTimeout(function () { box.remove(); }, 320);
            }, 5000);
        }
    });

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
    const modalTitle = modal ? modal.querySelector('.alert-dialog-title') : null;
    const modalIcon = modal ? modal.querySelector('.alert-dialog-icon') : null;
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    let pendingForm = null;

    function setAlertVariant(variant, iconClass) {
        if (!modal) return;
        const content = modal.querySelector('.alert-dialog');
        if (content) {
            const colors = { danger: '#DC2626', warning: '#F59E0B', success: '#22C55E', info: '#0EA5E9' };
            content.style.borderTopColor = colors[variant] || '#DC2626';
        }
        if (modalIcon) {
            modalIcon.className = 'alert-dialog-icon alert-dialog-icon--' + (variant || 'danger');
            modalIcon.innerHTML = '<i class="bi ' + (iconClass || 'bi-trash3') + '"></i>';
        }
    }

    // Global reusable AlertDialog — top tier
    window.showAlertDialog = function (opts) {
        opts = opts || {};
        const variant = opts.variant || 'danger';
        const icon = opts.icon || (variant === 'success' ? 'bi-check-circle' : variant === 'warning' ? 'bi-exclamation-triangle' : variant === 'info' ? 'bi-info-circle' : 'bi-trash3');
        if (modalTitle) modalTitle.textContent = opts.title || 'Konfirmasi';
        if (modalBody) modalBody.textContent = opts.message || 'Apakah Anda yakin?';
        if (confirmBtn) {
            confirmBtn.textContent = '';
            const i = document.createElement('i');
            i.className = 'bi ' + (opts.confirmIcon || 'bi-trash3') + ' me-1';
            confirmBtn.appendChild(i);
            confirmBtn.appendChild(document.createTextNode(' ' + (opts.confirmText || 'Ya, Hapus')));
            confirmBtn.className = 'btn btn-' + (variant === 'danger' ? 'danger' : variant === 'success' ? 'success' : 'primary');
        }
        const cancelBtn = modal ? modal.querySelector('.btn-ghost') : null;
        if (cancelBtn && opts.cancelText) cancelBtn.textContent = opts.cancelText;
        setAlertVariant(variant, icon);
        pendingForm = opts.onConfirm ? { submit: opts.onConfirm } : null;
        if (modal) new bootstrap.Modal(modal).show();
    };

    document.querySelectorAll('form.form-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            pendingForm = form;
            const variant = form.dataset.variant || 'danger';
            const icon = form.dataset.icon || 'bi-trash3';
            const title = form.dataset.title || 'Hapus produk?';
            if (modalTitle) modalTitle.textContent = title;
            if (modalBody) modalBody.textContent = form.dataset.message || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
            setAlertVariant(variant, icon);
            if (confirmBtn) {
                confirmBtn.innerHTML = '<i class="bi ' + icon + ' me-1"></i> ' + (form.dataset.confirmText || 'Ya, Hapus');
            }
            if (modal) new bootstrap.Modal(modal).show();
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (pendingForm) {
                if (pendingForm.submit && typeof pendingForm.submit === 'function' && pendingForm !== document.createElement('form')) {
                    // Check if it's a real form or callback
                    const isForm = pendingForm.tagName === 'FORM';
                    if (isForm) {
                        const button = pendingForm.querySelector('button[type="submit"]');
                        if (button) button.disabled = true;
                        pendingForm.submit();
                    } else {
                        pendingForm.submit();
                    }
                } else if (pendingForm.tagName === 'FORM') {
                    const button = pendingForm.querySelector('button[type="submit"]');
                    if (button) button.disabled = true;
                    pendingForm.submit();
                }
            }
            if (modal) {
                const inst = bootstrap.Modal.getInstance(modal);
                if (inst) inst.hide();
            }
        });
    }
});
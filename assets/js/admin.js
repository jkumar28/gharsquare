(function () {
    function initTooltips(scope) {
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Tooltip === 'undefined') {
            return;
        }

        const root = scope instanceof Element || scope instanceof Document ? scope : document;

        root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            if (!bootstrap.Tooltip.getInstance(element)) {
                new bootstrap.Tooltip(element);
            }
        });
    }

    window.AdminUi = window.AdminUi || {};
    window.AdminUi.initTooltips = initTooltips;

    function initAdminNavigation() {
        const toggle = document.querySelector('[data-admin-nav-toggle]');
        const panel = document.querySelector('[data-admin-nav-panel]');

        if (!toggle || !panel) {
            return;
        }

        function closeNavigation() {
            panel.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            const willOpen = !panel.classList.contains('is-open');
            panel.classList.toggle('is-open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        panel.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeNavigation);
        });

        document.addEventListener('click', function (event) {
            document.querySelectorAll('.admin-nav-group[open]').forEach(function (group) {
                if (!group.contains(event.target)) {
                    group.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            closeNavigation();
            document.querySelectorAll('.admin-nav-group[open]').forEach(function (group) {
                group.removeAttribute('open');
            });
            toggle.focus();
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 991) {
                closeNavigation();
            }
        });
    }

    function normalizeFlashType(type) {
        const value = (type || '').toLowerCase();

        if (value === 'danger') {
            return 'error';
        }

        if (value === 'warning') {
            return 'warning';
        }

        if (value === 'success') {
            return 'success';
        }

        return 'info';
    }

    function showLoading(title) {
        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: title || 'Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });
    }

    function showFlashMessage() {
        const flash = document.getElementById('app-flash');

        if (!flash || typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: normalizeFlashType(flash.dataset.type || ''),
            title: flash.dataset.message || '',
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: true
        });
    }

    function initDataTables() {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            return;
        }

        window.jQuery('.js-datatable').each(function () {
            if (window.jQuery.fn.DataTable.isDataTable(this)) {
                return;
            }

            window.jQuery(this).DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                language: {
                    search: '',
                    searchPlaceholder: 'Search records',
                    lengthMenu: 'Show _MENU_ rows',
                    info: 'Showing _START_ to _END_ of _TOTAL_ rows',
                    paginate: {
                        previous: 'Prev',
                        next: 'Next'
                    }
                }
            });
        });
    }

    function removeDataTableRow(row) {
        if (!row) {
            return;
        }

        const table = row.closest('table');

        if (table && typeof window.jQuery !== 'undefined' && window.jQuery.fn.DataTable.isDataTable(table)) {
            window.jQuery(table).DataTable().row(row).remove().draw(false);
            return;
        }

        row.remove();
    }

    function updateUserRow(row, payload) {
        if (!row || !payload) {
            return;
        }

        const statusCell = row.querySelector('[data-col="status"]');
        const verifiedCell = row.querySelector('[data-col="verified"]');
        const actionsCell = row.querySelector('[data-col="actions"]');

        if (statusCell && payload.status_html) {
            statusCell.innerHTML = payload.status_html;
        }

        if (verifiedCell && payload.verified_html) {
            verifiedCell.innerHTML = payload.verified_html;
        }

        if (actionsCell && payload.actions_html) {
            actionsCell.innerHTML = payload.actions_html;
            initTooltips(actionsCell);
        }
    }

    function updatePropertyRow(row, payload) {
        if (!row || !payload) {
            return;
        }

        const statusCell = row.querySelector('[data-col="status"]');
        const actionsCell = row.querySelector('[data-col="actions"]');

        if (statusCell && payload.status_html) {
            statusCell.innerHTML = payload.status_html;
        }

        if (actionsCell && payload.actions_html) {
            actionsCell.innerHTML = payload.actions_html;
            initTooltips(actionsCell);
        }
    }

    function updateUsersSummary(summary) {
        if (!summary) {
            return;
        }

        Object.keys(summary).forEach(function (key) {
            const target = document.querySelector('[data-users-summary="' + key + '"]');

            if (target) {
                target.textContent = summary[key];
            }
        });
    }

    function updatePropertySummary(summary) {
        if (!summary) {
            return;
        }

        Object.keys(summary).forEach(function (key) {
            const target = document.querySelector('[data-property-summary="' + key + '"]');

            if (target) {
                target.textContent = summary[key];
            }
        });
    }

    async function submitAsyncForm(form) {
        const rowId = form.dataset.rowId || '';
        const row = rowId !== '' ? document.querySelector('[data-row-id="' + rowId + '"]') : null;
        const loadingText = form.dataset.loadingText || 'Please wait...';

        showLoading(loadingText);

        try {
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Something went wrong. Please try again.');
            }

            await Swal.fire({
                icon: 'success',
                title: payload.message || 'Saved successfully.',
                showConfirmButton: false,
                timer: 1000,
                timerProgressBar: true
            });

            if (payload.action === 'remove-row') {
                removeDataTableRow(row);
            } else if (payload.action === 'update-user-row' && payload.row) {
                updateUserRow(row, payload.row);
            } else if (payload.action === 'update-property-row' && payload.row) {
                updatePropertyRow(row, payload.row);
            } else if (payload.redirect) {
                window.location.href = payload.redirect;
            }

            if (payload.summary) {
                updateUsersSummary(payload.summary);
                updatePropertySummary(payload.summary);
            }
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: error.message || 'Unable to complete the request.',
                showConfirmButton: false,
                timer: 1600
            });
        }
    }

    function initForms() {
        document.addEventListener('submit', async function (event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if ((form.dataset.customHandler || '') !== '') {
                return;
            }

            const confirmMessage = form.dataset.confirm || '';

            if (confirmMessage !== '') {
                event.preventDefault();

                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Please confirm',
                    text: confirmMessage,
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmButton || 'Yes',
                    cancelButtonText: 'Cancel'
                });

                if (!result.isConfirmed) {
                    return;
                }

                if (form.dataset.async === 'true') {
                    submitAsyncForm(form);
                    return;
                }

                showLoading(form.dataset.loadingText || 'Please wait...');
                form.submit();
                return;
            }

            if (form.dataset.async === 'true') {
                event.preventDefault();
                submitAsyncForm(form);
                return;
            }

            if (form.method && form.method.toLowerCase() === 'post' && form.dataset.noLoading !== 'true') {
                showLoading(form.dataset.loadingText || 'Please wait...');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        showFlashMessage();
        initAdminNavigation();
        initDataTables();
        initForms();
        initTooltips(document);
    });
})();

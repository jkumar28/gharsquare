(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-site-menu-toggle]');
        var header = document.querySelector('.site-header');

        if (!toggle || !header) {
            return;
        }

        toggle.addEventListener('click', function () {
            header.classList.toggle('is-open');
        });
    });
})();

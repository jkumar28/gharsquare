(function () {
    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function endpoint(path) {
        const base = document.querySelector('meta[name="app-url"]')?.content || "";
        return `${base.replace(/\/$/, "")}/${String(path || "").replace(/^\//, "")}`;
    }

    function trackActivity(payload) {
        const body = JSON.stringify(Object.assign({
            page_url: window.location.href,
            page_title: document.title
        }, payload || {}));

        if (navigator.sendBeacon) {
            const blob = new Blob([body], { type: "application/json" });
            navigator.sendBeacon(endpoint("track-activity"), blob);
            return;
        }

        fetch(endpoint("track-activity"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body,
            keepalive: true
        }).catch(function () {});
    }

    function accountDropdown(state) {
        const user = state.user || {};
        const links = state.links || [];
        const linkHtml = links.map(function (link) {
            return `<a href="${escapeHtml(link.href)}">${escapeHtml(link.label)}</a>`;
        }).join("");

        return `
            <div class="account-menu" data-account-menu>
                <button class="account-toggle" type="button" data-account-toggle aria-expanded="false">
                    <span class="account-avatar">${escapeHtml(user.initials || "U")}</span>
                    <span class="account-name">${escapeHtml(user.name || "User")}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="account-dropdown" data-account-dropdown>
                    <div class="account-dropdown-head">
                        <span class="account-avatar big">${escapeHtml(user.initials || "U")}</span>
                        <div>
                            <strong>${escapeHtml(user.name || "User")}</strong>
                            <small>${escapeHtml(user.email || user.phone || "")}</small>
                            <em>${escapeHtml(user.role_label || "Customer")}${user.email_verified ? " | Verified" : " | Email pending"}</em>
                        </div>
                    </div>
                    <div class="account-dropdown-links">${linkHtml}</div>
                    <a class="account-logout" href="${escapeHtml(state.logout_url || "logout")}"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        `;
    }

    function hydrateHeader() {
        const loginButtons = Array.from(document.querySelectorAll(".js-login-btn"));

        if (!loginButtons.length) {
            return;
        }

        fetch(endpoint("auth-state?redirect=" + encodeURIComponent(window.location.href)), {
            credentials: "same-origin",
            cache: "no-store"
        })
            .then(function (response) { return response.json(); })
            .then(function (state) {
                loginButtons.forEach(function (button) {
                    if (state.logged_in) {
                        button.outerHTML = accountDropdown(state);
                    } else {
                        button.setAttribute("href", state.login_url || "login");
                    }
                });

                bindDropdown();
            })
            .catch(function () {
                loginButtons.forEach(function (button) {
                    button.setAttribute("href", "login?redirect=" + encodeURIComponent(window.location.href));
                });
            });
    }

    function bindDropdown() {
        document.querySelectorAll("[data-account-menu]").forEach(function (menu) {
            const toggle = menu.querySelector("[data-account-toggle]");

            if (!toggle) {
                return;
            }

            toggle.addEventListener("click", function (event) {
                event.stopPropagation();
                const isOpen = menu.classList.toggle("open");
                toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
            });
        });

        document.addEventListener("click", function () {
            document.querySelectorAll("[data-account-menu].open").forEach(function (menu) {
                menu.classList.remove("open");
                menu.querySelector("[data-account-toggle]")?.setAttribute("aria-expanded", "false");
            });
        });
    }

    window.GharSquareAuth = {
        trackActivity
    };

    document.addEventListener("DOMContentLoaded", function () {
        hydrateHeader();
        trackActivity({
            activity_type: "page_view",
            metadata: {
                path: window.location.pathname,
                query: window.location.search
            }
        });
    });
})();

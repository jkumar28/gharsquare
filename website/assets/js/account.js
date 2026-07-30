(function () {
    function endpoint(path) {
        const base = document.querySelector('meta[name="app-url"]')?.content || "";
        return `${base.replace(/\/$/, "")}/${String(path || "").replace(/^\//, "")}`;
    }

    function removeSavedProperty(button) {
        const propertyRef = button.getAttribute("data-remove-saved");
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

        if (!propertyRef) {
            return;
        }

        button.disabled = true;
        button.textContent = "Removing";

        fetch(endpoint("saved-property"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken
            },
            credentials: "same-origin",
            body: JSON.stringify({
                action: "unsave",
                property_ref: propertyRef,
                csrf_token: csrfToken,
                source: "account_saved",
                page_url: window.location.href,
                page_title: document.title
            })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.login_required && data.login_url) {
                    window.location.href = data.login_url;
                    return;
                }

                if (!data.success) {
                    throw new Error(data.message || "Unable to remove saved property.");
                }

                const card = button.closest("[data-saved-card]");
                if (card) {
                    card.remove();
                }

                if (!document.querySelector("[data-saved-card]")) {
                    window.location.reload();
                }
            })
            .catch(function (error) {
                button.disabled = false;
                button.textContent = "Remove";
                window.alert(error.message || "Unable to remove saved property.");
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.addEventListener("click", function (event) {
            const button = event.target.closest("[data-remove-saved]");

            if (button) {
                removeSavedProperty(button);
            }
        });
    });
})();

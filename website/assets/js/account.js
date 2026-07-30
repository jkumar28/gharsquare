(function () {
    function removeSavedProperty(button) {
        const propertyRef = button.getAttribute("data-remove-saved");

        if (!propertyRef) {
            return;
        }

        button.disabled = true;
        button.textContent = "Removing";

        fetch("saved-property", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({
                action: "unsave",
                property_ref: propertyRef,
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
            .catch(function () {
                button.disabled = false;
                button.textContent = "Remove";
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

(function () {
    const root = document.querySelector(".listing-main");
    const csrfToken = root?.dataset.csrfToken || "";

    function postJson(path, payload) {
        return fetch(path, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken
            },
            credentials: "same-origin",
            body: JSON.stringify(Object.assign({ csrf_token: csrfToken }, payload))
        }).then(async function (response) {
            const data = await response.json();
            if (data.login_required && data.login_url) {
                window.location.href = data.login_url;
                return null;
            }
            if (!response.ok || !data.success) {
                throw new Error(data.message || "Request failed.");
            }
            return data;
        });
    }

    function notice(icon, title, text) {
        if (window.Swal) {
            Swal.fire({ icon, title, text, confirmButtonColor: "#0f766e" });
        }
    }

    document.querySelectorAll("[data-save-property]").forEach(function (button) {
        button.addEventListener("click", function () {
            const card = button.closest("[data-property-id]");
            const propertyId = card?.dataset.propertyId;
            if (!propertyId) return;

            const shouldSave = !button.classList.contains("active");
            postJson("saved-property", {
                property_ref: propertyId,
                action: shouldSave ? "save" : "unsave",
                source: "listing_card",
                page_url: window.location.href
            }).then(function (data) {
                if (!data) return;
                button.classList.toggle("active", !!data.saved);
                button.innerHTML = `<i class="bi ${data.saved ? "bi-heart-fill" : "bi-heart"}"></i>`;
                notice("success", data.saved ? "Property saved" : "Removed from saved", data.message || "");
            }).catch(function (error) {
                notice("error", "Could not update property", error.message);
            });
        });
    });

    document.querySelectorAll("[data-quick-enquiry]").forEach(function (button) {
        button.addEventListener("click", function () {
            const card = button.closest("[data-property-id]");
            const propertyId = card?.dataset.propertyId;
            if (!propertyId) return;

            postJson("property-enquiry", {
                property_ref: propertyId,
                enquiry_type: "callback",
                preferred_contact: "call",
                message: "I am interested in this property. Please arrange a callback.",
                consent: true,
                source: "listing_card",
                page_url: window.location.href
            }).then(function (data) {
                if (!data) return;
                notice("success", "Enquiry sent", data.message);
            }).catch(function (error) {
                notice("error", "Could not send enquiry", error.message);
            });
        });
    });
})();

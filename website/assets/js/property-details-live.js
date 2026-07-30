(function () {
    const root = document.querySelector(".details-main[data-property-id]");
    if (!root) return;

    const propertyId = root.dataset.propertyId;
    const csrfToken = root.dataset.csrfToken || "";
    const gallery = [];
    let galleryIndex = 0;

    function endpoint(path) {
        const base = document.querySelector('meta[name="app-url"]')?.content || "";
        return `${base.replace(/\/$/, "")}/${String(path || "").replace(/^\//, "")}`;
    }

    document.querySelectorAll("[data-gallery-thumb]").forEach(function (thumb) {
        gallery.push(thumb.dataset.src || "");
        thumb.addEventListener("click", function () {
            galleryIndex = Number(thumb.dataset.index || 0);
            renderGallery();
        });
    });

    if (!gallery.length) {
        const main = document.querySelector("[data-gallery-main]");
        if (main?.src) gallery.push(main.src);
    }

    function renderGallery() {
        const main = document.querySelector("[data-gallery-main]");
        if (main && gallery[galleryIndex]) main.src = gallery[galleryIndex];
        document.querySelectorAll("[data-gallery-thumb]").forEach(function (thumb) {
            thumb.classList.toggle("active", Number(thumb.dataset.index) === galleryIndex);
        });
    }

    document.querySelector("[data-gallery-prev]")?.addEventListener("click", function () {
        galleryIndex = (galleryIndex - 1 + gallery.length) % gallery.length;
        renderGallery();
    });
    document.querySelector("[data-gallery-next]")?.addEventListener("click", function () {
        galleryIndex = (galleryIndex + 1) % gallery.length;
        renderGallery();
    });

    function postJson(path, payload) {
        return fetch(endpoint(path), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken
            },
            credentials: "same-origin",
            body: JSON.stringify(Object.assign({
                csrf_token: csrfToken,
                property_ref: propertyId,
                page_url: window.location.href
            }, payload))
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
            return Swal.fire({ icon, title, text, confirmButtonColor: "#0f766e" });
        }
        alert(`${title}\n${text || ""}`);
        return Promise.resolve();
    }

    document.querySelector("[data-detail-save]")?.addEventListener("click", function (event) {
        const button = event.currentTarget;
        const shouldSave = !button.classList.contains("active");
        postJson("saved-property", {
            action: shouldSave ? "save" : "unsave",
            source: "property_details"
        }).then(function (data) {
            if (!data) return;
            button.classList.toggle("active", !!data.saved);
            button.innerHTML = `<i class="bi ${data.saved ? "bi-heart-fill" : "bi-heart"}"></i>`;
            notice("success", data.saved ? "Property saved" : "Removed from saved", data.message || "");
        }).catch(function (error) {
            notice("error", "Could not update property", error.message);
        });
    });

    document.querySelector("[data-property-enquiry-form]")?.addEventListener("submit", function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const submit = form.querySelector("button[type='submit']");
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        payload.consent = formData.get("consent") === "1";
        payload.source = "property_details_form";

        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending';

        postJson("property-enquiry", payload).then(function (data) {
            if (!data) return;
            form.reset();
            form.querySelector("[name='message']").value = "I am interested in this property. Please share the next steps.";
            return notice("success", "Enquiry sent", data.message);
        }).catch(function (error) {
            return notice("error", "Could not send enquiry", error.message);
        }).finally(function () {
            submit.disabled = false;
            submit.innerHTML = '<i class="bi bi-send"></i> Send Enquiry';
        });
    });

    window.GharSquareAuth?.trackActivity({
        activity_type: "property_view",
        entity_type: "property",
        entity_id: propertyId,
        page_url: window.location.href,
        page_title: document.title,
        metadata: { source: "property_details" }
    });
})();

(function () {
    const wizard = document.getElementById("postPropertyWizard");

    if (!wizard) {
        return;
    }

    const config = window.postPropertyData || {};
    const stepOrder = ["basic", "location", "profile", "pricing", "amenities", "media", "review"];
    const states = config.states || JSON.parse(wizard.dataset.states || "[]");
    const cities = config.cities || JSON.parse(wizard.dataset.cities || "[]");
    const localities = config.localities || JSON.parse(wizard.dataset.localities || "[]");
    const fixedCountryId = String(config.public_country_id || "");
    const saveStatus = document.querySelector("[data-save-status]");
    const progressBar = document.querySelector("[data-progress-bar]");
    const overallProgress = document.querySelector("[data-overall-progress]");
    const reviewProgress = document.querySelector("[data-review-progress]");
    const missingList = document.querySelector("[data-missing-list]");
    const mediaGrid = document.querySelector("[data-property-media-grid]");
    const imageCount = document.querySelector("[data-property-image-count]");
    let activeStep = stepOrder[Math.max(0, Math.min(stepOrder.length - 1, Number(wizard.dataset.currentStep || 1) - 1))] || "basic";
    let saveTimer = null;
    let googleMapsLoader = null;
    let descriptionTemplatesLoaded = false;

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function setStatus(text, tone) {
        if (!saveStatus) return;
        saveStatus.textContent = text;
        saveStatus.dataset.tone = tone || "idle";
    }

    function showNotice(options) {
        if (window.Swal) {
            return Swal.fire(Object.assign({ confirmButtonColor: "#0f766e" }, options || {}));
        }

        if (options && options.text) {
            alert(options.text);
        }

        return Promise.resolve();
    }

    function uploadedImageCount() {
        return Number(imageCount?.textContent || 0);
    }

    function confirmMissingMediaBeforeReview() {
        if (uploadedImageCount() > 0) {
            return Promise.resolve(true);
        }

        const text = "You can continue without photos, but listings with no media usually get lower visibility and fewer enquiries. You can upload photos later from your draft.";

        if (window.Swal) {
            return Swal.fire({
                icon: "warning",
                title: "Continue without media?",
                text,
                showCancelButton: true,
                confirmButtonText: "Continue to review",
                cancelButtonText: "Upload media",
                confirmButtonColor: "#0f766e",
                cancelButtonColor: "#64748b"
            }).then(result => result.isConfirmed);
        }

        return Promise.resolve(window.confirm(text));
    }

    function showStep(step) {
        activeStep = step;
        document.querySelectorAll("[data-step-panel]").forEach(panel => {
            panel.classList.toggle("active", panel.dataset.stepPanel === step);
        });
        document.querySelectorAll("[data-step-target]").forEach(button => {
            button.classList.toggle("active", button.dataset.stepTarget === step);
        });
        syncSmartContext();
        if (step === "review") {
            fetchDescriptionTemplates({ autofill: true });
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function updateProgress(progress) {
        if (!progress) return;

        const percent = String(progress.overall_percent || "0");
        const previewLink = document.querySelector("[data-property-preview]");
        if (overallProgress) overallProgress.textContent = percent;
        if (reviewProgress) reviewProgress.textContent = percent;
        if (progressBar) progressBar.style.width = `${percent}%`;
        if (previewLink) previewLink.hidden = Number(percent) < 100;

        Object.entries(progress.step_meta || {}).forEach(([step, meta]) => {
            const badge = document.querySelector(`[data-step-percent="${step}"]`);
            if (badge) badge.textContent = `${meta.percent || 0}%`;
        });

        if (missingList) {
            const items = (progress.missing || []).slice(0, 8);
            missingList.innerHTML = items.length
                ? items.map(item => `<li>${escapeHtml(item)}</li>`).join("")
                : "<li>All required details are ready.</li>";
        }
    }

    function saveForm(form, options = {}) {
        const data = new FormData(form);
        const submitFinal = options.submit === true;
        const step = form.dataset.stepForm || activeStep;

        if (!submitFinal) {
            data.set("action", "save_step");
        }

        if (options.validate === true) {
            data.set("validate_step", "1");
        }

        data.set("step", step);
        setStatus("Saving draft...", "saving");

        return fetch("post-property-draft", {
            method: "POST",
            credentials: "same-origin",
            body: data
        })
            .then(response => response.json())
            .then(data => {
                if (data.login_required && data.login_url) {
                    window.location.href = data.login_url;
                    return data;
                }

                if (!data.success) {
                    throw new Error(data.message || "Unable to save draft.");
                }

                updateProgress(data.progress);
                setStatus(submitFinal ? "Submitted" : "Draft saved", submitFinal ? "submitted" : "saved");

                if (!submitFinal && step !== "media") {
                    descriptionTemplatesLoaded = false;
                }

                if (submitFinal) {
                    const done = () => {
                        window.location.href = data.redirect_url || "account?view=properties";
                    };

                    showNotice({
                        icon: "success",
                        title: "Submitted for review",
                        text: data.message || "Your property is now in the review queue."
                    }).then(done);
                }

                return data;
            })
            .catch(error => {
                setStatus(error.message || "Save failed", "error");
                if (options.alert !== false) {
                    showNotice({
                        icon: "error",
                        title: "Draft not saved",
                        text: error.message || "Please check the form and try again."
                    });
                }
                throw error;
            });
    }

    function scheduleSave(form) {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            saveForm(form, { alert: false }).catch(() => {});
        }, 800);
    }

    function fillSelect(select, rows, valueKey, labelKey, selectedValue, placeholder) {
        if (!select) return;
        select.innerHTML = `<option value="">${placeholder}</option>` + rows.map(row => {
            const value = String(row[valueKey] || "");
            const selected = value === String(selectedValue || "") ? " selected" : "";
            return `<option value="${escapeHtml(value)}"${selected}>${escapeHtml(row[labelKey] || "")}</option>`;
        }).join("");
    }

    function syncLocationSelects(seed = {}) {
        const country = document.querySelector("[data-country-select]");
        const state = document.querySelector("[data-state-select]");
        const city = document.querySelector("[data-city-select]");
        const locality = document.querySelector("[data-locality-select]");

        if (country) {
            country.value = fixedCountryId || country.value || "";
        }

        const selectedState = Object.prototype.hasOwnProperty.call(seed, "state_id") ? seed.state_id : (state?.dataset.selected || state?.value || "");
        const selectedCity = Object.prototype.hasOwnProperty.call(seed, "city_id") ? seed.city_id : (city?.dataset.selected || city?.value || "");
        const selectedLocality = Object.prototype.hasOwnProperty.call(seed, "locality_id") ? seed.locality_id : (locality?.dataset.selected || locality?.value || "");
        const countryId = country?.value || "";
        const filteredStates = states.filter(item => String(item.country_id || "") === String(countryId));

        fillSelect(state, filteredStates, "id", "name", selectedState, "Select state");

        const stateId = state?.value || "";
        const filteredCities = cities.filter(item => String(item.state_id || "") === String(stateId));
        fillSelect(city, filteredCities, "id", "name", selectedCity, "Select city");

        const cityId = city?.value || "";
        const filteredLocalities = localities.filter(item => String(item.city_id || "") === String(cityId));
        fillSelect(locality, filteredLocalities, "id", "name", selectedLocality, "Select locality");

        if (state) state.dataset.selected = "";
        if (city) city.dataset.selected = "";
        if (locality) locality.dataset.selected = "";
    }

    function selectedListingInput() {
        return document.querySelector("input[name='listing_type_id']:checked");
    }

    function selectedListingLabel() {
        const selected = selectedListingInput();
        return (selected?.dataset.listingLabel || selected?.closest("label")?.textContent || "").trim();
    }

    function selectedListingMode() {
        const label = selectedListingLabel().toLowerCase();

        if (label === "") {
            return "none";
        }

        return label.includes("sell") || label.includes("sale") ? "sell" : "rent";
    }

    function isPgListing() {
        const label = selectedListingLabel().toLowerCase();

        return label === "pg" || label.includes("paying guest") || label.includes("co-living");
    }

    function selectedPostedBy() {
        return (byId("posted_by")?.value || "").toLowerCase().trim();
    }

    function selectedCategory() {
        const select = document.querySelector("[data-property-type-select]");
        const selectedOption = select?.selectedOptions?.[0];
        return selectedOption?.dataset.category || document.querySelector("input[name='property_category']:checked")?.value || "";
    }

    function selectedPropertyTypeName() {
        const select = document.querySelector("[data-property-type-select]");
        const selectedOption = select?.selectedOptions?.[0];
        return (selectedOption?.dataset.name || selectedOption?.textContent || "").toLowerCase().trim();
    }

    function isOfficeProfile() {
        const name = selectedPropertyTypeName();
        return selectedCategory() === "commercial" && (name.includes("office") || name.includes("co-working") || name.includes("coworking"));
    }

    function syncCategoryFromType() {
        const select = document.querySelector("[data-property-type-select]");
        const category = select?.selectedOptions?.[0]?.dataset.category || "";
        const radio = category ? document.querySelector(`input[name='property_category'][value="${category}"]`) : null;

        if (radio) {
            radio.checked = true;
        }
    }

    function syncPropertyTypes() {
        const pgListing = isPgListing();
        const categoryInputs = document.querySelectorAll("input[name='property_category']");
        const residentialInput = document.querySelector("input[name='property_category'][value='residential']");

        categoryInputs.forEach(input => {
            const choice = input.closest(".post-choice");
            const isAllowed = !pgListing || input.value === "residential";

            if (choice) {
                choice.hidden = !isAllowed;
            }

            input.disabled = !isAllowed;
        });

        if (pgListing && residentialInput) {
            residentialInput.disabled = false;
            residentialInput.checked = true;
        }

        const category = document.querySelector("input[name='property_category']:checked")?.value || "";
        const select = document.querySelector("[data-property-type-select]");
        if (!select) return;

        Array.from(select.options).forEach(option => {
            if (!option.value) return;
            option.hidden = option.dataset.category !== category;
        });

        const active = select.selectedOptions[0];
        if (active && active.hidden) {
            select.value = "";
        }

        renderPropertyTypeFlow();
    }

    function activePropertyTypeFlowKey() {
        return isPgListing() ? "pg" : (document.querySelector("input[name='property_category']:checked")?.value || "");
    }

    function activePropertyTypeGroups() {
        const groups = config.property_type_flow?.[activePropertyTypeFlowKey()];

        if (!Array.isArray(groups)) {
            return [];
        }

        return groups.filter(group => {
            if (!Array.isArray(group.types) || group.types.length === 0) {
                return false;
            }

            if (activePropertyTypeFlowKey() === "land" && selectedListingMode() !== "sell" && group.key === "residential-land") {
                return false;
            }

            return true;
        });
    }

    function renderPropertyTypeFlow() {
        const wrapper = document.querySelector("[data-property-type-flow]");
        const nativeField = document.querySelector("[data-native-property-type]");
        const groupList = document.querySelector("[data-property-group-list]");
        const subtypeSection = document.querySelector("[data-property-subtype-section]");
        const subtypeList = document.querySelector("[data-property-subtype-list]");
        const subtypeHeading = document.querySelector("[data-property-subtype-heading]");
        const groupInput = byId("property_group");
        const typeSelect = document.querySelector("[data-property-type-select]");
        const groups = activePropertyTypeGroups();

        if (!wrapper || !groupList || !subtypeSection || !subtypeList || !groupInput || !typeSelect || groups.length === 0) {
            if (wrapper) wrapper.hidden = true;
            if (nativeField) nativeField.hidden = false;
            return;
        }

        wrapper.hidden = false;
        if (nativeField) nativeField.hidden = true;

        const allowedTypeIds = groups.flatMap(group => group.types.map(type => String(type.id)));
        if (typeSelect.value && !allowedTypeIds.includes(String(typeSelect.value))) {
            typeSelect.value = "";
        }

        const selectedTypeId = String(typeSelect.value || "");
        const inferredGroup = groups.find(group => group.types.some(type => String(type.id) === selectedTypeId));
        let selectedGroupKey = groupInput.value || inferredGroup?.key || "";

        if (!groups.some(group => group.key === selectedGroupKey)) {
            selectedGroupKey = inferredGroup?.key || "";
        }

        groupInput.value = selectedGroupKey;
        groupList.innerHTML = groups.map(group => `
            <button type="button" class="post-type-chip${group.key === selectedGroupKey ? " active" : ""}" data-property-group="${escapeHtml(group.key)}">
                ${escapeHtml(group.label)}
            </button>
        `).join("");

        const selectedGroup = groups.find(group => group.key === selectedGroupKey);

        if (!selectedGroup) {
            subtypeSection.hidden = true;
            subtypeList.innerHTML = "";
            return;
        }

        if (selectedGroup.types.length === 1 && !selectedTypeId) {
            typeSelect.value = String(selectedGroup.types[0].id);
        }

        subtypeSection.hidden = false;
        if (subtypeHeading) {
            subtypeHeading.textContent = selectedGroup.question || "Select exact property type";
        }
        subtypeList.innerHTML = selectedGroup.types.map(type => `
            <button type="button" class="post-type-chip${String(typeSelect.value) === String(type.id) ? " active" : ""}" data-property-subtype="${type.id}">
                ${escapeHtml(type.name)}
            </button>
        `).join("");
    }

    function choosePropertyGroup(groupKey) {
        const groups = activePropertyTypeGroups();
        const group = groups.find(item => item.key === groupKey);
        const groupInput = byId("property_group");
        const typeSelect = document.querySelector("[data-property-type-select]");

        if (!group || !groupInput || !typeSelect) {
            return;
        }

        groupInput.value = group.key;
        if (!group.types.some(type => String(type.id) === String(typeSelect.value))) {
            typeSelect.value = group.types.length === 1 ? String(group.types[0].id) : "";
        }
        renderPropertyTypeFlow();
        syncSmartContext();
    }

    function choosePropertySubtype(typeId) {
        const typeSelect = document.querySelector("[data-property-type-select]");

        if (!typeSelect) {
            return;
        }

        typeSelect.value = String(typeId || "");
        syncCategoryFromType();
        renderPropertyTypeFlow();
        syncSmartContext();
    }

    function setWrapperEnabled(wrapper, enabled) {
        wrapper.hidden = !enabled;
        wrapper.querySelectorAll("input, select, textarea").forEach(input => {
            input.disabled = !enabled;
            if (!enabled && input.type === "checkbox") {
                input.checked = false;
            }
        });
    }

    function syncProfileFields() {
        const category = selectedCategory();
        const officeProfile = isOfficeProfile();
        const pgProfile = isPgListing();
        const note = document.querySelector("[data-profile-context]");
        const areaUnit = byId("area_unit");
        const allowedAreaUnits = config.area_units_by_category?.[category] || ["sq.ft", "sq.yards", "sq.m"];
        const labels = {
            residential: "Residential fields are visible: area, rooms, furnishing, floors and extra spaces.",
            commercial: "Commercial fields are visible: area, parking, floor, furnishing, age and ownership.",
            land: "Land fields are visible: plot area, facing and ownership. Home-room inputs are hidden."
        };

        document.querySelectorAll("[data-profile-field]").forEach(wrapper => {
            const allowed = (wrapper.dataset.visibleFor || "").split(/\s+/).filter(Boolean);
            setWrapperEnabled(wrapper, allowed.includes(category));
        });

        document.querySelectorAll("[data-office-hide-field]").forEach(wrapper => {
            if (officeProfile) {
                setWrapperEnabled(wrapper, false);
            }
        });
        document.querySelectorAll("[data-pg-hide-field]").forEach(wrapper => {
            if (pgProfile) {
                setWrapperEnabled(wrapper, false);
            }
        });

        document.querySelectorAll("[data-office-profile]").forEach(wrapper => {
            setWrapperEnabled(wrapper, officeProfile);
        });
        document.querySelectorAll("[data-pg-profile]").forEach(wrapper => {
            setWrapperEnabled(wrapper, pgProfile);
        });

        syncOfficeWashroomFields();

        if (areaUnit) {
            Array.from(areaUnit.options).forEach(option => {
                const allowed = allowedAreaUnits.includes(option.value);
                option.hidden = !allowed;
                option.disabled = !allowed;
            });
            if (!allowedAreaUnits.includes(areaUnit.value)) {
                areaUnit.value = allowedAreaUnits[0] || "sq.ft";
            }
        }

        document.querySelectorAll('[data-step-form="profile"] input, [data-step-form="profile"] select').forEach(input => {
            input.required = false;
        });

        const requiredNames = officeProfile
            ? ["area_unit", "office_min_seats", "office_cabins", "office_meeting_rooms"]
            : pgProfile
                ? ["area_unit", "bedrooms", "bathrooms", "furnishing", "property_age", "pg_room_type", "pg_available_for"]
            : category === "residential"
            ? ["area_unit", "bedrooms", "bathrooms", "furnishing", "property_age", "facing"]
            : category === "commercial"
                ? ["area_unit", "property_age", "facing"]
                : ["area_unit", "plot_area", "facing"];

        requiredNames.forEach(name => {
            const input = document.querySelector(`[data-step-form="profile"] [name="${name}"]`);
            if (input && !input.disabled) input.required = true;
        });

        if (note) {
            note.textContent = labels[category] || labels.residential;
        }

        syncFurnishingItems();
    }

    function syncOfficeWashroomFields() {
        const panel = document.querySelector("[data-office-profile]");
        const washrooms = panel?.querySelector("input[name='office_washrooms']:checked")?.value || "not_available";
        const counts = document.querySelector("[data-office-washroom-counts]");
        const enabled = !panel?.hidden && washrooms === "available";

        if (!counts) {
            return;
        }

        counts.hidden = !enabled;
        counts.querySelectorAll("input").forEach(input => {
            input.disabled = !enabled;
            if (!enabled) {
                input.value = "";
            }
        });
    }

    function syncFurnishingItems() {
        const select = document.querySelector("[data-furnishing-select]");
        const panel = document.querySelector("[data-furnishing-panel]");
        const countTarget = document.querySelector("[data-furnishing-count]");
        const items = Array.from(document.querySelectorAll("[data-furnishing-item]"));
        const furnishing = select?.value || "";
        const shouldShow = (furnishing === "semi" || furnishing === "fully") && !select?.disabled;
        const pgProfile = isPgListing();

        if (!panel) {
            return;
        }

        panel.hidden = !shouldShow;
        document.querySelectorAll("[data-furnishing-item-wrap]").forEach(wrapper => {
            const item = wrapper.querySelector("[data-furnishing-item]");
            const allowed = !pgProfile || wrapper.dataset.pgFurnishingItem === "1";
            wrapper.hidden = !allowed;
            if (item) {
                item.disabled = !shouldShow || !allowed;
                if (!allowed) {
                    item.checked = false;
                }
            }
        });
        panel.querySelectorAll("input").forEach(input => {
            if (!input.hasAttribute("data-furnishing-item")) {
                input.disabled = !shouldShow;
            }
        });

        if (!shouldShow) {
            panel.dataset.fullyAutoApplied = "";
            items.forEach(item => {
                item.checked = false;
            });
        } else if (furnishing === "fully" && panel.dataset.fullyAutoApplied !== "1" && !items.some(item => item.checked && !item.disabled)) {
            const defaults = pgProfile ? (config.pg_furnishing_defaults || []) : (config.fully_furnished_defaults || []);
            items.forEach(item => {
                item.checked = !item.disabled && defaults.includes(item.value);
            });
            panel.dataset.fullyAutoApplied = "1";
        }

        if (countTarget) {
            const count = items.filter(item => item.checked && !item.disabled).length;
            countTarget.textContent = `${count} selected`;
        }
    }

    function syncProfileLimits() {
        const superBuiltup = byId("super_builtup_area");
        const builtup = byId("builtup_area");
        const carpet = byId("carpet_area");
        const totalFloor = byId("total_floor");
        const floorNo = byId("floor_no");

        if (builtup) {
            builtup.max = Number(superBuiltup?.value || 0) > 0 ? superBuiltup.value : "";
        }
        if (carpet) {
            carpet.max = Number(builtup?.value || 0) > 0 ? builtup.value : "";
        }
        if (floorNo) {
            floorNo.max = Number(totalFloor?.value || 0) >= 0 && totalFloor?.value !== "" ? totalFloor.value : "";
        }
    }

    function syncPricingFields() {
        const mode = selectedListingMode();
        const note = document.querySelector("[data-pricing-context]");
        const category = selectedCategory();

        document.querySelectorAll("[data-pricing-field]").forEach(wrapper => {
            const fieldMode = wrapper.dataset.pricingMode || "both";
            const enabled = mode !== "none" && (fieldMode === "both" || fieldMode === mode);
            setWrapperEnabled(wrapper, enabled);
        });

        const pgListing = isPgListing();
        const ownerPosting = selectedPostedBy() === "owner";

        document.querySelectorAll("[data-pg-pricing-hide]").forEach(wrapper => {
            if (pgListing) {
                setWrapperEnabled(wrapper, false);
            }
        });

        document.querySelectorAll("[data-brokerage-section]").forEach(wrapper => {
            if (ownerPosting) {
                setWrapperEnabled(wrapper, false);
            }
        });

        const expectedPrice = byId("expected_price");
        const rent = byId("rent");
        const availableFrom = byId("available_from");

        if (expectedPrice) expectedPrice.required = mode === "sell";
        if (rent) rent.required = mode === "rent";
        if (availableFrom) availableFrom.required = mode === "rent";

        const depositType = document.querySelector("input[name='security_deposit_type']:checked")?.value || "none";
        const depositAmount = byId("security_deposit_amount");
        const depositMonths = byId("security_deposit_months");
        const legacyDeposit = byId("deposit");

        document.querySelectorAll("[data-security-deposit-detail]").forEach(wrapper => {
            const enabled = mode === "rent" && wrapper.dataset.securityDepositDetail === depositType;
            setWrapperEnabled(wrapper, enabled);
        });

        if (depositAmount) depositAmount.required = mode === "rent" && depositType === "fixed";
        if (depositMonths) depositMonths.required = mode === "rent" && depositType === "multiple";
        if (legacyDeposit && mode === "rent") {
            legacyDeposit.value = depositType === "fixed"
                ? (depositAmount?.value || "")
                : depositType === "multiple"
                    ? (depositMonths?.value || "")
                    : "";
        }

        const brokerageType = document.querySelector("input[name='brokerage_type']:checked")?.value || "none";
        const brokerageValue = byId("brokerage_value");

        document.querySelectorAll("[data-brokerage-detail]").forEach(wrapper => {
            const allowed = (wrapper.dataset.brokerageDetail || "").split(/\s+/).filter(Boolean);
            setWrapperEnabled(wrapper, mode !== "none" && !ownerPosting && allowed.includes(brokerageType));
        });

        if (brokerageValue) {
            brokerageValue.required = mode !== "none" && !ownerPosting && brokerageType !== "none";
            brokerageValue.max = brokerageType === "percentage" ? "100" : "";
            brokerageValue.placeholder = brokerageType === "percentage" ? "Enter percentage" : "Enter fixed amount";
        }

        if (note) {
            if (mode === "none") {
                note.textContent = "Choose a listing type in Basic Details to show sale or rent pricing fields.";
            } else if (mode === "sell") {
                note.textContent = category === "land"
                    ? "Sale price fields are visible for land. Price per area unit uses plot area."
                    : "Sale price fields are visible. Price per area unit uses the best saved area value.";
            } else {
                note.textContent = category === "land"
                    ? "Rent fields are visible for land. Rent per area unit uses plot area."
                    : ownerPosting
                        ? "Rent fields are visible. Brokerage is hidden for owner-posted properties."
                        : "Rent fields are visible with deposit, maintenance, electricity and brokerage options.";
            }
        }
    }

    function formatIndianNumber(value) {
        return new Intl.NumberFormat("en-IN", { maximumFractionDigits: 2 }).format(Number(value || 0));
    }

    function twoDigits(number) {
        const ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
        const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
        const value = Number(number || 0);

        if (value < 20) {
            return ones[value] || "";
        }

        return `${tens[Math.floor(value / 10)]}${value % 10 ? " " + ones[value % 10] : ""}`.trim();
    }

    function threeDigits(number) {
        const value = Number(number || 0);
        const hundred = Math.floor(value / 100);
        const rest = value % 100;
        return `${hundred ? twoDigits(hundred) + " Hundred" : ""}${rest ? " " + twoDigits(rest) : ""}`.trim();
    }

    function numberToIndianWords(value) {
        let amount = Math.floor(Number(value || 0));
        const parts = [
            { value: 10000000, label: "Crore" },
            { value: 100000, label: "Lakh" },
            { value: 1000, label: "Thousand" },
            { value: 100, label: "Hundred" }
        ];
        const words = [];

        if (!amount) {
            return "Zero Rupees";
        }

        parts.forEach(part => {
            if (amount >= part.value) {
                const chunk = Math.floor(amount / part.value);
                words.push((part.value === 100 ? twoDigits(chunk) : twoDigits(chunk)) + " " + part.label);
                amount %= part.value;
            }
        });

        if (amount > 0) {
            words.push(amount < 100 ? twoDigits(amount) : threeDigits(amount));
        }

        return words.join(" ").trim() + " Rupees";
    }

    function currentAreaValue() {
        const category = selectedCategory();
        const keys = category === "land"
            ? ["plot_area"]
            : ["builtup_area", "carpet_area", "super_builtup_area", "plot_area"];

        for (const key of keys) {
            const input = document.querySelector(`[name="${key}"]`);
            const value = Number(input?.value || 0);
            if (!input?.disabled && value > 0) {
                return { value, source: key.replace(/_/g, " ") };
            }
        }

        return null;
    }

    function updatePriceSummary() {
        const summary = document.querySelector("[data-price-summary]");
        const main = document.querySelector("[data-price-words-main]");
        const unit = document.querySelector("[data-price-unit]");
        const mode = selectedListingMode();
        const amountInput = mode === "sell" ? byId("expected_price") : byId("rent");
        const amount = Number(amountInput?.value || 0);
        const area = currentAreaValue();
        const areaUnit = byId("area_unit")?.value || "sq.ft";
        const label = mode === "sell" ? "Price" : "Rent";

        if (!summary || !main || !unit) {
            return;
        }

        if (mode === "none") {
            main.textContent = "Choose sale or rent to see amount in words.";
            unit.textContent = "Price per unit will appear after listing type, area and amount are entered.";
            return;
        }

        if (!amount) {
            main.textContent = `Enter ${label.toLowerCase()} to see amount in words.`;
            unit.textContent = "Price per unit will appear after area and amount are entered.";
            return;
        }

        main.textContent = `₹${formatIndianNumber(amount)} (${numberToIndianWords(amount)})`;

        if (area && area.value > 0) {
            const perUnit = amount / area.value;
            unit.textContent = `${label} per ${areaUnit}: ₹${formatIndianNumber(perUnit)} using ${area.source}.`;
        } else {
            unit.textContent = "Add area in Property Details to calculate price per unit.";
        }
    }

    function clearStepValidation(form) {
        const panel = form.closest("[data-step-panel]");
        panel?.querySelector("[data-step-errors]")?.remove();
        panel?.querySelectorAll(".is-invalid").forEach(element => element.classList.remove("is-invalid"));
    }

    function validateStep(form) {
        const step = form.dataset.stepForm || activeStep;
        const panel = form.closest("[data-step-panel]");
        const errors = [];
        const addError = (message, element) => {
            errors.push({ message, element });
            element?.classList.add("is-invalid");
        };
        const field = name => form.querySelector(`[name="${name}"]`);
        const hasValue = input => !!input && !input.disabled && String(input.value || "").trim() !== "";

        clearStepValidation(form);

        if (step === "basic") {
            const listing = form.querySelector("input[name='listing_type_id']:checked");
            const category = form.querySelector("input[name='property_category']:checked:not(:disabled)");
            const group = field("property_group");
            const type = field("property_type_id");
            const postedBy = field("posted_by");
            const title = field("title");
            const availableFrom = field("available_from");

            if (!listing) addError("Select whether the property is for sale, rent/lease, or PG.", form.querySelector(".post-choice-grid"));
            if (!category) addError("Select a property category.", form.querySelectorAll(".post-choice-grid")[1]);
            if (!hasValue(group)) addError("Select a property group.", form.querySelector("[data-property-type-flow]"));
            if (!hasValue(type)) addError("Select the exact property subtype.", form.querySelector("[data-property-type-flow]"));
            if (!hasValue(postedBy)) addError("Select who is posting the property.", postedBy);
            if (!hasValue(title)) addError("Enter a property title.", title);
            if (selectedListingMode() === "rent" && !hasValue(availableFrom)) addError("Select the available-from date.", availableFrom);
        } else if (step === "location") {
            [
                ["country_id", "Select a country."],
                ["state_id", "Select a state."],
                ["city_id", "Select a city."],
                ["locality_id", "Select a locality."]
            ].forEach(([name, message]) => {
                const input = field(name);
                if (!hasValue(input)) addError(message, input);
            });
        } else if (step === "profile") {
            const category = selectedCategory();
            const officeProfile = isOfficeProfile();
            const pgProfile = isPgListing();
            const area = currentAreaValue();
            const superBuiltup = field("super_builtup_area");
            const builtup = field("builtup_area");
            const carpet = field("carpet_area");
            const totalFloor = field("total_floor");
            const floorNo = field("floor_no");

            if (!area) {
                const areaInput = category === "land"
                    ? field("plot_area")
                    : (field("super_builtup_area") || field("builtup_area") || field("carpet_area"));
                addError(category === "land" ? "Enter the plot / land area." : "Enter at least one property area.", areaInput);
            }

            if (hasValue(superBuiltup) && hasValue(builtup) && Number(builtup.value) > Number(superBuiltup.value)) {
                addError("Built-up area cannot exceed super built-up area.", builtup);
            }

            if (hasValue(builtup) && hasValue(carpet) && Number(carpet.value) > Number(builtup.value)) {
                addError("Carpet area cannot exceed built-up area.", carpet);
            }

            if (hasValue(totalFloor) && hasValue(floorNo) && Number(floorNo.value) > Number(totalFloor.value)) {
                addError("Floor number cannot exceed total floors.", floorNo);
            }

            const required = officeProfile
                ? [
                    ["office_min_seats", "Enter minimum office seats."],
                    ["office_cabins", "Enter the number of cabins."],
                    ["office_meeting_rooms", "Enter the number of meeting rooms."]
                ]
                : pgProfile
                    ? [
                        ["bedrooms", "Enter the number of bedrooms."],
                        ["bathrooms", "Enter the number of bathrooms."],
                        ["furnishing", "Select furnishing."],
                        ["property_age", "Select property age."],
                        ["pg_room_type", "Select room type."],
                        ["pg_available_for", "Select who the PG is available for."]
                    ]
                : category === "residential"
                ? [
                    ["bedrooms", "Enter the number of bedrooms."],
                    ["bathrooms", "Enter the number of bathrooms."],
                    ["furnishing", "Select furnishing."],
                    ["property_age", "Select property age."],
                    ["facing", "Select property facing."]
                ]
                : category === "commercial"
                    ? [
                        ["property_age", "Select property age."],
                        ["facing", "Select property facing."]
                    ]
                    : [["facing", "Select land facing."]];

            required.forEach(([name, message]) => {
                const input = field(name);
                if (!hasValue(input)) addError(message, input);
            });

            if (officeProfile) {
                const minSeats = field("office_min_seats");
                const maxSeats = field("office_max_seats");
                if (hasValue(minSeats) && hasValue(maxSeats) && Number(maxSeats.value) < Number(minSeats.value)) {
                    addError("Maximum seats cannot be less than minimum seats.", maxSeats);
                }

                const washrooms = form.querySelector("input[name='office_washrooms']:checked")?.value || "not_available";
                if (washrooms === "available") {
                    const privateWashrooms = field("office_private_washrooms");
                    const sharedWashrooms = field("office_shared_washrooms");
                    if (Number(privateWashrooms?.value || 0) + Number(sharedWashrooms?.value || 0) <= 0) {
                        addError("Add private or shared washroom count.", privateWashrooms || sharedWashrooms);
                    }
                }
            }
            if (pgProfile) {
                const totalRooms = field("pg_total_rooms");
                const availableRooms = field("pg_available_rooms");
                if (hasValue(totalRooms) && hasValue(availableRooms) && Number(availableRooms.value) > Number(totalRooms.value)) {
                    addError("Available rooms cannot exceed total rooms.", availableRooms);
                }
            }

            const furnishing = field("furnishing");
            if (!officeProfile && ["semi", "fully"].includes(furnishing?.value || "")) {
                const selectedItems = form.querySelectorAll("[data-furnishing-item]:checked:not(:disabled)");
                if (selectedItems.length === 0) {
                    addError("Select what is included in furnishing.", form.querySelector("[data-furnishing-panel]"));
                } else if (pgProfile && furnishing?.value === "fully" && selectedItems.length < 3) {
                    addError("Select at least three furnishing items for furnished PG.", form.querySelector("[data-furnishing-panel]"));
                }
            }
        } else if (step === "pricing") {
            const mode = selectedListingMode();
            const amount = mode === "sell" ? field("expected_price") : field("rent");
            const amountValue = Number(amount?.value || 0);

            if (!hasValue(amount) || amountValue <= 0) {
                addError(mode === "sell" ? "Enter a valid expected price." : "Enter a valid monthly rent.", amount);
            }

            if (mode === "rent") {
                const depositType = form.querySelector("input[name='security_deposit_type']:checked")?.value || "";

                if (!["fixed", "multiple", "none"].includes(depositType)) {
                    addError("Select the security deposit type.", form.querySelector('[aria-label="Security deposit type"]'));
                } else if (depositType === "fixed") {
                    const depositAmount = field("security_deposit_amount");
                    if (!hasValue(depositAmount) || Number(depositAmount.value) <= 0) {
                        addError("Enter a valid fixed security deposit amount.", depositAmount);
                    }
                } else if (depositType === "multiple") {
                    const depositMonths = field("security_deposit_months");
                    const months = Number(depositMonths?.value || 0);
                    if (!Number.isInteger(months) || months < 1 || months > 30) {
                        addError("Enter security deposit months between 1 and 30.", depositMonths);
                    }
                }

                const lockIn = field("lock_in_months");
                const lockInValue = Number(lockIn?.value || 0);
                if (hasValue(lockIn) && (!Number.isInteger(lockInValue) || lockInValue < 1 || lockInValue > 120)) {
                    addError("Enter a lock-in period between 1 and 120 months.", lockIn);
                }

                const yearlyIncrease = field("annual_rent_increase_percent");
                const yearlyIncreaseValue = Number(yearlyIncrease?.value || 0);
                if (hasValue(yearlyIncrease) && (yearlyIncreaseValue < 0 || yearlyIncreaseValue > 100)) {
                    addError("Yearly rent increase must be between 0 and 100 percent.", yearlyIncrease);
                }
            }

            if (hasValue(field("maintenance")) && !hasValue(field("maintenance_period"))) {
                addError("Select the maintenance period.", field("maintenance_period"));
            }

            if (selectedPostedBy() !== "owner") {
                const brokerageType = form.querySelector("input[name='brokerage_type']:checked")?.value || "";
                if (!["fixed", "percentage", "none"].includes(brokerageType)) {
                    addError("Select the brokerage type.", form.querySelector('[aria-label="Brokerage type"]'));
                } else if (brokerageType !== "none") {
                    const brokerageValue = field("brokerage_value");
                    const value = Number(brokerageValue?.value || 0);
                    if (!hasValue(brokerageValue) || value <= 0) {
                        addError("Enter a valid brokerage value.", brokerageValue);
                    } else if (brokerageType === "percentage" && value > 100) {
                        addError("Brokerage percentage cannot exceed 100.", brokerageValue);
                    }
                }
            }
        } else if (step === "amenities") {
            if (!form.querySelector("input[name='amenity_ids[]']:checked")) {
                addError("Select at least one amenity.", form.querySelector(".post-amenity-grid"));
            }
        } else if (step === "review") {
            const description = field("description");
            if (!hasValue(description)) addError("Add or choose a property description.", description);
        }

        if (errors.length === 0) {
            return true;
        }

        const summary = document.createElement("div");
        summary.className = "post-step-errors";
        summary.dataset.stepErrors = "true";
        summary.innerHTML = `<strong>Complete the required details</strong><ul>${errors.map(error => `<li>${escapeHtml(error.message)}</li>`).join("")}</ul>`;
        const title = panel?.querySelector(".post-panel-title");
        if (title) {
            title.insertAdjacentElement("afterend", summary);
        } else {
            panel?.prepend(summary);
        }

        setStatus("Required details missing", "error");
        const firstTarget = errors[0].element;
        firstTarget?.scrollIntoView({ behavior: "smooth", block: "center" });
        firstTarget?.focus?.();
        return false;
    }

    function syncSmartContext() {
        syncPropertyTypes();
        syncProfileFields();
        syncProfileLimits();
        syncPricingFields();
        updatePriceSummary();
    }

    function renderDescriptionTemplates(templates, options = {}) {
        const list = document.querySelector("[data-description-template-list]");
        const textarea = byId("description");

        if (!list) {
            return;
        }

        if (!Array.isArray(templates) || templates.length === 0) {
            list.innerHTML = '<div class="post-description-empty">Save more property details to generate templates.</div>';
            return;
        }

        list.innerHTML = templates.slice(0, 3).map(template => (
            `<article class="post-description-card">
                <div><strong>${escapeHtml(template.title)}</strong><button type="button" data-description-template-use="${escapeHtml(template.id)}">Use</button></div>
                <p data-description-template-content="${escapeHtml(template.id)}">${escapeHtml(template.content)}</p>
            </article>`
        )).join("");

        if (options.autofill && textarea && textarea.value.trim() === "" && templates[0]?.content) {
            textarea.value = templates[0].content;
        }
    }

    function fetchDescriptionTemplates(options = {}) {
        const list = document.querySelector("[data-description-template-list]");
        const endpoint = config.description_templates_url || "post-property-description-templates";

        if (!list || (descriptionTemplatesLoaded && !options.force)) {
            return Promise.resolve();
        }

        list.innerHTML = '<div class="post-description-empty">Generating smart templates...</div>';

        return fetch(`${endpoint}?draft_id=${encodeURIComponent(wizard.dataset.draftId || "")}`, {
            credentials: "same-origin",
            headers: { "Accept": "application/json" }
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || "Unable to generate templates.");
                }
                descriptionTemplatesLoaded = true;
                renderDescriptionTemplates(payload.templates || [], options);
            })
            .catch(() => {
                list.innerHTML = '<div class="post-description-empty">Description templates are not available right now.</div>';
            });
    }

    function setFieldValue(selector, value) {
        const input = document.querySelector(selector);
        if (input) input.value = value;
    }

    function setMapAddressPreview(value) {
        const preview = document.querySelector("[data-map-address-preview]");
        const mapAddressInput = byId("map_address");

        if (mapAddressInput) mapAddressInput.value = value || "";
        if (preview) preview.textContent = value || "Not selected";
    }

    function normalizeLocationName(value) {
        return String(value || "").toLowerCase().replace(/&/g, " and ").replace(/[^\w\s]/g, " ").replace(/\s+/g, " ").trim();
    }

    function findLocationOptionByName(items, name, extraNames = []) {
        const targetNames = [name].concat(extraNames).map(normalizeLocationName).filter(Boolean);
        let partialMatch = null;

        for (const item of items) {
            const itemName = normalizeLocationName(item.name || "");
            if (targetNames.includes(itemName)) return item;
            if (!partialMatch && targetNames.some(target => itemName.includes(target) || target.includes(itemName))) {
                partialMatch = item;
            }
        }

        return partialMatch;
    }

    function extractAddressComponent(result, typeNames) {
        const components = result?.address_components || [];
        return components.find(component => (component.types || []).some(type => typeNames.includes(type))) || null;
    }

    function applyLocationFromMapResult(result) {
        const countryComponent = extractAddressComponent(result, ["country"]);
        const stateComponent = extractAddressComponent(result, ["administrative_area_level_1"]);
        const cityComponent = extractAddressComponent(result, ["locality", "administrative_area_level_2"]);
        const localityComponent = extractAddressComponent(result, ["sublocality_level_1", "sublocality", "sublocality_level_2", "neighborhood"]);
        const postalCodeComponent = extractAddressComponent(result, ["postal_code"]);
        const country = findLocationOptionByName(config.countries || [], countryComponent?.long_name || "", countryComponent ? [countryComponent.short_name] : []);
        const stateOptions = states.filter(item => !country || Number(item.country_id) === Number(country.id));
        const state = findLocationOptionByName(stateOptions, stateComponent?.long_name || "", stateComponent ? [stateComponent.short_name] : []);
        const cityOptions = cities.filter(item => !state || Number(item.state_id) === Number(state.id));
        const city = findLocationOptionByName(cityOptions, cityComponent?.long_name || "", cityComponent ? [cityComponent.short_name] : []);
        const localityOptions = localities.filter(item => !city || Number(item.city_id) === Number(city.id));
        const locality = findLocationOptionByName(localityOptions, localityComponent?.long_name || "", localityComponent ? [localityComponent.short_name] : []);

        syncLocationSelects({
            country_id: country ? country.id : 0,
            state_id: state ? state.id : 0,
            city_id: city ? city.id : 0,
            locality_id: locality ? locality.id : 0
        });

        if (postalCodeComponent) {
            setFieldValue("#pincode", postalCodeComponent.long_name || "");
        }

        const locationForm = document.querySelector('[data-step-form="location"]');
        if (locationForm) {
            scheduleSave(locationForm);
        }
    }

    function loadGoogleMapsApi(apiKey) {
        if (!apiKey) {
            return Promise.reject(new Error("Google Maps API key is missing."));
        }

        if (window.google && window.google.maps) {
            return Promise.resolve(window.google.maps);
        }

        if (googleMapsLoader) {
            return googleMapsLoader;
        }

        googleMapsLoader = new Promise((resolve, reject) => {
            const callbackName = "__gharsquarePublicMapInit";
            const script = document.createElement("script");

            window[callbackName] = function () {
                resolve(window.google.maps);
                delete window[callbackName];
            };

            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&loading=async&callback=${callbackName}`;
            script.async = true;
            script.defer = true;
            script.dataset.googleMapsLoader = "true";
            script.onerror = function () {
                reject(new Error("Unable to load Google Maps."));
                delete window[callbackName];
            };
            document.head.appendChild(script);
        });

        return googleMapsLoader;
    }

    function initLocationMap() {
        const googleMaps = config.google_maps || {};
        const mapElement = byId("location_map");

        if (!mapElement || !googleMaps.enabled) {
            return;
        }

        loadGoogleMapsApi(googleMaps.api_key)
            .then(maps => {
                const locationData = config.location || {};
                const latitudeInput = byId("latitude");
                const longitudeInput = byId("longitude");
                const addressInput = byId("address_line");
                const searchInput = byId("map_search");
                const searchButton = byId("map_search_button");
                const useMapAddressButton = byId("use_map_address");
                const defaultCenter = {
                    lat: parseFloat(locationData.latitude || latitudeInput?.value || "23.3441") || 23.3441,
                    lng: parseFloat(locationData.longitude || longitudeInput?.value || "85.3096") || 85.3096
                };
                const map = new maps.Map(mapElement, {
                    center: defaultCenter,
                    zoom: locationData.latitude && locationData.longitude ? 16 : 11,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false
                });
                const marker = new maps.Marker({
                    map,
                    position: defaultCenter,
                    draggable: true,
                    animation: maps.Animation.DROP
                });
                const geocoder = new maps.Geocoder();

                function fillLatLng(latLng) {
                    const lat = typeof latLng.lat === "function" ? latLng.lat() : latLng.lat;
                    const lng = typeof latLng.lng === "function" ? latLng.lng() : latLng.lng;
                    setFieldValue("#latitude", Number(lat).toFixed(7));
                    setFieldValue("#longitude", Number(lng).toFixed(7));
                }

                function applyAddress(address) {
                    const formatted = address || "";
                    setMapAddressPreview(formatted);
                    if (searchInput && formatted !== "") searchInput.value = formatted;
                    if (addressInput && addressInput.value.trim() === "" && formatted !== "") addressInput.value = formatted;
                }

                function moveMarker(latLng, shouldReverseGeocode) {
                    marker.setPosition(latLng);
                    map.panTo(latLng);
                    fillLatLng(latLng);
                    if (shouldReverseGeocode) reverseGeocode(latLng);
                    const locationForm = document.querySelector('[data-step-form="location"]');
                    if (locationForm) scheduleSave(locationForm);
                }

                function applyGeocodeResult(result, fallbackAddress) {
                    if (!result) {
                        applyAddress(fallbackAddress || "");
                        return;
                    }
                    const geometryLocation = result.geometry?.location || null;
                    if (geometryLocation) moveMarker(geometryLocation, false);
                    applyAddress(result.formatted_address || fallbackAddress || "");
                    applyLocationFromMapResult(result);
                }

                function reverseGeocode(latLng) {
                    geocoder.geocode({ location: latLng }, (results, status) => {
                        if (status === "OK" && results && results.length > 0) {
                            const bestResult = results.find(item => {
                                const types = item.types || [];
                                return types.includes("street_address") || types.includes("premise") || types.includes("route") || types.includes("plus_code");
                            }) || results[0];
                            applyAddress(bestResult.formatted_address || "");
                            applyLocationFromMapResult(bestResult);
                        } else {
                            const lat = typeof latLng.lat === "function" ? latLng.lat() : latLng.lat;
                            const lng = typeof latLng.lng === "function" ? latLng.lng() : latLng.lng;
                            applyAddress(`Pinned at ${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`);
                        }
                    });
                }

                function runAddressSearch() {
                    const query = searchInput?.value.trim() || "";
                    if (query === "") return;
                    geocoder.geocode({ address: query }, (results, status) => {
                        if (status === "OK" && results && results[0]) {
                            map.setZoom(16);
                            applyGeocodeResult(results[0], query);
                        } else {
                            showNotice({
                                icon: "error",
                                title: "Address not found",
                                text: "Try a more specific location or move the pin on the map.",
                                timer: 1400,
                                showConfirmButton: false
                            });
                        }
                    });
                }

                map.addListener("click", event => {
                    if (event.latLng) moveMarker(event.latLng, true);
                });
                marker.addListener("dragend", event => {
                    if (event.latLng) moveMarker(event.latLng, true);
                });

                if (searchInput && maps.places && typeof maps.places.Autocomplete === "function") {
                    const autocomplete = new maps.places.Autocomplete(searchInput, {
                        fields: ["address_components", "formatted_address", "geometry", "name"],
                        componentRestrictions: { country: ["in"] }
                    });
                    autocomplete.bindTo("bounds", map);
                    autocomplete.addListener("place_changed", () => {
                        const place = autocomplete.getPlace();
                        if (!place?.geometry?.location) {
                            runAddressSearch();
                            return;
                        }
                        map.setZoom(17);
                        applyGeocodeResult({
                            formatted_address: place.formatted_address || place.name || searchInput.value.trim(),
                            address_components: place.address_components || [],
                            geometry: { location: place.geometry.location }
                        }, searchInput.value.trim());
                    });
                }

                if (searchButton) searchButton.addEventListener("click", runAddressSearch);
                if (searchInput) {
                    searchInput.addEventListener("keydown", event => {
                        if (event.key === "Enter") {
                            event.preventDefault();
                            runAddressSearch();
                        }
                    });
                }
                if (useMapAddressButton && addressInput) {
                    useMapAddressButton.addEventListener("click", () => {
                        const pickedAddress = byId("map_address")?.value.trim() || "";
                        if (pickedAddress !== "") {
                            addressInput.value = pickedAddress;
                            const form = document.querySelector('[data-step-form="location"]');
                            if (form) scheduleSave(form);
                        }
                    });
                }

                if (locationData.map_address) {
                    applyAddress(locationData.map_address);
                } else {
                    fillLatLng(defaultCenter);
                }
            })
            .catch(() => setMapAddressPreview(""));
    }

    function updateMediaFromPayload(payload) {
        if (payload.grid_html && mediaGrid) {
            mediaGrid.innerHTML = payload.grid_html;
        }
        if (imageCount && typeof payload.image_count !== "undefined") {
            imageCount.textContent = String(payload.image_count);
        }
        updateProgress(payload.progress || null);
        setStatus("Media saved", "saved");
    }

    function validateVideoFiles(files) {
        const maxBytes = 20 * 1024 * 1024;
        const maxSeconds = 60;
        return Promise.all(Array.from(files || []).map(file => new Promise((resolve, reject) => {
            if (file.size > maxBytes) {
                reject(new Error(`${file.name} is larger than 20 MB.`));
                return;
            }
            const video = document.createElement("video");
            video.preload = "metadata";
            video.onloadedmetadata = function () {
                URL.revokeObjectURL(video.src);
                if (video.duration > maxSeconds) {
                    reject(new Error(`${file.name} must be 1 minute or shorter.`));
                    return;
                }
                resolve(true);
            };
            video.onerror = function () {
                URL.revokeObjectURL(video.src);
                reject(new Error(`Unable to read video duration for ${file.name}.`));
            };
            video.src = URL.createObjectURL(file);
        })));
    }

    function setMediaUploadState(form, active, percent = 0, message = "Uploading...") {
        let indicator = form.querySelector("[data-media-upload-progress]");

        if (!indicator) {
            indicator = document.createElement("div");
            indicator.className = "post-media-upload-progress";
            indicator.dataset.mediaUploadProgress = "";
            indicator.innerHTML = `
                <span class="post-media-upload-spinner" aria-hidden="true"></span>
                <div>
                    <strong data-media-upload-message>Uploading...</strong>
                    <div class="post-media-upload-track"><span data-media-upload-bar></span></div>
                    <small data-media-upload-percent>0%</small>
                </div>`;
            form.appendChild(indicator);
        }

        form.classList.toggle("is-uploading", active);
        indicator.hidden = !active;
        const normalizedPercent = Math.max(0, Math.min(100, Math.round(percent)));
        indicator.querySelector("[data-media-upload-message]").textContent = message;
        indicator.querySelector("[data-media-upload-bar]").style.width = `${normalizedPercent}%`;
        indicator.querySelector("[data-media-upload-percent]").textContent = `${normalizedPercent}%`;
        form.querySelectorAll("input, button").forEach(control => {
            control.disabled = active;
        });
    }

    function uploadMediaRequest(url, data, onProgress) {
        return new Promise((resolve, reject) => {
            const request = new XMLHttpRequest();
            request.open("POST", url);
            request.withCredentials = true;
            request.setRequestHeader("Accept", "application/json");
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            request.upload.addEventListener("progress", event => {
                if (event.lengthComputable) {
                    onProgress((event.loaded / event.total) * 100);
                }
            });
            request.addEventListener("load", () => {
                try {
                    const payload = JSON.parse(request.responseText || "{}");
                    if (payload.login_required && payload.login_url) {
                        resolve(payload);
                        return;
                    }
                    if (request.status < 200 || request.status >= 300 || !payload.success) {
                        reject(new Error(payload.message || "Unable to upload media."));
                        return;
                    }
                    resolve(payload);
                } catch (error) {
                    reject(new Error("Unexpected server response. Please refresh and try again."));
                }
            });
            request.addEventListener("error", () => reject(new Error("Upload interrupted. Check your connection and try again.")));
            request.addEventListener("abort", () => reject(new Error("Upload cancelled.")));
            request.send(data);
        });
    }

    function postMediaForm(form) {
        if (form.dataset.uploading === "true") {
            return Promise.resolve();
        }

        const data = new FormData(form);
        const uploadKind = form.dataset.uploadKind || "";
        form.dataset.uploading = "true";
        setMediaUploadState(form, true, 0, uploadKind === "youtube" ? "Adding video..." : "Preparing files...");
        setStatus("Uploading media...", "saving");

        return uploadMediaRequest(config.media_url || "post-property-media", data, percent => {
            const message = percent >= 100
                ? (uploadKind === "image" ? "Compressing and cropping images..." : "Processing media...")
                : "Uploading media...";
            setMediaUploadState(form, true, percent, message);
        })
            .then(payload => {
                if (payload.login_required && payload.login_url) {
                    window.location.href = payload.login_url;
                    return payload;
                }
                if (!payload.success) {
                    throw new Error(payload.message || "Unable to upload media.");
                }
                updateMediaFromPayload(payload);
                form.reset();
                showNotice({
                    icon: "success",
                    title: "Media saved",
                    text: payload.message || "Media uploaded successfully.",
                    timer: 1000,
                    showConfirmButton: false
                });
                return payload;
            })
            .catch(error => {
                setStatus(error.message || "Media failed", "error");
                showNotice({
                    icon: "error",
                    title: "Media not saved",
                    text: error.message || "Please try again."
                });
                throw error;
            })
            .finally(() => {
                delete form.dataset.uploading;
                setMediaUploadState(form, false);
            });
    }

    function handleMediaForm(form) {
        const videoInput = form.querySelector('input[type="file"][accept^="video"]');

        if (videoInput && videoInput.files && videoInput.files.length > 0) {
            return validateVideoFiles(videoInput.files).then(() => postMediaForm(form));
        }

        return postMediaForm(form);
    }

    function deleteMedia(mediaId) {
        const data = new FormData();
        const csrf = document.querySelector('input[name="csrf_token"]')?.value || "";
        data.set("csrf_token", csrf);
        data.set("draft_id", wizard.dataset.draftId || "");
        data.set("action", "delete");
        data.set("media_id", mediaId);
        setStatus("Removing media...", "saving");

        return fetch(config.media_url || "post-property-media", {
            method: "POST",
            credentials: "same-origin",
            body: data
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || "Unable to remove media.");
                }
                updateMediaFromPayload(payload);
                return payload;
            })
            .catch(error => {
                setStatus(error.message || "Remove failed", "error");
                showNotice({
                    icon: "error",
                    title: "Media not removed",
                    text: error.message || "Please try again."
                });
            });
    }

    function updateMediaMetadata(mediaId, action, title = "") {
        const data = new FormData();
        const csrf = document.querySelector('input[name="csrf_token"]')?.value || "";
        data.set("csrf_token", csrf);
        data.set("draft_id", wizard.dataset.draftId || "");
        data.set("action", action);
        data.set("media_id", mediaId);
        if (action === "set_photo_type") {
            data.set("title", title);
        }
        setStatus(action === "set_cover" ? "Updating cover..." : "Updating photo type...", "saving");

        return fetch(config.media_url || "post-property-media", {
            method: "POST",
            credentials: "same-origin",
            body: data
        })
            .then(response => response.json())
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || "Unable to update media.");
                }
                updateMediaFromPayload(payload);
                showNotice({
                    icon: "success",
                    title: payload.message || "Media updated",
                    timer: 900,
                    showConfirmButton: false
                });
            })
            .catch(error => {
                setStatus(error.message || "Media update failed", "error");
                showNotice({
                    icon: "error",
                    title: "Media not updated",
                    text: error.message || "Please try again."
                });
            });
    }

    window.postPropertyLocation = {
        applySelection(selection) {
            syncLocationSelects(selection || {});
        }
    };

    const exitLink = document.querySelector("[data-property-exit]");
    if (exitLink) {
        exitLink.addEventListener("click", event => {
            event.preventDefault();
            const destination = exitLink.href;
            const leaveWizard = () => {
                const activeForm = document.querySelector(`[data-step-form="${activeStep}"]`);
                if (!activeForm) {
                    window.location.href = destination;
                    return;
                }
                saveForm(activeForm).catch(() => {}).finally(() => {
                    window.location.href = destination;
                });
            };

            if (window.Swal) {
                window.Swal.fire({
                    icon: "question",
                    title: "Exit property listing?",
                    text: "Your current details will be saved as a draft before you leave.",
                    showCancelButton: true,
                    confirmButtonText: "Save & Exit",
                    cancelButtonText: "Continue Editing",
                    confirmButtonColor: "#0f766e"
                }).then(result => {
                    if (result.isConfirmed) {
                        leaveWizard();
                    }
                });
                return;
            }

            if (window.confirm("Exit property listing? Your current details will be saved as a draft.")) {
                leaveWizard();
            }
        });
    }

    document.querySelectorAll("[data-step-target]").forEach(button => {
        button.addEventListener("click", () => {
            const target = button.dataset.stepTarget;
            const currentIndex = stepOrder.indexOf(activeStep);
            const targetIndex = stepOrder.indexOf(target);

            if (targetIndex <= currentIndex) {
                showStep(target);
                return;
            }

            if (targetIndex > currentIndex + 1) {
                showNotice({
                    icon: "info",
                    title: "Complete steps in order",
                    text: "Finish the current step before opening a later step."
                });
                return;
            }

            const form = document.querySelector(`[data-step-form="${activeStep}"]`);
            if (!form || !validateStep(form)) {
                return;
            }

            if (activeStep === "media" && target === "review") {
                confirmMissingMediaBeforeReview()
                    .then(confirmed => {
                        if (!confirmed) return;
                        saveForm(form, { alert: true, validate: true }).then(() => showStep(target)).catch(() => {});
                    })
                    .catch(() => {});
                return;
            }

            saveForm(form, { alert: true, validate: true }).then(() => showStep(target)).catch(() => {});
        });
    });

    document.querySelectorAll("[data-next-step], [data-prev-step]").forEach(button => {
        button.addEventListener("click", () => {
            const target = button.dataset.nextStep || button.dataset.prevStep;
            const form = document.querySelector(`[data-step-form="${activeStep}"]`);
            const shouldSave = button.hasAttribute("data-next-step") && form;

            if (!shouldSave) {
                showStep(target);
                return;
            }

            if (!validateStep(form)) {
                return;
            }

            if (activeStep === "media" && target === "review") {
                confirmMissingMediaBeforeReview()
                    .then(confirmed => {
                        if (!confirmed) return;
                        saveForm(form, { alert: true, validate: true }).then(() => showStep(target)).catch(() => {});
                    })
                    .catch(() => {});
                return;
            }

            saveForm(form, { alert: true, validate: true }).then(() => showStep(target)).catch(() => {});
        });
    });

    document.querySelectorAll(".post-step-form").forEach(form => {
        form.noValidate = true;

        form.addEventListener("submit", event => {
            event.preventDefault();
            const submitFinal = form.querySelector("[name='action']")?.value === "submit";

            if (submitFinal && !validateStep(form)) {
                return;
            }

            saveForm(form, { submit: submitFinal, alert: true, validate: submitFinal }).catch(() => {});
        });

        form.addEventListener("input", event => {
            if (event.target.matches("textarea, input, select") && event.target.type !== "file") {
                syncSmartContext();
                scheduleSave(form);
            }
        });

        form.addEventListener("change", event => {
            if (event.target.matches("input, select, textarea") && event.target.type !== "file") {
                if (event.target.name === "property_category") {
                    syncPropertyTypes();
                }
                if (event.target.name === "listing_type_id") {
                    syncPropertyTypes();
                }
                if (event.target.name === "property_type_id") {
                    syncCategoryFromType();
                }
                if (event.target.name === "furnishing" || event.target.matches("[data-furnishing-item]")) {
                    syncFurnishingItems();
                }
                if (event.target.name === "office_washrooms") {
                    syncOfficeWashroomFields();
                }
                if (event.target.matches("[data-country-select], [data-state-select], [data-city-select]")) {
                    syncLocationSelects();
                }
                syncSmartContext();
                scheduleSave(form);
            }
        });
    });

    document.querySelectorAll(".post-media-upload-form").forEach(form => {
        form.addEventListener("submit", event => {
            event.preventDefault();
            handleMediaForm(form).catch(() => {});
        });

        form.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener("change", () => {
                if (input.files && input.files.length > 0) {
                    handleMediaForm(form).catch(() => {});
                }
            });
        });
    });

    document.querySelectorAll(".post-media-dropzone").forEach(dropzone => {
        ["dragenter", "dragover"].forEach(eventName => {
            dropzone.addEventListener(eventName, event => {
                event.preventDefault();
                dropzone.classList.add("is-dragover");
            });
        });
        ["dragleave", "drop"].forEach(eventName => {
            dropzone.addEventListener(eventName, event => {
                event.preventDefault();
                dropzone.classList.remove("is-dragover");
            });
        });
        dropzone.addEventListener("drop", event => {
            const input = dropzone.querySelector('input[type="file"]');
            const form = dropzone.closest("form");
            if (!input || !form || !event.dataTransfer?.files?.length) return;
            const transfer = new DataTransfer();
            Array.from(event.dataTransfer.files).forEach(file => transfer.items.add(file));
            input.files = transfer.files;
            handleMediaForm(form).catch(() => {});
        });
    });

    document.addEventListener("click", event => {
        const refreshButton = event.target.closest("[data-description-refresh]");
        const useButton = event.target.closest("[data-description-template-use]");
        const deleteButton = event.target.closest("[data-public-media-delete]");
        const coverButton = event.target.closest("[data-public-media-cover]");
        const groupButton = event.target.closest("[data-property-group]");
        const subtypeButton = event.target.closest("[data-property-subtype]");

        if (groupButton) {
            event.preventDefault();
            choosePropertyGroup(groupButton.dataset.propertyGroup || "");
            const form = groupButton.closest("form");
            if (form) scheduleSave(form);
        }

        if (subtypeButton) {
            event.preventDefault();
            choosePropertySubtype(subtypeButton.dataset.propertySubtype || "");
            const form = subtypeButton.closest("form");
            if (form) scheduleSave(form);
        }

        if (refreshButton) {
            event.preventDefault();
            descriptionTemplatesLoaded = false;
            fetchDescriptionTemplates({ force: true, autofill: false });
        }

        if (useButton) {
            event.preventDefault();
            const templateId = useButton.dataset.descriptionTemplateUse || "";
            const content = document.querySelector(`[data-description-template-content="${templateId}"]`);
            const textarea = byId("description");
            if (content && textarea) {
                textarea.value = content.textContent.trim();
                const form = textarea.closest("form");
                if (form) scheduleSave(form);
                showNotice({
                    icon: "success",
                    title: "Template inserted",
                    timer: 900,
                    showConfirmButton: false
                });
            }
        }

        if (deleteButton) {
            event.preventDefault();
            deleteMedia(deleteButton.dataset.publicMediaDelete || "");
        }

        if (coverButton) {
            event.preventDefault();
            updateMediaMetadata(coverButton.dataset.publicMediaCover || "", "set_cover");
        }
    });

    document.addEventListener("change", event => {
        const photoType = event.target.closest("[data-public-media-title]");
        if (photoType) {
            updateMediaMetadata(photoType.dataset.publicMediaTitle || "", "set_photo_type", photoType.value || "other");
        }
    });

    syncPropertyTypes();
    syncCategoryFromType();
    syncLocationSelects(config.selected || {});
    syncSmartContext();
    initLocationMap();
    fetchDescriptionTemplates({ autofill: true });
    showStep(activeStep);
})();

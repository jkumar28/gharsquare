const typeLabels = {
    buy: "Buy",
    rent: "Rent",
    commercial: "Commercial",
    pg: "PG/co-living",
    plots: "Plots"
};

const typeTitles = {
    buy: "Properties to buy",
    rent: "Rental properties",
    commercial: "Commercial spaces",
    pg: "PG and co-living spaces",
    plots: "Plots and land"
};

const listingImages = [
    "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1497366754035-f200968a6e72?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?q=80&w=900&auto=format&fit=crop"
];

const listings = [
    {
        id: "buy-1",
        type: "buy",
        title: "Luxury 3BHK Apartment",
        priceText: "\u20b968 Lac",
        price: 6800000,
        budget: "mid",
        location: "Bariatu",
        bhk: 3,
        area: 1450,
        status: "Ready",
        category: "Apartment",
        verified: true,
        ready: true,
        agent: "Rahul Singh",
        posted: "Today",
        image: listingImages[0],
        amenities: ["Lift", "Parking", "Security"],
        pin: { x: 30, y: 42 }
    },
    {
        id: "buy-2",
        type: "buy",
        title: "Premium Villa With Garden",
        priceText: "\u20b91.25 Cr",
        price: 12500000,
        budget: "premium",
        location: "Morabadi",
        bhk: 4,
        area: 2400,
        status: "Luxury",
        category: "Villa",
        verified: true,
        ready: true,
        agent: "Neha Verma",
        posted: "1 day ago",
        image: listingImages[1],
        amenities: ["Garden", "Servant Room", "Parking"],
        pin: { x: 56, y: 32 }
    },
    {
        id: "buy-3",
        type: "buy",
        title: "Modern 2BHK Flat",
        priceText: "\u20b942 Lac",
        price: 4200000,
        budget: "mid",
        location: "Argora",
        bhk: 2,
        area: 980,
        status: "Verified",
        category: "Apartment",
        verified: true,
        ready: false,
        agent: "Amit Kumar",
        posted: "2 days ago",
        image: listingImages[2],
        amenities: ["Balcony", "Power Backup"],
        pin: { x: 68, y: 58 }
    },
    {
        id: "buy-4",
        type: "buy",
        title: "Skyline Premium Flat",
        priceText: "\u20b985 Lac",
        price: 8500000,
        budget: "premium",
        location: "Kanke",
        bhk: 3,
        area: 1650,
        status: "Hot",
        category: "Apartment",
        verified: true,
        ready: true,
        agent: "Priya Sharma",
        posted: "Today",
        image: listingImages[0],
        amenities: ["Clubhouse", "Gym", "Lift"],
        pin: { x: 46, y: 70 }
    },
    {
        id: "rent-1",
        type: "rent",
        title: "2BHK Flat For Rent",
        priceText: "\u20b912,000/mo",
        price: 12000,
        budget: "low",
        location: "Lalpur",
        bhk: 2,
        area: 900,
        status: "Family",
        category: "Flat",
        verified: true,
        ready: true,
        agent: "Priya Sharma",
        posted: "Today",
        image: listingImages[1],
        amenities: ["Water Supply", "Parking"],
        pin: { x: 34, y: 64 }
    },
    {
        id: "rent-2",
        type: "rent",
        title: "Semi Furnished 3BHK",
        priceText: "\u20b918,000/mo",
        price: 18000,
        budget: "mid",
        location: "Kanke Road",
        bhk: 3,
        area: 1350,
        status: "Popular",
        category: "Flat",
        verified: true,
        ready: true,
        agent: "Rahul Singh",
        posted: "1 day ago",
        image: listingImages[2],
        amenities: ["Furnished", "Lift", "Security"],
        pin: { x: 58, y: 48 }
    },
    {
        id: "rent-3",
        type: "rent",
        title: "1BHK Independent Flat",
        priceText: "\u20b910,000/mo",
        price: 10000,
        budget: "low",
        location: "Argora",
        bhk: 1,
        area: 650,
        status: "New",
        category: "Flat",
        verified: false,
        ready: true,
        agent: "Amit Kumar",
        posted: "3 days ago",
        image: listingImages[0],
        amenities: ["Independent Entry", "Water Supply"],
        pin: { x: 70, y: 54 }
    },
    {
        id: "commercial-1",
        type: "commercial",
        title: "Main Road Shop",
        priceText: "\u20b945 Lac",
        price: 4500000,
        budget: "mid",
        location: "Main Road",
        bhk: 0,
        area: 420,
        status: "Prime",
        category: "Shop",
        verified: true,
        ready: true,
        agent: "Neha Verma",
        posted: "Today",
        image: listingImages[3],
        amenities: ["Main Road", "High Footfall"],
        pin: { x: 44, y: 52 }
    },
    {
        id: "commercial-2",
        type: "commercial",
        title: "Corporate Office Space",
        priceText: "\u20b935,000/mo",
        price: 35000,
        budget: "premium",
        location: "Lalpur",
        bhk: 0,
        area: 1100,
        status: "Rent",
        category: "Office",
        verified: true,
        ready: true,
        agent: "Rahul Singh",
        posted: "2 days ago",
        image: listingImages[3],
        amenities: ["Lift", "Reception", "Parking"],
        pin: { x: 38, y: 68 }
    },
    {
        id: "pg-1",
        type: "pg",
        title: "Boys PG With Food",
        priceText: "\u20b96,500/mo",
        price: 6500,
        budget: "low",
        location: "Lalpur",
        bhk: 0,
        area: 220,
        status: "Budget",
        category: "PG",
        verified: true,
        ready: true,
        agent: "Priya Sharma",
        posted: "Today",
        image: listingImages[5],
        amenities: ["Food", "WiFi", "Laundry"],
        pin: { x: 36, y: 66 }
    },
    {
        id: "pg-2",
        type: "pg",
        title: "Premium Co-living",
        priceText: "\u20b911,000/mo",
        price: 11000,
        budget: "mid",
        location: "Morabadi",
        bhk: 0,
        area: 320,
        status: "Premium",
        category: "Co-living",
        verified: true,
        ready: true,
        agent: "Neha Verma",
        posted: "1 day ago",
        image: listingImages[5],
        amenities: ["AC", "WiFi", "Housekeeping"],
        pin: { x: 54, y: 34 }
    },
    {
        id: "plots-1",
        type: "plots",
        title: "Residential Plot",
        priceText: "\u20b918 Lac",
        price: 1800000,
        budget: "low",
        location: "Ring Road",
        bhk: 0,
        area: 1200,
        status: "Hot",
        category: "Plot",
        verified: true,
        ready: false,
        agent: "Amit Kumar",
        posted: "Today",
        image: listingImages[4],
        amenities: ["Road Access", "Mutation Ready"],
        pin: { x: 76, y: 42 }
    },
    {
        id: "plots-2",
        type: "plots",
        title: "Corner Plot",
        priceText: "\u20b932 Lac",
        price: 3200000,
        budget: "mid",
        location: "Kanke",
        bhk: 0,
        area: 2400,
        status: "Prime",
        category: "Plot",
        verified: true,
        ready: false,
        agent: "Amit Kumar",
        posted: "2 days ago",
        image: listingImages[4],
        amenities: ["Corner Plot", "Gated Society"],
        pin: { x: 48, y: 72 }
    }
];

const params = new URLSearchParams(window.location.search);
const state = {
    type: normalizeType(params.get("type")),
    city: params.get("city") || localStorage.getItem("selectedCity") || "Ranchi",
    query: params.get("q") || "",
    budget: "all",
    bhk: "all",
    area: 0,
    verified: false,
    ready: false,
    sort: "recommended",
    view: "grid"
};

const savedListings = new Set();

function normalizeType(type) {
    return typeLabels[type] ? type : "buy";
}

function formatArea(value) {
    return Number(value) > 0 ? `${value} sqft+` : "Any";
}

function showNotice(options) {
    if (window.Swal) {
        Swal.fire(options);
        return;
    }

    alert(`${options.title}\n${options.text || ""}`);
}

function trackActivity(payload) {
    if (window.GharSquareAuth && typeof window.GharSquareAuth.trackActivity === "function") {
        window.GharSquareAuth.trackActivity(payload);
    }
}

function trackListingSearch(source) {
    trackActivity({
        activity_type: state.query ? "search" : "listing_browse",
        search_query: state.query,
        listing_type: state.type,
        city: state.city,
        metadata: { source }
    });
}

function trackPropertyAction(item, activityType, metadata = {}) {
    trackActivity({
        activity_type: activityType,
        entity_type: "property",
        entity_id: item.id,
        search_query: state.query,
        listing_type: item.type,
        city: state.city,
        metadata: Object.assign({
            title: item.title,
            locality: item.location,
            category: item.category
        }, metadata)
    });
}

function propertyPayload(item, source, extra = {}) {
    return Object.assign({
        property_ref: item.id,
        listing_type: item.type,
        title: item.title,
        price_text: item.priceText,
        city: state.city,
        locality: item.location,
        category: item.category,
        image_url: item.image,
        details_url: new URL(getDetailsUrl(item), window.location.href).href,
        source,
        page_url: window.location.href,
        page_title: document.title,
        metadata: {
            agent: item.agent,
            area: item.area,
            bhk: item.bhk,
            status: item.status,
            ready: item.ready,
            verified: item.verified
        }
    }, extra);
}

function handleLoginRequired(data) {
    if (data && data.login_required && data.login_url) {
        window.location.href = data.login_url;
        return true;
    }

    return false;
}

function postJson(path, payload) {
    return fetch(path, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify(payload)
    }).then(response => response.json());
}

function loadSavedListings() {
    fetch("saved-property", {
        credentials: "same-origin",
        cache: "no-store"
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.logged_in) {
                return;
            }

            savedListings.clear();
            (data.saved_ids || []).forEach(id => savedListings.add(String(id)));
            renderResults();
        })
        .catch(() => {});
}

function persistSavedProperty(item, shouldSave, source) {
    postJson("saved-property", propertyPayload(item, source, {
        action: shouldSave ? "save" : "unsave"
    }))
        .then(data => {
            if (handleLoginRequired(data)) {
                return;
            }

            if (!data.success) {
                throw new Error(data.message || "Unable to update saved property.");
            }

            if (data.saved) {
                savedListings.add(item.id);
            } else {
                savedListings.delete(item.id);
            }

            renderResults();
            showNotice({
                icon: "success",
                title: data.saved ? "Property saved" : "Removed from saved",
                text: data.saved ? `${item.title} is now in Saved Properties.` : `${item.title} was removed from Saved Properties.`,
                confirmButtonColor: "#0f766e"
            });
        })
        .catch(() => {
            showNotice({
                icon: "error",
                title: "Could not update saved property",
                text: "Please try again in a moment.",
                confirmButtonColor: "#0f766e"
            });
        });
}

function createPropertyEnquiry(item, source) {
    postJson("property-enquiry", propertyPayload(item, source, {
        message: `Interested in ${item.title} at ${item.location}, ${state.city}.`
    }))
        .then(data => {
            if (handleLoginRequired(data)) {
                return;
            }

            if (!data.success) {
                throw new Error(data.message || "Unable to create enquiry.");
            }

            showEnquiry(item);
        })
        .catch(() => {
            showNotice({
                icon: "error",
                title: "Could not send enquiry",
                text: "Please try again in a moment.",
                confirmButtonColor: "#0f766e"
            });
        });
}

function getListingUrl(type = state.type, query = state.query) {
    const next = new URLSearchParams();
    next.set("type", type);
    next.set("city", state.city);
    if (query) next.set("q", query);
    return `listing?${next.toString()}`;
}

function getDetailsUrl(item) {
    const next = new URLSearchParams();
    next.set("id", item.id);
    next.set("type", item.type);
    next.set("city", state.city);
    return `property-details?${next.toString()}`;
}

function updateUrl() {
    window.history.replaceState(null, "", getListingUrl());
}

function updateHeader() {
    document.getElementById("headerCity").innerText = state.city;
    document.getElementById("listingTitle").innerText = `${typeTitles[state.type]} in ${state.city}`;

    const context = state.query
        ? `Showing matches for "${state.query}" near ${state.city}.`
        : `Verified ${typeLabels[state.type].toLowerCase()} listings near ${state.city}.`;

    document.getElementById("listingSubtitle").innerText = context;
    document.getElementById("listingSearchInput").value = state.query;

    document.querySelectorAll(".listing-tab").forEach(btn => {
        btn.classList.toggle("active", btn.dataset.type === state.type);
    });
}

function syncFilterControls() {
    setValue("budgetFilter", state.budget);
    setValue("mobileBudgetFilter", state.budget);
    setValue("bhkFilter", state.bhk);
    setValue("mobileBhkFilter", state.bhk);
    setValue("areaFilter", state.area);
    setValue("mobileAreaFilter", state.area);
    setValue("sortSelect", state.sort);
    setChecked("verifiedFilter", state.verified);
    setChecked("mobileVerifiedFilter", state.verified);
    setChecked("readyFilter", state.ready);
    setChecked("mobileReadyFilter", state.ready);
    setAreaLabels();
}

function setValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
}

function setChecked(id, value) {
    const el = document.getElementById(id);
    if (el) el.checked = value;
}

function setAreaLabels() {
    const label = formatArea(state.area);
    const areaValue = document.getElementById("areaValue");
    const mobileAreaValue = document.getElementById("mobileAreaValue");
    if (areaValue) areaValue.innerText = label;
    if (mobileAreaValue) mobileAreaValue.innerText = label;
}

function filterListings() {
    const query = state.query.trim().toLowerCase();

    let results = listings.filter(item => item.type === state.type);

    if (query) {
        results = results.filter(item => {
            const text = [
                item.title,
                item.location,
                item.category,
                item.status,
                item.agent,
                ...item.amenities
            ].join(" ").toLowerCase();
            return text.includes(query);
        });
    }

    if (state.budget !== "all") {
        results = results.filter(item => item.budget === state.budget);
    }

    if (state.bhk !== "all") {
        const requested = Number(state.bhk);
        results = results.filter(item => requested === 4 ? item.bhk >= 4 : item.bhk === requested);
    }

    if (Number(state.area) > 0) {
        results = results.filter(item => item.area >= Number(state.area));
    }

    if (state.verified) {
        results = results.filter(item => item.verified);
    }

    if (state.ready) {
        results = results.filter(item => item.ready);
    }

    return sortListings(results);
}

function sortListings(results) {
    const next = [...results];

    if (state.sort === "priceLow") {
        next.sort((a, b) => a.price - b.price);
    }

    if (state.sort === "priceHigh") {
        next.sort((a, b) => b.price - a.price);
    }

    if (state.sort === "areaHigh") {
        next.sort((a, b) => b.area - a.area);
    }

    return next;
}

function renderResults() {
    const results = filterListings();
    const grid = document.getElementById("resultsGrid");
    const empty = document.getElementById("emptyResults");

    grid.classList.toggle("list-view", state.view === "list");
    grid.innerHTML = results.map(item => renderCard(item)).join("");

    empty.style.display = results.length ? "none" : "block";
    grid.style.display = results.length ? "grid" : "none";

    document.getElementById("resultsTitle").innerText = `${typeLabels[state.type]} listings`;
    document.getElementById("resultsCount").innerText = `${results.length} listing${results.length === 1 ? "" : "s"} found`;

    renderActiveFilters();
    renderMap(results);
    updateHeader();
    syncFilterControls();
    updateUrl();
}

function renderCard(item) {
    const specs = [
        item.bhk ? `${item.bhk} BHK` : item.category,
        `${item.area} sqft`,
        item.ready ? "Ready" : item.status
    ];
    const saved = savedListings.has(item.id);

    return `
        <article class="result-card">
            <div class="result-image">
                <img src="${item.image}" alt="${item.title}">
                <span class="result-badge">${item.verified ? "Verified" : item.status}</span>
            </div>
            <div class="result-body">
                <div class="result-top">
                    <div class="result-price">${item.priceText}</div>
                    <button type="button" class="save-btn ${saved ? "active" : ""}" data-action="save" data-id="${item.id}" aria-label="Save listing">
                        <i class="bi ${saved ? "bi-heart-fill" : "bi-heart"}"></i>
                    </button>
                </div>
                <h3>${item.title}</h3>
                <p class="result-location"><i class="bi bi-geo-alt"></i> ${item.location}, ${state.city}</p>
                <div class="result-specs">
                    ${specs.map(spec => `<span>${spec}</span>`).join("")}
                </div>
                <div class="result-meta">
                    <span>${item.agent}</span>
                    <span>${item.posted}</span>
                </div>
                <div class="result-actions">
                    <a class="details-btn" data-id="${item.id}" href="${getDetailsUrl(item)}">Details</a>
                    <button type="button" class="enquire-btn" data-action="enquire" data-id="${item.id}">Enquire</button>
                    <a class="call-btn" data-id="${item.id}" href="tel:+917004757477"><i class="bi bi-telephone"></i> Call</a>
                </div>
            </div>
        </article>
    `;
}

function renderActiveFilters() {
    const chips = [];

    chips.push(typeLabels[state.type]);
    if (state.query) chips.push(state.query);
    if (state.budget !== "all") chips.push(`${capitalize(state.budget)} budget`);
    if (state.bhk !== "all") chips.push(state.bhk === "4" ? "4+ BHK" : `${state.bhk} BHK`);
    if (Number(state.area) > 0) chips.push(formatArea(state.area));
    if (state.verified) chips.push("Verified");
    if (state.ready) chips.push("Ready");

    document.getElementById("activeFilters").innerHTML = chips
        .map(chip => `<span class="filter-pill">${chip}</span>`)
        .join("");
}

function renderMap(results) {
    const mapCanvas = document.getElementById("mapCanvas");
    const localityList = document.getElementById("localityList");
    const pins = results.slice(0, 8).map(item => `
        <button type="button" class="map-pin" style="left:${item.pin.x}%;top:${item.pin.y}%;" data-locality="${item.location}">
            ${item.location}
        </button>
    `).join("");

    mapCanvas.innerHTML = pins;

    const localityCounts = results.reduce((acc, item) => {
        acc[item.location] = (acc[item.location] || 0) + 1;
        return acc;
    }, {});

    localityList.innerHTML = Object.entries(localityCounts)
        .map(([locality, count]) => `
            <div class="locality-item">
                <span>${locality}</span>
                <span>${count} listing${count === 1 ? "" : "s"}</span>
            </div>
        `)
        .join("");
}

function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function resetFilters() {
    state.budget = "all";
    state.bhk = "all";
    state.area = 0;
    state.verified = false;
    state.ready = false;
    state.sort = "recommended";
    renderResults();
    trackActivity({
        activity_type: "filters_reset",
        search_query: state.query,
        listing_type: state.type,
        city: state.city
    });
}

function findListing(id) {
    return listings.find(item => item.id === id);
}

function showEnquiry(item) {
    showNotice({
        icon: "success",
        title: "Enquiry started",
        text: `Our team will connect you with ${item.agent} for ${item.title} in ${item.location}, ${state.city}.`,
        confirmButtonColor: "#0f766e"
    });
}

function createMobileFilters() {
    const slot = document.getElementById("mobileFilterSlot");
    slot.innerHTML = `
        <div class="filter-panel">
            <div class="filter-heading">
                <h2>Filters</h2>
                <button type="button" class="clear-filter-btn" id="mobileClearFilters">Clear</button>
            </div>

            <label class="filter-label" for="mobileBudgetFilter">Budget</label>
            <select id="mobileBudgetFilter" class="filter-control">
                <option value="all">Any budget</option>
                <option value="low">Budget friendly</option>
                <option value="mid">Mid range</option>
                <option value="premium">Premium</option>
            </select>

            <label class="filter-label" for="mobileBhkFilter">Bedrooms</label>
            <select id="mobileBhkFilter" class="filter-control">
                <option value="all">Any BHK</option>
                <option value="1">1 BHK</option>
                <option value="2">2 BHK</option>
                <option value="3">3 BHK</option>
                <option value="4">4+ BHK</option>
            </select>

            <label class="filter-label" for="mobileAreaFilter">Minimum Area</label>
            <div class="range-row">
                <input id="mobileAreaFilter" type="range" min="0" max="3000" value="0" step="250">
                <span id="mobileAreaValue">Any</span>
            </div>

            <label class="check-row">
                <input type="checkbox" id="mobileVerifiedFilter">
                <span>Verified only</span>
            </label>

            <label class="check-row">
                <input type="checkbox" id="mobileReadyFilter">
                <span>Ready to move</span>
            </label>
        </div>
    `;
}

function bindFilter(id, key, eventName = "change", transform = value => value) {
    const el = document.getElementById(id);
    if (!el) return;

    el.addEventListener(eventName, function () {
        state[key] = transform(this.type === "checkbox" ? this.checked : this.value);
        renderResults();
        trackActivity({
            activity_type: "filter_change",
            search_query: state.query,
            listing_type: state.type,
            city: state.city,
            metadata: {
                control: id,
                filter: key,
                value: state[key]
            }
        });
    });
}

function bindEvents() {
    document.querySelectorAll(".listing-tab").forEach(btn => {
        btn.addEventListener("click", function () {
            state.type = this.dataset.type;
            state.query = "";
            renderResults();
            trackActivity({
                activity_type: "listing_type_change",
                listing_type: state.type,
                city: state.city,
                metadata: { source: "listing_tab" }
            });
        });
    });

    document.querySelectorAll(".listing-nav-type").forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            state.type = this.dataset.type;
            state.query = "";
            renderResults();
            trackActivity({
                activity_type: "listing_type_change",
                listing_type: state.type,
                city: state.city,
                metadata: { source: "navigation" }
            });
        });
    });

    document.getElementById("listingSearchBtn").addEventListener("click", function () {
        state.query = document.getElementById("listingSearchInput").value.trim();
        renderResults();
        trackListingSearch("listing_search_button");
    });

    document.getElementById("listingSearchInput").addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            state.query = this.value.trim();
            renderResults();
            trackListingSearch("listing_search_enter");
        }
    });

    bindFilter("budgetFilter", "budget");
    bindFilter("mobileBudgetFilter", "budget");
    bindFilter("bhkFilter", "bhk");
    bindFilter("mobileBhkFilter", "bhk");
    bindFilter("areaFilter", "area", "input", Number);
    bindFilter("mobileAreaFilter", "area", "input", Number);
    bindFilter("verifiedFilter", "verified");
    bindFilter("mobileVerifiedFilter", "verified");
    bindFilter("readyFilter", "ready");
    bindFilter("mobileReadyFilter", "ready");
    bindFilter("sortSelect", "sort");

    document.getElementById("clearFilters").addEventListener("click", resetFilters);
    document.getElementById("mobileClearFilters").addEventListener("click", resetFilters);
    document.getElementById("resetEmptyBtn").addEventListener("click", function () {
        state.query = "";
        document.getElementById("listingSearchInput").value = "";
        resetFilters();
    });

    document.querySelectorAll(".view-mode").forEach(btn => {
        btn.addEventListener("click", function () {
            state.view = this.dataset.view;
            document.querySelectorAll(".view-mode").forEach(item => item.classList.remove("active"));
            this.classList.add("active");
            renderResults();
            trackActivity({
                activity_type: "view_mode_change",
                search_query: state.query,
                listing_type: state.type,
                city: state.city,
                metadata: { view: state.view }
            });
        });
    });

    document.getElementById("resultsGrid").addEventListener("click", function (e) {
        const detailsLink = e.target.closest(".details-btn");
        if (detailsLink) {
            const item = findListing(detailsLink.dataset.id);
            if (item) {
                trackPropertyAction(item, "property_details_open", { source: "listing_card" });
            }
            return;
        }

        const callLink = e.target.closest(".call-btn");
        if (callLink) {
            const item = findListing(callLink.dataset.id);
            if (item) {
                trackPropertyAction(item, "call_click", { source: "listing_card" });
            }
            return;
        }

        const target = e.target.closest("[data-action]");
        if (!target) return;

        const item = findListing(target.dataset.id);
        if (!item) return;

        if (target.dataset.action === "save") {
            persistSavedProperty(item, !savedListings.has(item.id), "listing_card");
        }

        if (target.dataset.action === "enquire") {
            createPropertyEnquiry(item, "listing_card");
        }
    });

    document.getElementById("mapCanvas").addEventListener("click", function (e) {
        const pin = e.target.closest(".map-pin");
        if (!pin) return;

        state.query = pin.dataset.locality;
        renderResults();
        trackActivity({
            activity_type: "map_locality_click",
            search_query: state.query,
            listing_type: state.type,
            city: state.city,
            metadata: { locality: state.query }
        });
    });

    document.getElementById("mobileMapToggle").addEventListener("click", function () {
        document.getElementById("mapPanel").classList.add("open");
        trackActivity({
            activity_type: "map_open",
            search_query: state.query,
            listing_type: state.type,
            city: state.city,
            metadata: { source: "mobile_toggle" }
        });
    });

    document.getElementById("closeMapPanel").addEventListener("click", function () {
        document.getElementById("mapPanel").classList.remove("open");
        trackActivity({
            activity_type: "map_close",
            search_query: state.query,
            listing_type: state.type,
            city: state.city,
            metadata: { source: "mobile_panel" }
        });
    });

    document.querySelectorAll(".city-option").forEach(btn => {
        btn.addEventListener("click", function () {
            state.city = this.innerText.trim();
            localStorage.setItem("selectedCity", state.city);
            renderResults();
            trackActivity({
                activity_type: "city_change",
                search_query: state.query,
                listing_type: state.type,
                city: state.city,
                metadata: { source: "city_menu" }
            });
        });
    });

    document.getElementById("citySearch").addEventListener("input", function () {
        const value = this.value.trim().toLowerCase();
        document.querySelectorAll(".city-option").forEach(btn => {
            btn.style.display = btn.innerText.toLowerCase().includes(value) ? "" : "none";
        });
    });

    document.getElementById("citySearch").addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            const city = this.value.trim();
            if (!city) return;

            state.city = city;
            localStorage.setItem("selectedCity", state.city);
            renderResults();
            trackActivity({
                activity_type: "city_change",
                search_query: state.query,
                listing_type: state.type,
                city: state.city,
                metadata: { source: "city_search" }
            });
        }
    });

    document.querySelector(".js-login-btn")?.addEventListener("click", function () {
        if (this.tagName !== "A") {
            window.location.href = "login";
        }
    });
}

createMobileFilters();
bindEvents();
renderResults();
loadSavedListings();

const agents = {
    "Rahul Singh": {
        role: "Ranchi property expert",
        photo: "https://randomuser.me/api/portraits/men/32.jpg"
    },
    "Priya Sharma": {
        role: "Rental specialist",
        photo: "https://randomuser.me/api/portraits/women/44.jpg"
    },
    "Amit Kumar": {
        role: "Plot and land advisor",
        photo: "https://randomuser.me/api/portraits/men/51.jpg"
    },
    "Neha Verma": {
        role: "Premium property consultant",
        photo: "https://randomuser.me/api/portraits/women/65.jpg"
    }
};

const typeLabels = {
    buy: "Buy",
    rent: "Rent",
    commercial: "Commercial",
    pg: "PG/co-living",
    plots: "Plots"
};

const images = {
    homeA: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=1200&auto=format&fit=crop",
    homeB: "https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?q=80&w=1200&auto=format&fit=crop",
    homeC: "https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1200&auto=format&fit=crop",
    villa: "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?q=80&w=1200&auto=format&fit=crop",
    room: "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?q=80&w=1200&auto=format&fit=crop",
    office: "https://images.unsplash.com/photo-1497366754035-f200968a6e72?q=80&w=1200&auto=format&fit=crop",
    land: "https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=1200&auto=format&fit=crop",
    pg: "https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?q=80&w=1200&auto=format&fit=crop",
    lobby: "https://images.unsplash.com/photo-1560185127-6ed189bf02f4?q=80&w=1200&auto=format&fit=crop"
};

const properties = [
    {
        id: "buy-1",
        type: "buy",
        title: "Luxury 3BHK Apartment",
        priceText: "\u20b968 Lac",
        location: "Bariatu",
        category: "Apartment",
        bhk: 3,
        bathrooms: 2,
        balconies: 2,
        area: 1450,
        carpet: 1180,
        floor: "5 of 10",
        facing: "East",
        furnishing: "Semi furnished",
        possession: "Ready to move",
        ownership: "Freehold",
        status: "Verified",
        verified: true,
        ready: true,
        agent: "Rahul Singh",
        posted: "Updated today",
        images: [images.homeA, images.homeB, images.homeC, images.lobby, images.villa],
        amenities: ["Lift", "Covered Parking", "24x7 Security", "Power Backup", "Balcony", "Modular Kitchen", "Water Supply", "Visitor Parking", "CCTV"],
        description: "A well-planned 3BHK apartment in Bariatu with bright rooms, practical storage, covered parking and quick access to schools, hospitals and daily needs. The home is suitable for families looking for a ready property in a settled residential locality.",
        nearby: [["Hospital", "1.2 km"], ["School", "900 m"], ["Market", "700 m"], ["Main Road", "2.8 km"]],
        pin: { x: 50, y: 48 }
    },
    {
        id: "buy-2",
        type: "buy",
        title: "Premium Villa With Garden",
        priceText: "\u20b91.25 Cr",
        location: "Morabadi",
        category: "Villa",
        bhk: 4,
        bathrooms: 4,
        balconies: 3,
        area: 2400,
        carpet: 1980,
        floor: "Ground + 1",
        facing: "North-East",
        furnishing: "Unfurnished",
        possession: "Ready to move",
        ownership: "Freehold",
        status: "Luxury",
        verified: true,
        ready: true,
        agent: "Neha Verma",
        posted: "Updated yesterday",
        images: [images.villa, images.homeB, images.homeA, images.lobby, images.homeC],
        amenities: ["Private Garden", "Covered Parking", "Security", "Servant Room", "Terrace", "Water Storage", "Power Backup"],
        description: "A premium villa in Morabadi with generous living spaces, a private garden and family-friendly planning. Ideal for buyers who want privacy, outdoor space and a premium address with strong connectivity.",
        nearby: [["Park", "600 m"], ["School", "1.4 km"], ["Market", "900 m"], ["Hospital", "2.1 km"]],
        pin: { x: 56, y: 40 }
    },
    {
        id: "buy-3",
        type: "buy",
        title: "Modern 2BHK Flat",
        priceText: "\u20b942 Lac",
        location: "Argora",
        category: "Apartment",
        bhk: 2,
        bathrooms: 2,
        balconies: 1,
        area: 980,
        carpet: 790,
        floor: "3 of 7",
        facing: "West",
        furnishing: "Unfurnished",
        possession: "Under construction",
        ownership: "Freehold",
        status: "Verified",
        verified: true,
        ready: false,
        agent: "Amit Kumar",
        posted: "Updated 2 days ago",
        images: [images.homeC, images.homeA, images.homeB, images.lobby, images.room],
        amenities: ["Lift", "Power Backup", "Balcony", "Security", "Water Supply"],
        description: "A compact and efficient 2BHK flat in Argora with a practical layout and good access to transport, shopping and office hubs. A strong option for first-time buyers and investors.",
        nearby: [["Station", "2.6 km"], ["Market", "500 m"], ["School", "1.1 km"], ["Hospital", "1.8 km"]],
        pin: { x: 64, y: 56 }
    },
    {
        id: "buy-4",
        type: "buy",
        title: "Skyline Premium Flat",
        priceText: "\u20b985 Lac",
        location: "Kanke",
        category: "Apartment",
        bhk: 3,
        bathrooms: 3,
        balconies: 2,
        area: 1650,
        carpet: 1320,
        floor: "9 of 14",
        facing: "South-East",
        furnishing: "Semi furnished",
        possession: "Ready to move",
        ownership: "Freehold",
        status: "Hot",
        verified: true,
        ready: true,
        agent: "Priya Sharma",
        posted: "Updated today",
        images: [images.homeB, images.homeA, images.homeC, images.lobby, images.villa],
        amenities: ["Clubhouse", "Gym", "Lift", "Security", "Power Backup", "Parking", "Play Area"],
        description: "A premium high-floor apartment in Kanke with open views, quality amenities and a calm residential setting. The project is suitable for buyers looking for comfort and long-term value.",
        nearby: [["School", "1.5 km"], ["Hospital", "2.4 km"], ["Market", "850 m"], ["Ring Road", "3.2 km"]],
        pin: { x: 46, y: 65 }
    },
    {
        id: "rent-1",
        type: "rent",
        title: "2BHK Flat For Rent",
        priceText: "\u20b912,000/mo",
        location: "Lalpur",
        category: "Flat",
        bhk: 2,
        bathrooms: 2,
        balconies: 1,
        area: 900,
        carpet: 740,
        floor: "2 of 5",
        facing: "East",
        furnishing: "Semi furnished",
        possession: "Available now",
        ownership: "Owner listed",
        status: "Family",
        verified: true,
        ready: true,
        agent: "Priya Sharma",
        posted: "Updated today",
        images: [images.room, images.homeA, images.homeC, images.lobby, images.homeB],
        amenities: ["Water Supply", "Parking", "Balcony", "Security", "Kitchen Cabinets"],
        description: "A clean 2BHK rental flat in Lalpur for families, with easy access to markets, schools and public transport. The property is available for quick move-in after basic verification.",
        nearby: [["Market", "450 m"], ["School", "800 m"], ["Hospital", "1.6 km"], ["Bus Stop", "300 m"]],
        pin: { x: 38, y: 62 }
    },
    {
        id: "rent-2",
        type: "rent",
        title: "Semi Furnished 3BHK",
        priceText: "\u20b918,000/mo",
        location: "Kanke Road",
        category: "Flat",
        bhk: 3,
        bathrooms: 2,
        balconies: 2,
        area: 1350,
        carpet: 1060,
        floor: "4 of 8",
        facing: "North",
        furnishing: "Semi furnished",
        possession: "Available now",
        ownership: "Owner listed",
        status: "Popular",
        verified: true,
        ready: true,
        agent: "Rahul Singh",
        posted: "Updated yesterday",
        images: [images.homeB, images.room, images.homeA, images.lobby, images.homeC],
        amenities: ["Lift", "Security", "Parking", "Power Backup", "Wardrobes", "Balcony"],
        description: "A spacious 3BHK rental home on Kanke Road with useful furnishing and secure apartment living. Good for families or working professionals who need more room.",
        nearby: [["School", "1.2 km"], ["Market", "700 m"], ["Hospital", "2.3 km"], ["Park", "900 m"]],
        pin: { x: 58, y: 50 }
    },
    {
        id: "rent-3",
        type: "rent",
        title: "1BHK Independent Flat",
        priceText: "\u20b910,000/mo",
        location: "Argora",
        category: "Flat",
        bhk: 1,
        bathrooms: 1,
        balconies: 1,
        area: 650,
        carpet: 520,
        floor: "1 of 3",
        facing: "West",
        furnishing: "Unfurnished",
        possession: "Available now",
        ownership: "Owner listed",
        status: "New",
        verified: false,
        ready: true,
        agent: "Amit Kumar",
        posted: "Updated 3 days ago",
        images: [images.room, images.homeC, images.lobby, images.homeA, images.homeB],
        amenities: ["Independent Entry", "Water Supply", "Balcony", "Two Wheeler Parking"],
        description: "A simple 1BHK independent flat in Argora with practical access and privacy. Suitable for a single professional or a couple looking for a budget rental.",
        nearby: [["Market", "600 m"], ["Bus Stop", "400 m"], ["Station", "2.4 km"], ["Hospital", "1.9 km"]],
        pin: { x: 68, y: 55 }
    },
    {
        id: "commercial-1",
        type: "commercial",
        title: "Main Road Shop",
        priceText: "\u20b945 Lac",
        location: "Main Road",
        category: "Shop",
        bhk: 0,
        bathrooms: 1,
        balconies: 0,
        area: 420,
        carpet: 360,
        floor: "Ground",
        facing: "Road facing",
        furnishing: "Bare shell",
        possession: "Ready to move",
        ownership: "Freehold",
        status: "Prime",
        verified: true,
        ready: true,
        agent: "Neha Verma",
        posted: "Updated today",
        images: [images.office, images.lobby, images.homeC, images.homeA, images.homeB],
        amenities: ["Main Road", "High Footfall", "Shutter Front", "Parking Nearby", "Water Point"],
        description: "A compact commercial shop on Main Road with strong frontage and visibility. Suitable for retail, services, boutique office or showroom use.",
        nearby: [["Market", "100 m"], ["Parking", "250 m"], ["Bus Stop", "300 m"], ["Bank", "200 m"]],
        pin: { x: 48, y: 52 }
    },
    {
        id: "commercial-2",
        type: "commercial",
        title: "Corporate Office Space",
        priceText: "\u20b935,000/mo",
        location: "Lalpur",
        category: "Office",
        bhk: 0,
        bathrooms: 2,
        balconies: 0,
        area: 1100,
        carpet: 930,
        floor: "2 of 5",
        facing: "East",
        furnishing: "Furnished",
        possession: "Available now",
        ownership: "Lease",
        status: "Rent",
        verified: true,
        ready: true,
        agent: "Rahul Singh",
        posted: "Updated 2 days ago",
        images: [images.office, images.lobby, images.room, images.homeA, images.homeC],
        amenities: ["Reception", "Lift", "Parking", "Power Backup", "Conference Room", "Pantry"],
        description: "A ready office space in Lalpur with reception, pantry and conference room setup. Suitable for small teams, consultants and service businesses.",
        nearby: [["Main Road", "900 m"], ["Bank", "300 m"], ["Cafe", "200 m"], ["Parking", "150 m"]],
        pin: { x: 40, y: 64 }
    },
    {
        id: "pg-1",
        type: "pg",
        title: "Boys PG With Food",
        priceText: "\u20b96,500/mo",
        location: "Lalpur",
        category: "PG",
        bhk: 0,
        bathrooms: 1,
        balconies: 0,
        area: 220,
        carpet: 180,
        floor: "Shared",
        facing: "Open",
        furnishing: "Furnished",
        possession: "Available now",
        ownership: "Managed",
        status: "Budget",
        verified: true,
        ready: true,
        agent: "Priya Sharma",
        posted: "Updated today",
        images: [images.pg, images.room, images.lobby, images.homeC, images.homeA],
        amenities: ["Food", "WiFi", "Laundry", "Housekeeping", "Security", "Study Table"],
        description: "A budget boys PG in Lalpur with meals, WiFi and basic housekeeping. Suitable for students and working professionals looking for a practical stay.",
        nearby: [["College", "850 m"], ["Market", "350 m"], ["Bus Stop", "250 m"], ["Hospital", "1.5 km"]],
        pin: { x: 36, y: 66 }
    },
    {
        id: "pg-2",
        type: "pg",
        title: "Premium Co-living",
        priceText: "\u20b911,000/mo",
        location: "Morabadi",
        category: "Co-living",
        bhk: 0,
        bathrooms: 1,
        balconies: 0,
        area: 320,
        carpet: 250,
        floor: "Shared",
        facing: "Open",
        furnishing: "Furnished",
        possession: "Available now",
        ownership: "Managed",
        status: "Premium",
        verified: true,
        ready: true,
        agent: "Neha Verma",
        posted: "Updated yesterday",
        images: [images.pg, images.room, images.lobby, images.homeB, images.homeC],
        amenities: ["AC", "WiFi", "Housekeeping", "Laundry", "Power Backup", "Common Lounge"],
        description: "A premium co-living option in Morabadi with managed services and comfortable shared spaces. Suitable for working professionals who prefer convenience.",
        nearby: [["Park", "500 m"], ["Cafe", "450 m"], ["Market", "700 m"], ["Hospital", "2 km"]],
        pin: { x: 54, y: 35 }
    },
    {
        id: "plots-1",
        type: "plots",
        title: "Residential Plot",
        priceText: "\u20b918 Lac",
        location: "Ring Road",
        category: "Plot",
        bhk: 0,
        bathrooms: 0,
        balconies: 0,
        area: 1200,
        carpet: 1200,
        floor: "Open plot",
        facing: "East",
        furnishing: "Land",
        possession: "Immediate",
        ownership: "Freehold",
        status: "Hot",
        verified: true,
        ready: false,
        agent: "Amit Kumar",
        posted: "Updated today",
        images: [images.land, images.villa, images.homeA, images.homeB, images.homeC],
        amenities: ["Road Access", "Mutation Ready", "Electricity Nearby", "Drainage", "Gated Entry"],
        description: "A residential plot near Ring Road with road access and promising investment potential. Suitable for future home construction or long-term land holding.",
        nearby: [["Ring Road", "400 m"], ["School", "2.2 km"], ["Market", "1.4 km"], ["Hospital", "3.1 km"]],
        pin: { x: 72, y: 42 }
    },
    {
        id: "plots-2",
        type: "plots",
        title: "Corner Plot",
        priceText: "\u20b932 Lac",
        location: "Kanke",
        category: "Plot",
        bhk: 0,
        bathrooms: 0,
        balconies: 0,
        area: 2400,
        carpet: 2400,
        floor: "Open plot",
        facing: "North-East",
        furnishing: "Land",
        possession: "Immediate",
        ownership: "Freehold",
        status: "Prime",
        verified: true,
        ready: false,
        agent: "Amit Kumar",
        posted: "Updated 2 days ago",
        images: [images.land, images.villa, images.homeB, images.homeA, images.homeC],
        amenities: ["Corner Plot", "Gated Society", "Wide Road", "Electricity Nearby", "Clear Title"],
        description: "A corner residential plot in Kanke inside a developing gated society. The plot has wide-road access and is suitable for a premium independent home.",
        nearby: [["School", "1.8 km"], ["Market", "1.1 km"], ["Ring Road", "2.5 km"], ["Hospital", "2.8 km"]],
        pin: { x: 48, y: 68 }
    }
];

const params = new URLSearchParams(window.location.search);
const state = {
    city: params.get("city") || localStorage.getItem("selectedCity") || "Ranchi",
    id: params.get("id") || "",
    imageIndex: 0,
    saved: false
};

let currentProperty = properties.find(item => item.id === state.id) || properties[0];
let hasTrackedView = false;

function detailsUrl(property) {
    const next = new URLSearchParams();
    next.set("id", property.id);
    next.set("type", property.type);
    next.set("city", state.city);
    return `property-details?${next.toString()}`;
}

function listingUrl(type = currentProperty.type) {
    const next = new URLSearchParams();
    next.set("type", type);
    next.set("city", state.city);
    return `listing?${next.toString()}`;
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

function trackProperty(activityType, metadata = {}) {
    trackActivity({
        activity_type: activityType,
        entity_type: "property",
        entity_id: currentProperty.id,
        listing_type: currentProperty.type,
        city: state.city,
        metadata: Object.assign({
            title: currentProperty.title,
            locality: currentProperty.location,
            category: currentProperty.category
        }, metadata)
    });
}

function trackPropertyView() {
    if (hasTrackedView) {
        return;
    }

    hasTrackedView = true;
    trackProperty("property_view", { source: "property_details" });
}

function propertyPayload(source, extra = {}) {
    return Object.assign({
        property_ref: currentProperty.id,
        listing_type: currentProperty.type,
        title: currentProperty.title,
        price_text: currentProperty.priceText,
        city: state.city,
        locality: currentProperty.location,
        category: currentProperty.category,
        image_url: currentProperty.images[0],
        details_url: new URL(detailsUrl(currentProperty), window.location.href).href,
        source,
        page_url: window.location.href,
        page_title: document.title,
        metadata: {
            agent: currentProperty.agent,
            area: currentProperty.area,
            bhk: currentProperty.bhk,
            bathrooms: currentProperty.bathrooms,
            status: currentProperty.status,
            ready: currentProperty.ready,
            verified: currentProperty.verified
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

function loadSavedState() {
    fetch("saved-property", {
        credentials: "same-origin",
        cache: "no-store"
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.logged_in) {
                return;
            }

            state.saved = (data.saved_ids || []).map(String).includes(currentProperty.id);
            updateSaveButton();
        })
        .catch(() => {});
}

function persistSavedProperty(shouldSave, source) {
    postJson("saved-property", propertyPayload(source, {
        action: shouldSave ? "save" : "unsave"
    }))
        .then(data => {
            if (handleLoginRequired(data)) {
                return;
            }

            if (!data.success) {
                throw new Error(data.message || "Unable to update saved property.");
            }

            state.saved = !!data.saved;
            updateSaveButton();
            showNotice({
                icon: "success",
                title: data.saved ? "Property saved" : "Removed from saved",
                text: data.saved ? `${currentProperty.title} is now in Saved Properties.` : `${currentProperty.title} was removed from Saved Properties.`,
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

function createPropertyEnquiry(source) {
    postJson("property-enquiry", propertyPayload(source, {
        message: `Interested in ${currentProperty.title} at ${currentProperty.location}, ${state.city}.`
    }))
        .then(data => {
            if (handleLoginRequired(data)) {
                return;
            }

            if (!data.success) {
                throw new Error(data.message || "Unable to create enquiry.");
            }

            showEnquirySuccess();
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

function renderProperty() {
    const item = currentProperty;
    const agent = agents[item.agent] || agents["Rahul Singh"];

    document.title = `${item.title} - GharSquare`;
    document.getElementById("headerCity").innerText = state.city;
    document.getElementById("detailsListingBack").href = listingUrl(item.type);
    document.getElementById("breadcrumbTitle").innerText = item.title;

    document.getElementById("detailTitle").innerText = item.title;
    document.getElementById("detailLocation").innerHTML = `<i class="bi bi-geo-alt"></i> ${item.location}, ${state.city}`;
    document.getElementById("detailPrice").innerText = item.priceText;
    document.getElementById("postedText").innerText = item.posted;
    document.getElementById("localityBadge").innerText = `${item.location}, ${state.city}`;
    document.getElementById("propertyDescription").innerText = item.description;

    document.getElementById("summaryBadges").innerHTML = [
        item.verified ? "Verified" : item.status,
        typeLabels[item.type],
        item.possession
    ].map(label => `<span class="summary-badge">${label}</span>`).join("");

    document.getElementById("detailSpecs").innerHTML = [
        ["Property Type", item.category],
        ["Area", `${item.area} sqft`],
        ["Bedrooms", item.bhk ? `${item.bhk} BHK` : "Not applicable"],
        ["Possession", item.possession]
    ].map(([label, value]) => `
        <div class="detail-spec">
            <span>${label}</span>
            <strong>${value}</strong>
        </div>
    `).join("");

    document.getElementById("overviewGrid").innerHTML = [
        ["bi-aspect-ratio", "Built-up Area", `${item.area} sqft`],
        ["bi-bounding-box", "Carpet Area", `${item.carpet} sqft`],
        ["bi-droplet", "Bathrooms", item.bathrooms || "Not applicable"],
        ["bi-door-open", "Balconies", item.balconies || "Not applicable"],
        ["bi-layers", "Floor", item.floor],
        ["bi-compass", "Facing", item.facing],
        ["bi-lamp", "Furnishing", item.furnishing],
        ["bi-file-earmark-check", "Ownership", item.ownership]
    ].map(([icon, label, value]) => `
        <div class="overview-item">
            <i class="bi ${icon}"></i>
            <span>${label}</span>
            <strong>${value}</strong>
        </div>
    `).join("");

    document.getElementById("amenityGrid").innerHTML = item.amenities.map(name => `
        <div class="amenity-item"><i class="bi bi-check2-circle"></i> ${name}</div>
    `).join("");

    document.getElementById("nearbyList").innerHTML = item.nearby.map(([name, distance]) => `
        <div class="nearby-item">
            <span>${name}</span>
            <span>${distance}</span>
        </div>
    `).join("");

    const pin = document.getElementById("detailMapPin");
    pin.style.left = `${item.pin.x}%`;
    pin.style.top = `${item.pin.y}%`;

    document.getElementById("agentPhoto").src = agent.photo;
    document.getElementById("agentPhoto").alt = item.agent;
    document.getElementById("agentName").innerText = item.agent;
    document.getElementById("agentRole").innerText = agent.role;

    const message = encodeURIComponent(`Hi GharSquare, I am interested in ${item.title} at ${item.location}, ${state.city}.`);
    document.getElementById("whatsappBtn").href = `https://wa.me/917004757477?text=${message}`;

    renderGallery();
    renderSimilar();
    updateSaveButton();
    updateUrl();
    trackPropertyView();
}

function renderGallery() {
    const item = currentProperty;
    document.getElementById("detailMainImage").src = item.images[state.imageIndex];
    document.getElementById("detailMainImage").alt = item.title;
    document.getElementById("photoCount").innerText = `${item.images.length} Photos`;

    document.getElementById("detailThumbs").innerHTML = item.images.map((src, index) => `
        <button type="button" class="thumb-btn ${index === state.imageIndex ? "active" : ""}" data-index="${index}" aria-label="View photo ${index + 1}">
            <img src="${src}" alt="${currentProperty.title} photo ${index + 1}">
        </button>
    `).join("");
}

function renderSimilar() {
    const similar = properties
        .filter(item => item.type === currentProperty.type && item.id !== currentProperty.id)
        .slice(0, 3);

    document.getElementById("similarGrid").innerHTML = similar.map(item => `
        <a class="similar-card" data-id="${item.id}" href="${detailsUrl(item)}">
            <img src="${item.images[0]}" alt="${item.title}">
            <div class="similar-card-body">
                <strong>${item.priceText}</strong>
                <h3>${item.title}</h3>
                <p>${item.location}, ${state.city}</p>
            </div>
        </a>
    `).join("");
}

function updateUrl() {
    window.history.replaceState(null, "", detailsUrl(currentProperty));
}

function updateSaveButton() {
    const button = document.getElementById("savePropertyBtn");
    button.classList.toggle("active", state.saved);
    button.innerHTML = `<i class="bi ${state.saved ? "bi-heart-fill" : "bi-heart"}"></i>`;
}

function nextImage(direction, source = "gallery_control") {
    const total = currentProperty.images.length;
    state.imageIndex = (state.imageIndex + direction + total) % total;
    renderGallery();
    trackProperty("gallery_interaction", {
        source,
        direction,
        image_index: state.imageIndex
    });
}

function showEnquirySuccess() {
    showNotice({
        icon: "success",
        title: "Enquiry sent",
        text: `Our team will connect you with ${currentProperty.agent} for ${currentProperty.title} in ${currentProperty.location}, ${state.city}.`,
        confirmButtonColor: "#0f766e"
    });
}

function bindEvents() {
    document.getElementById("galleryPrev").addEventListener("click", () => nextImage(-1, "gallery_prev"));
    document.getElementById("galleryNext").addEventListener("click", () => nextImage(1, "gallery_next"));

    document.getElementById("detailThumbs").addEventListener("click", function (e) {
        const thumb = e.target.closest(".thumb-btn");
        if (!thumb) return;

        state.imageIndex = Number(thumb.dataset.index);
        renderGallery();
        trackProperty("gallery_interaction", {
            source: "gallery_thumb",
            image_index: state.imageIndex
        });
    });

    document.getElementById("savePropertyBtn").addEventListener("click", function () {
        persistSavedProperty(!state.saved, "property_details");
    });

    document.getElementById("enquireBtn").addEventListener("click", () => createPropertyEnquiry("hero_actions"));
    document.getElementById("agentEnquireBtn").addEventListener("click", () => createPropertyEnquiry("agent_card"));

    document.getElementById("callBtn").addEventListener("click", function () {
        trackProperty("call_click", { source: "hero_actions" });
    });

    document.getElementById("agentCallBtn").addEventListener("click", function () {
        trackProperty("call_click", { source: "agent_card" });
    });

    document.getElementById("whatsappBtn").addEventListener("click", function () {
        trackProperty("whatsapp_click", { source: "hero_actions" });
    });

    document.querySelectorAll(".visit-options button").forEach(btn => {
        btn.addEventListener("click", function () {
            document.querySelectorAll(".visit-options button").forEach(item => item.classList.remove("active"));
            this.classList.add("active");
            showNotice({
                icon: "success",
                title: "Visit preference saved",
                text: `Preferred visit slot: ${this.innerText}.`,
                confirmButtonColor: "#0f766e"
            });
            trackProperty("visit_preference", {
                source: "visit_options",
                preference: this.innerText.trim()
            });
        });
    });

    document.querySelectorAll(".city-option").forEach(btn => {
        btn.addEventListener("click", function () {
            state.city = this.innerText.trim();
            localStorage.setItem("selectedCity", state.city);
            renderProperty();
            trackProperty("city_change", { source: "city_menu" });
        });
    });

    document.getElementById("citySearch").addEventListener("input", function () {
        const value = this.value.trim().toLowerCase();
        document.querySelectorAll(".city-option").forEach(btn => {
            btn.style.display = btn.innerText.toLowerCase().includes(value) ? "" : "none";
        });
    });

    document.getElementById("citySearch").addEventListener("keydown", function (e) {
        if (e.key !== "Enter") return;
        e.preventDefault();

        const city = this.value.trim();
        if (!city) return;

        state.city = city;
        localStorage.setItem("selectedCity", state.city);
        renderProperty();
        trackProperty("city_change", { source: "city_search" });
    });

    document.getElementById("similarGrid").addEventListener("click", function (e) {
        const card = e.target.closest(".similar-card");
        if (!card) return;

        const similar = properties.find(item => item.id === card.dataset.id);
        if (!similar) return;

        trackActivity({
            activity_type: "similar_property_open",
            entity_type: "property",
            entity_id: similar.id,
            listing_type: similar.type,
            city: state.city,
            metadata: {
                title: similar.title,
                locality: similar.location,
                from_property_id: currentProperty.id
            }
        });
    });

    document.querySelector(".js-login-btn")?.addEventListener("click", function () {
        if (this.tagName !== "A") {
            window.location.href = "login";
        }
    });
}

bindEvents();
renderProperty();
loadSavedState();

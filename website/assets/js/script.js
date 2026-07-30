let heroSwiper;
let propertySwiper;
let selectedCity = localStorage.getItem("selectedCity") || "Ranchi";
let currentType = "buy";
let localityRequestController;

const data = {
    buy: {
        title: "Properties to buy in Ranchi",
        subtitle: "7K+ listings added daily and 75K+ total verified",
        sectionTitle: "Top highlighted projects",
        sectionSubTitle: "Noteworthy projects to watch",
        placeholder: "Search for locality, landmark, project, or builder",
        slides: [
            "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?q=80&w=900&auto=format&fit=crop"
        ],
        properties: [
            ["\u20b968 Lac", "Luxury 3BHK Apartment", "Bariatu, Ranchi", "3 BHK", "1450 sqft", "Ready"],
            ["\u20b91.25 Cr", "Premium Villa With Garden", "Morabadi, Ranchi", "4 BHK", "2400 sqft", "Luxury"],
            ["\u20b942 Lac", "Modern 2BHK Flat", "Argora, Ranchi", "2 BHK", "980 sqft", "Verified"],
            ["\u20b985 Lac", "Skyline Premium Flat", "Kanke, Ranchi", "3 BHK", "1650 sqft", "Hot"],
            ["\u20b958 Lac", "Family Apartment", "Namkum, Ranchi", "3 BHK", "1300 sqft", "New"]
        ]
    },
    rent: {
        title: "Find rental properties in Ranchi",
        subtitle: "1800+ rental homes, flats and PG options",
        sectionTitle: "Best properties for rent",
        sectionSubTitle: "Ready to move rental homes near you",
        placeholder: "Search rental flat, PG, location or landmark",
        slides: [
            "https://images.unsplash.com/photo-1560185127-6ed189bf02f4?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?q=80&w=900&auto=format&fit=crop"
        ],
        properties: [
            ["\u20b912,000/mo", "2BHK Flat For Rent", "Lalpur, Ranchi", "2 BHK", "900 sqft", "Family"],
            ["\u20b918,000/mo", "Semi Furnished 3BHK", "Kanke Road, Ranchi", "3 BHK", "1350 sqft", "Popular"],
            ["\u20b97,500/mo", "Single Room PG", "Bariatu, Ranchi", "PG", "Food", "Budget"],
            ["\u20b925,000/mo", "Luxury Rental Flat", "Morabadi, Ranchi", "3 BHK", "1600 sqft", "Premium"],
            ["\u20b910,000/mo", "1BHK Independent Flat", "Argora, Ranchi", "1 BHK", "650 sqft", "New"]
        ]
    },
    commercial: {
        title: "Commercial properties in Ranchi",
        subtitle: "Shops, offices and showrooms for your business",
        sectionTitle: "Commercial spaces for business",
        sectionSubTitle: "Office, showroom, shop and warehouse options",
        placeholder: "Search office, shop, showroom or commercial locality",
        slides: [
            "https://images.unsplash.com/photo-1497366754035-f200968a6e72?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1497366811353-6870744d04b2?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=900&auto=format&fit=crop"
        ],
        properties: [
            ["\u20b945 Lac", "Main Road Shop", "Main Road, Ranchi", "Shop", "420 sqft", "Prime"],
            ["\u20b935,000/mo", "Corporate Office Space", "Lalpur, Ranchi", "Office", "1100 sqft", "Rent"],
            ["\u20b91.8 Cr", "Premium Showroom", "Harmu, Ranchi", "Showroom", "2200 sqft", "Hot"],
            ["\u20b922,000/mo", "Small Office Setup", "Doranda, Ranchi", "Office", "650 sqft", "Budget"],
            ["\u20b970 Lac", "Commercial Shop", "Kutchery Road", "Shop", "580 sqft", "Verified"]
        ]
    },
    pg: {
        title: "PG and co-living spaces",
        subtitle: "Safe, verified and budget friendly stays",
        sectionTitle: "PG and co-living options",
        sectionSubTitle: "Best stays for students and working professionals",
        placeholder: "Search PG, hostel, co-living or nearby area",
        slides: [
            "https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1555854877-bab0e564b8d5?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?q=80&w=900&auto=format&fit=crop"
        ],
        properties: [
            ["\u20b96,500/mo", "Boys PG With Food", "Lalpur, Ranchi", "PG", "Food", "Budget"],
            ["\u20b98,500/mo", "Girls PG Near College", "Bariatu, Ranchi", "PG", "WiFi", "Safe"],
            ["\u20b911,000/mo", "Premium Co-living", "Morabadi, Ranchi", "Co-living", "AC", "Premium"],
            ["\u20b95,500/mo", "Student Hostel", "Kanke, Ranchi", "Hostel", "Food", "Popular"],
            ["\u20b99,000/mo", "Working PG", "Argora, Ranchi", "PG", "Laundry", "Verified"]
        ]
    },
    plots: {
        title: "Investment plots and land",
        subtitle: "1200+ residential plots and land opportunities",
        sectionTitle: "Top plots and land deals",
        sectionSubTitle: "Residential plots, farmhouse land and investment land",
        placeholder: "Search plots, land, area or investment location",
        slides: [
            "https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=900&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=900&auto=format&fit=crop"
        ],
        properties: [
            ["\u20b918 Lac", "Residential Plot", "Ring Road, Ranchi", "Plot", "1200 sqft", "Hot"],
            ["\u20b932 Lac", "Corner Plot", "Kanke, Ranchi", "Plot", "2400 sqft", "Prime"],
            ["\u20b912 Lac", "Budget Land", "Namkum, Ranchi", "Land", "1000 sqft", "Budget"],
            ["\u20b955 Lac", "Farmhouse Land", "Ormanjhi, Ranchi", "Land", "5000 sqft", "Premium"],
            ["\u20b925 Lac", "Gated Society Plot", "Bariatu, Ranchi", "Plot", "1500 sqft", "Verified"]
        ]
    }
};

const propertyImages = [
    "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?q=80&w=900&auto=format&fit=crop",
    "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?q=80&w=900&auto=format&fit=crop"
];

function initHeroSlider(type) {
    const slideBox = document.getElementById("heroSlides");
    slideBox.innerHTML = data[type].slides.map(img => `
        <div class="swiper-slide hero-slide">
            <img src="${img}" alt="Property">
        </div>
    `).join("");

    if (heroSwiper) heroSwiper.destroy(true, true);

    heroSwiper = new Swiper(".heroSwiper", {
        loop: true,
        autoplay: { delay: 2500 },
        effect: "fade",
        speed: 900
    });
}

function renderProperties(type) {
    const cards = document.getElementById("propertyCards");

    cards.innerHTML = data[type].properties.map((p, i) => `
        <div class="swiper-slide">
            <div class="property-card">
                <div class="property-img">
                    <img src="${propertyImages[i % propertyImages.length]}" alt="${p[1]}">
                    <span class="badge-premium">${p[5]}</span>
                </div>
                <div class="property-body">
                    <div class="price">${p[0]}</div>
                    <h5>${p[1]}</h5>
                    <div class="location"><i class="bi bi-geo-alt"></i> ${p[2].replace("Ranchi", selectedCity)}</div>
                    <div class="property-info mt-3">
                        <span>${p[3]}</span>
                        <span>${p[4]}</span>
                        <span><i class="bi bi-heart"></i></span>
                    </div>
                </div>
            </div>
        </div>
    `).join("");

    if (propertySwiper) propertySwiper.destroy(true, true);

    propertySwiper = new Swiper(".propertySwiper", {
        slidesPerView: 4,
        spaceBetween: 22,
        loop: true,
        autoplay: { delay: 2800 },
        pagination: {
            el: ".swiper-pagination",
            clickable: true
        },
        breakpoints: {
            0: { slidesPerView: 2, spaceBetween: 10 },
            576: { slidesPerView: 2, spaceBetween: 14 },
            768: { slidesPerView: 3, spaceBetween: 18 },
            1200: { slidesPerView: 4, spaceBetween: 22 }
        }
    });
}

function changeContent(type) {
    if (!data[type]) return;

    currentType = type;
    const city = getCityText();
    let title = data[type].title;

    title = title
        .replace("Ranchi", city)
        .replace("ranchi", city);

    document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));
    document.querySelector(`.tab-btn[data-type="${type}"]`)?.classList.add("active");

    document.getElementById("heroTitle").innerText = title;
    document.getElementById("heroSubTitle").innerText = data[type].subtitle;
    document.getElementById("sectionTitle").innerText = data[type].sectionTitle;
    document.getElementById("sectionSubTitle").innerText = data[type].sectionSubTitle;
    document.getElementById("searchInput").placeholder = data[type].placeholder;

    initHeroSlider(type);
    renderProperties(type);
}

function searchProperty() {
    const searchedLocation = document.getElementById("searchInput").value.trim();
    openListing(currentType, searchedLocation);
}

function escapeLocalityText(value) {
    return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function renderPopularLocalities(localities, city, cityFound = true) {
    const list = document.getElementById("localityChipList");

    if (!list) {
        return;
    }

    if (!cityFound || !Array.isArray(localities) || localities.length === 0) {
        list.innerHTML = `<span class="locality-empty">No locality data available for ${escapeLocalityText(city)}.</span>`;
        return;
    }

    list.innerHTML = localities.map(locality => `
        <button type="button" class="locality-chip" data-locality="${escapeLocalityText(locality.name)}">
            ${escapeLocalityText(locality.name)}
            <i class="bi bi-chevron-right"></i>
        </button>
    `).join("");
}

function loadPopularLocalities(city) {
    const list = document.getElementById("localityChipList");
    const requestedCity = String(city || "").trim();

    if (!list || requestedCity === "") {
        return Promise.resolve();
    }

    if (localityRequestController) {
        localityRequestController.abort();
    }

    localityRequestController = new AbortController();
    list.innerHTML = `<span class="locality-loading">Loading ${escapeLocalityText(requestedCity)} localities...</span>`;

    return fetch(`home-localities?city=${encodeURIComponent(requestedCity)}&limit=5`, {
        headers: { Accept: "application/json" },
        signal: localityRequestController.signal
    })
        .then(response => response.json())
        .then(payload => {
            if (!payload.success) {
                throw new Error(payload.message || "Unable to load localities.");
            }

            renderPopularLocalities(payload.localities || [], payload.city || requestedCity, payload.city_found !== false);
        })
        .catch(error => {
            if (error.name === "AbortError") {
                return;
            }

            list.innerHTML = '<span class="locality-empty">Unable to load localities right now.</span>';
        });
}

function updateCityEverywhere(city) {
    selectedCity = city || "Ranchi";
    localStorage.setItem("selectedCity", selectedCity);

    document.getElementById("headerCity").innerText = selectedCity;
    changeContent(currentType);
    loadPopularLocalities(selectedCity);
}

function getCityText() {
    return selectedCity;
}

function formatType(type) {
    const labels = {
        buy: "buy",
        rent: "rental",
        commercial: "commercial",
        pg: "PG/co-living",
        plots: "plot"
    };

    return labels[type] || "property";
}

function showNotice(options) {
    if (window.Swal) {
        Swal.fire(options);
        return;
    }

    alert(`${options.title}\n${options.text || ""}`);
}

function trackActivity(payload) {
    window.GharSquareAuth?.trackActivity(payload);
}

function openListing(type = currentType, query = "") {
    trackActivity({
        activity_type: query ? "search" : "listing_open",
        search_query: query,
        listing_type: type,
        city: selectedCity,
        metadata: {
            source: "homepage"
        }
    });

    const params = new URLSearchParams();
    params.set("type", type);
    params.set("city", selectedCity);

    if (query) {
        params.set("q", query);
    }

    window.location.href = `listing?${params.toString()}`;
}

document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("headerCity").innerText = selectedCity;
    changeContent("buy");
    loadPopularLocalities(selectedCity);
});

document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.addEventListener("click", () => changeContent(btn.dataset.type));
});

document.querySelectorAll(".category-card").forEach(card => {
    card.addEventListener("click", () => {
        trackActivity({
            activity_type: "category_click",
            listing_type: card.dataset.type,
            city: selectedCity,
            metadata: {
                label: card.querySelector("h5")?.textContent || ""
            }
        });
        openListing(card.dataset.type);
    });
});

document.querySelectorAll(".city-option").forEach(btn => {
    btn.addEventListener("click", function () {
        updateCityEverywhere(this.innerText.trim());
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

        if (city !== "") {
            updateCityEverywhere(city);
        }
    }
});

document.getElementById("searchBtn").addEventListener("click", searchProperty);

document.getElementById("searchInput").addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
        e.preventDefault();
        searchProperty();
    }
});

document.querySelectorAll("[data-nav-type]").forEach(link => {
    link.addEventListener("click", function () {
        const href = this.getAttribute("href") || "";
        if (href.startsWith("#")) {
            changeContent(this.dataset.navType);
        }
    });
});

document.getElementById("localityChipList")?.addEventListener("click", function (event) {
    const button = event.target.closest(".locality-chip");

    if (!button) {
        return;
    }

    const locality = button.dataset.locality || button.textContent.trim();
    document.getElementById("searchInput").value = locality;
    searchProperty();
});

document.querySelectorAll(".js-owner-cta").forEach(link => {
    link.addEventListener("click", function (e) {
        e.preventDefault();
        document.getElementById("owner-cta")?.scrollIntoView({ behavior: "smooth", block: "center" });
        trackActivity({
            activity_type: "owner_cta_click",
            city: selectedCity,
            metadata: {
                source: "homepage"
            }
        });
        showNotice({
            icon: "info",
            title: "Post property for free",
            text: "Start the step-by-step wizard to save your listing as a draft and submit it for review.",
            confirmButtonColor: "#5b21b6"
        });
    });
});

document.querySelector(".js-login-btn")?.addEventListener("click", function () {
    if (this.tagName !== "A") {
            window.location.href = "login";
    }
});

document.querySelector(".js-view-all").addEventListener("click", function () {
    openListing(currentType);
});

document.querySelector(".js-start-exploring").addEventListener("click", function () {
    changeContent("buy");
    document.getElementById("properties")?.scrollIntoView({ behavior: "smooth" });
});

document.querySelectorAll(".js-agent-contact").forEach(btn => {
    btn.addEventListener("click", function () {
        trackActivity({
            activity_type: "agent_contact_click",
            city: selectedCity,
            metadata: {
                agent: this.dataset.agent
            }
        });
        showNotice({
            icon: "success",
            title: "Expert selected",
            text: `${this.dataset.agent} will be connected through the enquiry flow next.`,
            confirmButtonColor: "#5b21b6"
        });
    });
});

(function () {
    document.querySelectorAll("[data-city-search]").forEach(function (input) {
        input.addEventListener("input", function () {
            const value = input.value.trim().toLowerCase();
            input.closest(".location-dropdown")?.querySelectorAll(".city-option").forEach(function (option) {
                option.hidden = !option.textContent.toLowerCase().includes(value);
            });
        });
    });

    document.querySelectorAll("[data-scroll-enquiry]").forEach(function (button) {
        button.addEventListener("click", function () {
            document.getElementById("propertyEnquiry")?.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    });
})();

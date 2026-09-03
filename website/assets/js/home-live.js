(function () {
    const typeInput = document.querySelector("[data-home-type]");

    document.querySelectorAll(".search-tabs .tab-btn").forEach(function (button) {
        button.addEventListener("click", function () {
            document.querySelectorAll(".search-tabs .tab-btn").forEach(function (item) {
                item.classList.remove("active");
            });
            button.classList.add("active");
            if (typeInput) {
                typeInput.value = button.dataset.type || "";
            }
        });
    });

    if (window.Swiper && document.querySelector(".heroSwiper")) {
        new Swiper(".heroSwiper", {
            loop: false,
            rewind: true,
            autoplay: { delay: 3200 },
            effect: "fade",
            speed: 700
        });
    }

    if (window.Swiper && document.querySelector(".propertySwiper")) {
        new Swiper(".propertySwiper", {
            slidesPerView: 1.15,
            spaceBetween: 12,
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: {
                480: { slidesPerView: 1.5 },
                576: { slidesPerView: 2 },
                900: { slidesPerView: 3 },
                1200: { slidesPerView: 4 }
            }
        });
    }
})();

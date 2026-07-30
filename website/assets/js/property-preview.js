document.addEventListener("DOMContentLoaded", () => {
    const gallery = document.querySelector("[data-preview-gallery]");
    const mainImage = document.querySelector("[data-preview-main-image]");
    const thumbs = Array.from(document.querySelectorAll("[data-preview-thumb]"));
    const previous = document.querySelector("[data-preview-gallery-prev]");
    const next = document.querySelector("[data-preview-gallery-next]");
    let activeIndex = 0;

    if (!gallery || !mainImage || thumbs.length === 0) {
        return;
    }

    function showImage(index) {
        activeIndex = (index + thumbs.length) % thumbs.length;
        const activeThumb = thumbs[activeIndex];
        mainImage.src = activeThumb.dataset.src || "";
        mainImage.alt = activeThumb.getAttribute("aria-label") || "Property image";
        thumbs.forEach((thumb, thumbIndex) => thumb.classList.toggle("active", thumbIndex === activeIndex));
    }

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener("click", () => showImage(index));
    });

    previous?.addEventListener("click", () => showImage(activeIndex - 1));
    next?.addEventListener("click", () => showImage(activeIndex + 1));
});

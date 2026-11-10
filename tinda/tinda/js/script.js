/* ===========================================
   TINDA — Homepage animations
   =========================================== */

// ---------- 1. HERO PARALLAX ----------
const hero = document.querySelector('.hero');
const heroInner = document.querySelector('.hero-inner');

function parallaxHero() {
    if (!hero || !heroInner) return;

    const scrollY = window.scrollY;
    const heroHeight = hero.offsetHeight;

    // Stop applying parallax once we've scrolled past the hero
    if (scrollY > heroHeight) {
        heroInner.style.transform = `translateY(${heroHeight * 0.4}px)`;
        heroInner.style.opacity = 0;
        return;
    }

    // Hero content moves UP slower than the page
    // 0.4 = 40% of scroll speed → creates depth
    const yOffset = scrollY * 0.4;

    // Fade out over the hero's height
    const opacity = 1 - (scrollY / (heroHeight * 0.85));

    heroInner.style.transform = `translateY(${yOffset}px)`;
    heroInner.style.opacity = Math.max(opacity, 0);
}

// Use rAF for smoothness — no transition needed in CSS
window.addEventListener('scroll', () => {
    window.requestAnimationFrame(parallaxHero);
}, { passive: true });

parallaxHero(); // run on load


// ---------- 2. FADE-IN ON SCROLL ----------
const revealEls = document.querySelectorAll('.reveal');

const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
        }
    });
}, {
    threshold: 0.15
});

revealEls.forEach(el => revealObserver.observe(el));


// ---------- 3. STATS COUNT-UP ----------
const statNums = document.querySelectorAll('.stat-num');

const countObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (!entry.isIntersecting) return;

        const el = entry.target;
        const target = parseInt(el.dataset.count, 10) || 0;
        const suffix = el.dataset.suffix || '';

        // Per-stat duration: give bigger/special numbers more time
        // 100% gets a slow, satisfying 2200ms; others stay quick
        let duration = 900;
        if (target === 100) duration = 2200;
        else if (target >= 10) duration = 1400;

        const start = performance.now();

        function tick(now) {
            const t = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3);  // ease-out cubic
            el.textContent = Math.round(eased * target) + suffix;
            if (t < 1) requestAnimationFrame(tick);
        }

        requestAnimationFrame(tick);
        countObserver.unobserve(el);
    });
}, { threshold: 0.5 });

statNums.forEach(el => countObserver.observe(el));
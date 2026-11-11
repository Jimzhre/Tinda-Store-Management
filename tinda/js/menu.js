/* ===========================================
   TINDA — Menu button (phones only)
   Adds a 3-line button to the header that slides the menu in/out.
   Needs no changes to the header HTML.
   =========================================== */
(function () {
    var header = document.querySelector('header');
    var nav = header ? header.querySelector('nav') : null;
    if (!header || !nav) return;   // no menu on this page (e.g. homepage) -> do nothing

    // Button
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'nav-toggle';
    btn.setAttribute('aria-label', 'Open menu');
    btn.setAttribute('aria-expanded', 'false');
    btn.innerHTML = '<span></span><span></span><span></span>';

    // Dark background behind the menu
    var overlay = document.createElement('div');
    overlay.className = 'nav-overlay';

    header.appendChild(overlay);
    header.appendChild(btn);

    function setOpen(open) {
        nav.classList.toggle('open', open);
        overlay.classList.toggle('show', open);
        btn.classList.toggle('open', open);
        document.body.classList.toggle('menu-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }

    btn.addEventListener('click', function () {
        setOpen(!nav.classList.contains('open'));
    });

    overlay.addEventListener('click', function () { setOpen(false); });

    // Close after tapping a menu link
    nav.addEventListener('click', function (e) {
        if (e.target.closest('a')) setOpen(false);
    });

    // Close with the Esc key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });

    // Close if the screen grows past phone size
    window.addEventListener('resize', function () {
        if (window.innerWidth > 767) setOpen(false);
    });
})();
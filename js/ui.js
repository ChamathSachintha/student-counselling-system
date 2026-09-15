// Show the current date using the browser's local calendar.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-today]').forEach(label => {
        label.textContent = new Intl.DateTimeFormat('en', {weekday:'short',month:'short',day:'numeric'}).format(new Date());
    });

    // Highlight the dashboard section selected from the sidebar.
    const links = [...document.querySelectorAll('.side-nav a[href^="#"]')];
    function highlight(id) {
        links.forEach(link => {
            const active = link.hash === '#' + id;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
    }
    links.forEach(link => link.addEventListener('click', () => highlight(link.hash.slice(1))));
    if (location.hash && links.some(link => link.hash === location.hash)) highlight(location.hash.slice(1));

    // Keep navigation in step with the section currently visible while scrolling.
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            const visible = entries.filter(entry => entry.isIntersecting).sort((a,b) => a.boundingClientRect.top-b.boundingClientRect.top);
            if (visible.length) highlight(visible[0].target.id);
        }, {rootMargin:'-90px 0px -55% 0px', threshold:0});
        links.forEach(link => {
            const section = document.getElementById(link.hash.slice(1));
            if (section) observer.observe(section);
        });
    }
});

const mobileNavigationToggle = document.getElementById('mobileNavigationToggle');
const mobileNavigation = document.getElementById('mobileNavigation');

if (mobileNavigationToggle && mobileNavigation) {
    const closeMobileNavigation = () => {
        mobileNavigation.classList.add('hidden');
        mobileNavigationToggle.setAttribute('aria-expanded', 'false');
        mobileNavigationToggle.setAttribute('aria-label', 'Open navigation menu');
        mobileNavigationToggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    };

    mobileNavigationToggle.addEventListener('click', () => {
        const isExpanded = mobileNavigationToggle.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
            closeMobileNavigation();
            return;
        }

        mobileNavigation.classList.remove('hidden');
        mobileNavigationToggle.setAttribute('aria-expanded', 'true');
        mobileNavigationToggle.setAttribute('aria-label', 'Close navigation menu');
        mobileNavigationToggle.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
    });

    mobileNavigation.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('a')) {
            closeMobileNavigation();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileNavigation();
        }
    });
}

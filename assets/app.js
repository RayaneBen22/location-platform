document.documentElement.classList.add('js');

const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#main-nav');

menuButton?.addEventListener('click', () => {
    const expanded = menuButton.getAttribute('aria-expanded') !== 'true';
    menuButton.setAttribute('aria-expanded', String(expanded));
    navigation?.classList.toggle('is-open', expanded);
});

navigation?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        menuButton?.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
    });
});

document.querySelectorAll('[data-model-filter]').forEach((modelSelect) => {
    const brandSelect = document.querySelector('[data-brand-filter]');
    if (!brandSelect) return;
    const updateModels = () => {
        Array.from(modelSelect.options).forEach((option) => {
            const hidden = option.value !== '' && brandSelect.value !== '' && option.dataset.brand !== brandSelect.value;
            option.hidden = hidden;
            option.disabled = hidden;
            if (hidden && option.selected) modelSelect.value = '';
        });
    };
    brandSelect.addEventListener('change', updateModels);
    updateModels();
});

document.querySelectorAll('[data-booking-estimate]').forEach((form) => {
    const start = form.querySelector('[name="start"]');
    const end = form.querySelector('[name="end"]');
    const result = form.querySelector('[data-estimate-result]');
    let controller;
    if (!start || !end || !result) return;
    const update = async () => {
        controller?.abort();
        if (start.value) {
            const minimum = new Date(start.value + 'T00:00:00Z');
            minimum.setUTCDate(minimum.getUTCDate() + 1);
            end.min = minimum.toISOString().slice(0, 10);
        }
        if (!start.value || !end.value) return;
        controller = new AbortController();
        result.textContent = 'Vérification des dates…';
        const url = new URL(form.dataset.availabilityUrl, window.location.origin);
        url.searchParams.set('id', form.dataset.vehicleId);
        url.searchParams.set('start', start.value);
        url.searchParams.set('end', end.value);
        try {
            const response = await fetch(url, {signal: controller.signal, headers: {'Accept': 'application/json'}});
            const data = await response.json();
            result.textContent = data.message;
        } catch (error) {
            if (error.name !== 'AbortError') result.textContent = 'Le prix et la disponibilité seront vérifiés à la confirmation.';
        }
    };
    start.addEventListener('change', update);
    end.addEventListener('change', update);
    update();
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

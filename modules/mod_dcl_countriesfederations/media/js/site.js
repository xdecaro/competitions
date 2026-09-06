(() => {
    'use strict';

    const normalize = (value) => (value || '').toLocaleLowerCase().trim();

    document.querySelectorAll('.dcl-cf').forEach((root) => {
        const search = root.querySelector('[data-dcl-cf-search]');
        const items = [...root.querySelectorAll('[data-dcl-cf-item]')];
        const empty = root.querySelector('[data-dcl-cf-empty]');

        if (!search || !items.length) {
            return;
        }

        const applyFilter = () => {
            const term = normalize(search.value);
            let visible = 0;

            items.forEach((item) => {
                const matches = term === '' || normalize(item.dataset.search).includes(term);
                item.hidden = !matches;
                visible += matches ? 1 : 0;
            });

            if (empty) {
                empty.hidden = visible !== 0;
            }
        };

        search.addEventListener('input', applyFilter, { passive: true });
    });
})();

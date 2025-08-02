/**
 * Города: AJAX-загрузка с поиском и пагинацией.
 * Подключать через wp_enqueue_script() с локализованным объектом cities_ajax_obj.
 */

(function ($) {
    const $tableBody = $('#cities-table tbody');
    const $countDisplay = $('#cities-count');
    const $pagination = $('#cities-pagination');
    const $searchInput = $('#city-search');

    let currentPage = 1;
    const perPage = 20;

    const debounce = (fn, delay) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), delay);
        };
    };

    const renderRows = (cities, page) => {
        return cities.map((city, i) => `
            <tr>
                <td>${(page - 1) * perPage + i + 1}</td>
                <td>${city.country}</td>
                <td>${city.city}</td>
                <td>${city.temperature}</td>
            </tr>
        `).join('');
    };

    const renderPagination = (totalPages, query) => {
        if (totalPages <= 1) return;

        let html = '';

        if (currentPage > 1)
            html += `<span class="page-link" data-page="${currentPage - 1}">Previous</span>`;

        for (let i = 1; i <= totalPages; i++)
            html += `<span class="page-link ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</span>`;

        if (currentPage < totalPages)
            html += `<span class="page-link" data-page="${currentPage + 1}">Next</span>`;

        $pagination.html(html);

        // Навешиваем события
        $pagination.find('.page-link').on('click', function () {
            const newPage = parseInt($(this).data('page'));
            loadCities($searchInput.val().trim(), newPage);
        });
    };

    const loadCities = (query = '', page = 1) => {
        currentPage = page;

        $tableBody.html(`
            <tr>
                <td colspan="4">
                    <div class="loading-inline">
                        <div class="spinner"></div><span>Loading...</span>
                    </div>
                </td>
            </tr>
        `);
        $pagination.empty();

        $.post(cities_ajax_obj.ajax_url, {
            action: 'load_cities',
            security: cities_ajax_obj.nonce,
            search: query,
            page,
            per_page: perPage
        }).done(response => {
            if (response.success && response.data.cities.length) {
                const { cities, total_pages, total_items } = response.data;

                $tableBody.html(renderRows(cities, page));
                $countDisplay.text(`Total: ${total_items} records`);
                renderPagination(total_pages, query);
            } else {
                $tableBody.html('<tr><td colspan="4">No results found</td></tr>');
                $countDisplay.text('Total: 0 records');
            }
        }).fail(() => {
            $tableBody.html('<tr><td colspan="4">Error loading data</td></tr>');
            $countDisplay.text('Total: 0 records');
        });
    };

    // Обработчик поиска с debounce
    $searchInput.on('input', debounce(() => {
        loadCities($searchInput.val().trim(), 1);
    }, 300));

    // Первая загрузка
    loadCities();
})(jQuery);

/* ================================================================
 * POS Category / Brand Live Search Filter
 * Activates the .live-filter inputs in the category and brand panels.
 * No PHP dependencies — pure client-side.
 * ================================================================ */

$(document).ready(function () {

    /**
     * Generic live-filter: shows/hides elements matched by data-target
     * as the user types in any .live-filter input.
     */
    $(document).on('keyup input', '.live-filter', function () {
        var searchText = $(this).val().toLowerCase().trim();
        var target     = $(this).data('target');

        $(target).each(function () {
            var label = $(this).find('p, span, .category-name, .brand-name').first().text().toLowerCase();
            if (!label) {
                label = $(this).text().toLowerCase();
            }
            if (label.indexOf(searchText) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });

    /* Clear filter when the panel is closed / tab switches */
    $(document).on('hidden.bs.tab shown.bs.tab', '[data-toggle="tab"]', function () {
        $('.live-filter').val('').trigger('input');
    });

});

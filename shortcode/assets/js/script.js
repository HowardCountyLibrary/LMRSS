/* global jQuery, featuredEventsAjax */
(function ($) {
    'use strict';

    // BUG FIX: itemsPerPage, allData, and currentPage were declared inside loadEvents()
    // but referenced by renderPage() and renderPagination() as if they were globals.
    // Solution: each shortcode widget gets its own closure-scoped state via initWidget().

    const ITEMS_PER_PAGE = 6;

    function initWidget(cardsEl) {
        const $cards      = $(cardsEl);
        const uid         = $cards.attr('id').replace('-cards', '');
        const $pagination = $('#' + uid + '-pagination');

        // Read params stored on the cards container by the shortcode
        const params = {
            action   : 'get_featured_events',
            nonce    : featuredEventsAjax.nonce,
            start    : $cards.data('start')    || '',
            end      : $cards.data('end')      || '',
            type     : $cards.data('type')     || '',
            age      : $cards.data('age')      || '',
            branch   : $cards.data('branch')   || '',
            quantity : $cards.data('quantity') || 36,
        };

        // Per-widget state (no globals needed)
        let allData     = [];
        let currentPage = 1;

        // ---------------------------------------------------------------
        // Fetch
        // ---------------------------------------------------------------
        var request = $.ajax({
            dataType     : 'json',
            contentType  : 'application/json; charset=utf-8',
            type         : 'GET',
            url          : featuredEventsAjax.ajaxUrl,
            data         : params,
        });

        request.done(function (response) {
            $cards.empty();

            if ( ! response.success ) {
                $cards.append(
                    "<div class='alert alert-danger'>Error: " +
                    ( response.data && response.data.message ? response.data.message : 'Unknown error.' ) +
                    "</div>"
                );
                return;
            }

            allData = response.data || [];

            if ( allData.length === 0 ) {
                $cards.append("<p>No upcoming events found.</p>");
                return;
            }

            renderPage(currentPage);
        });

        request.fail(function (jqXHR, textStatus, errorThrown) {
            $cards.empty();
            $cards.append(
                "<div class='alert alert-danger'>AJAX Error: " +
                textStatus + ' ' + errorThrown +
                "<br>Response Text: " + jqXHR.responseText +
                "</div>"
            );
        });

        // ---------------------------------------------------------------
        // Render helpers  (same logic as your original)
        // ---------------------------------------------------------------
        function renderPage(page) {
            currentPage       = page;
            var startIdx      = (page - 1) * ITEMS_PER_PAGE;
            var pageItems     = allData.slice(startIdx, startIdx + ITEMS_PER_PAGE);

            $cards.empty();

            pageItems.forEach(function (element) {
                var branchLabel = Array.isArray(element.branch) ? element.branch.join(', ') : element.branch;

                $cards.append(
                    "<a href='" + element.url + "' target='_blank' class='featured-branch-events--card'>" +
                        "<div class='featured-branch-events--card--inner'>" +
                            "<div class='featured-branch-events--card--meta'>" +
                                "<span>" + element.start_date + "</span>" +
                                "<div class='separator'></div>" +
                                "<span>" + element.start_time + " - " + element.end_time + "</span>" +
                            "</div>" +
                            "<h4 class='featured-branch-events--card--heading'>" + element.title + "</h4>" +
                            "<div class='featured-branch-events--card--footer'>" +
                                "<span class='featured-branch-events--card--location'>" + branchLabel + "</span>" +
                                "<span class='featured-branch-events--card--category " + element.age_group_class + "'>" + element.age_group_label + "</span>" +
                            "</div>" +
                        "</div>" +
                    "</a>"
                );
            });

            renderPagination();
        }

        function renderPagination() {
            var totalPages = Math.ceil(allData.length / ITEMS_PER_PAGE);

            $pagination.empty();

            if (totalPages <= 1) { return; }

            var $ul = $('<ul class="page-numbers"></ul>');

            // Prev
            var $prev = $('<li><a class="prev page-numbers">&lt;</a></li>');
            if (currentPage === 1) {
                $prev.find('a').addClass('disabled').removeAttr('href');
            }
            $ul.append($prev);

            // Page numbers
            getPageRange(currentPage, totalPages).forEach(function (p) {
                if (p === '...') {
                    $ul.append('<li><span class="page-numbers dots">…</span></li>');
                } else if (p === currentPage) {
                    $ul.append('<li><span class="page-numbers current" aria-current="page">' + p + '</span></li>');
                } else {
                    var $li = $('<li><a class="page-numbers">' + p + '</a></li>');
                    $li.find('a').data('page', p);
                    $ul.append($li);
                }
            });

            // Next
            var $next = $('<li><a class="next page-numbers">&gt;</a></li>');
            if (currentPage === totalPages) {
                $next.find('a').addClass('disabled').removeAttr('href');
            }
            $ul.append($next);

            $pagination.append($ul);
        }

        // ---------------------------------------------------------------
        // Pagination clicks – scoped to this widget's $pagination element
        // ---------------------------------------------------------------
        $pagination.on('click', '.page-numbers:not(.prev):not(.next):not(.dots):not(.current)', function () {
            var page = $(this).data('page');
            if (page) {
                currentPage = page;
                renderPage(currentPage);
            }
        });

        $pagination.on('click', '.prev.page-numbers:not(.disabled)', function () {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        });

        $pagination.on('click', '.next.page-numbers:not(.disabled)', function () {
            var totalPages = Math.ceil(allData.length / ITEMS_PER_PAGE);
            if (currentPage < totalPages) {
                currentPage++;
                renderPage(currentPage);
            }
        });
    }

    // -----------------------------------------------------------------------
    // Utilities
    // -----------------------------------------------------------------------
    function getPageRange(current, total) {
        var delta  = 1;
        var range  = [];
        var result = [];
        var last;

        for (var i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
                range.push(i);
            }
        }

        range.forEach(function (i) {
            if (last) {
                if      (i - last === 2) { result.push(last + 1); }
                else if (i - last >  2) { result.push('...'); }
            }
            result.push(i);
            last = i;
        });

        return result;
    }

    // -----------------------------------------------------------------------
    // Bootstrap – one initWidget() call per shortcode on the page
    // -----------------------------------------------------------------------
    $(document).ready(function () {
        $('[id$="-cards"].featured-branch-events--cards').each(function () {
            initWidget(this);
        });
    });

}(jQuery));

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eventGet</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="featured-branch-events.css">
    <style>
        /* spinner css */

        .event-spinner {
            display: flex;
            justify-content: center;
            flex-direction: row;
            flex-wrap: nowrap;
            align-content: center;
            align-items: center;
            grid-column: 2;
        }
        .lds-ring-event {
            /* change color here */
            color: #ffffff;
        }
    
        .lds-ring-event,
        .lds-ring-event div {
            box-sizing: border-box;
        }
        .lds-ring-event {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }
        .lds-ring-event div {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 64px;
            height: 64px;
            margin: 8px;
            border: 8px solid currentColor;
            border-radius: 50%;
            animation: lds-ring-event 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            border-color: currentColor transparent transparent transparent;
        }
        .lds-ring-event div:nth-child(1) {
            animation-delay: -0.45s;
        }
        .lds-ring-event div:nth-child(2) {
            animation-delay: -0.3s;
        }
        .lds-ring-event div:nth-child(3) {
            animation-delay: -0.15s;
        }
        @keyframes lds-ring-event {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="featured-branch-events--background">
        <div class="block-container--xlarge">
            <div class="block__inner">
                <div class="heading has-categories">
                    <h2>Featured Classes &amp; Events</h2>
                    
                    <div class="heading--filters">
                        <select id="categoriesMobileOnly" aria-label="Select Classes">
                            <option value="all" selected="selected">All Classes</option>
                            <option value="Children">Children’s Classes</option>
                            <option value="Teens">Teen Classes</option>
                            <option value="Adults">Adult Classes</option>
                            <option value="Culture &amp; Language">Culture &amp; Language</option>
                            <option value="DIY &amp; Makerspace">DIY &amp; Makerspace</option>
                            <option value="Parents &amp; Educators">Parents &amp; Educators</option>
                        </select>
                    </div>
                </div>
                <div class="category-filters">
                    <a href="https://howardcounty.librarycalendar.com/events/upcoming?branches=97" class="button--secondary view-all-events" target="_blank"><span>View Calendar</span></a>
                </div>
                <div id="featured-branch-events--cards__container" class="featured-branch-events--cards">
                    
                </div>
                <div id="featured-branch-events--pagination" class="featured-branch-events--pagination">
                    
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        $(document).ready(function() {
            const itemsPerPage = 6;
            let currentPage = 1;
            let allData = [];

            $('#featured-branch-events--cards__container').append(
                '<div class="event-spinner">' +
                    '<div class="lds-ring-event"><div></div><div></div><div></div><div></div></div>' +
                '</div>'
            );

            var request = $.ajax({
                dataType: "json",
                contentType: "application/json; charset=utf-8",
                type: "GET",
                url: "eventGet.php",
            });

            request.done(function(data) {
                $('#featured-branch-events--cards__container').empty();
                try {
                    allData = Object.values(data);
                    renderPage(currentPage);
                    renderPagination();
                } catch (e) {
                    $("#featured-branch-events--cards__container").append(
                        "<div class='alert alert-danger'>JSON Parsing Error: " + e.message + "</div>"
                    );
                }
            });

            request.fail(function(jqXHR, textStatus, errorThrown) {
                $("#featured-branch-events--cards__container").empty();
                $("#featured-branch-events--cards__container").append(
                    "<div class='alert alert-danger'>AJAX Error: " +
                        textStatus + errorThrown + "</br>Response Text: " + jqXHR.responseText +
                    "</div>"
                );
            });

            function renderPage(page) {
                const start = (page - 1) * itemsPerPage;
                const pageItems = allData.slice(start, start + itemsPerPage);

                $("#featured-branch-events--cards__container").empty();

                pageItems.forEach(function(element) {
                    $("#featured-branch-events--cards__container").append(
                        "<a href='" + element.url + "' target='_blank' class='featured-branch-events--card'>" +
                            "<div class='featured-branch-events--card--inner'>" +
                                "<div class='featured-branch-events--card--meta'>" +
                                    "<span>" + element.start_date + "</span>" +
                                    "<div class='separator'></div>" +
                                    "<span>" + element.start_time + " - " + element.end_time + "</span>" +
                                "</div>" +
                                "<h4 class='featured-branch-events--card--heading'>" + element.title + "</h4>" +
                                "<div class='featured-branch-events--card--footer'>" +
                                    "<span class='featured-branch-events--card--location'>" + element.branch + "</span>" +
                                    "<span class='featured-branch-events--card--category " + element.age_group_class + "'>" + element.age_group_label + "</span>" +
                                "</div>" +
                            "</div>" +
                        "</a>"
                    );
                });

                renderPagination();
            }

            function renderPagination() {
                const totalPages = Math.ceil(allData.length / itemsPerPage);

                $('.featured-branch-events--pagination').remove();

                const $ul = $('<ul class="page-numbers"></ul>');

                const $prev = $('<li><a class="prev page-numbers"">&lt;</a></li>');
                if (currentPage === 1) {
                    $prev.find('a').addClass('disabled').removeAttr('href');
                }
                $ul.append($prev);
                const pages = getPageRange(currentPage, totalPages);

                pages.forEach(function(p) {
                    if (p === '...') {
                        $ul.append('<li><span class="page-numbers dots">…</span></li>');
                    } else if (p === currentPage) {
                        $ul.append('<li><span class="page-numbers current" aria-current="page">' + p + '</span></li>');
                    } else {
                        const $li = $('<li><a class="page-numbers">' + p + '</a></li>');
                        $li.find('a').data('page', p);
                        $ul.append($li);
                    }
                });

                const $next = $('<li><a class="next page-numbers">&gt;</a></li>');
                if (currentPage === totalPages) {
                    $next.find('a').addClass('disabled').removeAttr('href');
                }
                $ul.append($next);

                const $pagination = $('<div class="featured-branch-events--pagination"></div>').append($ul);
                $('#featured-branch-events--cards__container').after($pagination);
            }

            function getPageRange(current, total) {
                const delta = 1;
                const range = [];
                const result = [];
                let last;

                for (let i = 1; i <= total; i++) {
                    if (
                        i === 1 ||
                        i === total ||
                        (i >= current - delta && i <= current + delta)
                    ) {
                        range.push(i);
                    }
                }

                range.forEach(function(i) {
                    if (last) {
                        if (i - last === 2) {
                            result.push(last + 1);
                        } else if (i - last > 2) {
                            result.push('...');
                        }
                    }
                    result.push(i);
                    last = i;
                });

                return result;
            }
            $(document).on('click', '.featured-branch-events--pagination .page-numbers:not(.prev):not(.next):not(.dots):not(.current)', function() {
                const page = $(this).data('page');
                if (page) {
                    currentPage = page;
                    renderPage(currentPage);
                }
            });
            $(document).on('click', '.featured-branch-events--pagination .prev.page-numbers:not(.disabled)', function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderPage(currentPage);
                }
            });
            $(document).on('click', '.featured-branch-events--pagination .next.page-numbers:not(.disabled)', function() {
                const totalPages = Math.ceil(allData.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPage(currentPage);
                }
            });
        });
    </script>
</body>
</html>
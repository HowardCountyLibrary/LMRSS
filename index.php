<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eventGet</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <style>
        /* spinner css */

        .spinner {
            display: flex;
            justify-content: center;
            flex-direction: row;
            flex-wrap: nowrap;
            align-content: center;
            align-items: center;
        }
        .lds-ring {
            /* change color here */
            color: #007dc5;
        }
    
        .lds-ring,
        .lds-ring div {
            box-sizing: border-box;
        }
        .lds-ring {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }
        .lds-ring div {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 64px;
            height: 64px;
            margin: 8px;
            border: 8px solid currentColor;
            border-radius: 50%;
            animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            border-color: currentColor transparent transparent transparent;
        }
        .lds-ring div:nth-child(1) {
            animation-delay: -0.45s;
        }
        .lds-ring div:nth-child(2) {
            animation-delay: -0.3s;
        }
        .lds-ring div:nth-child(3) {
            animation-delay: -0.15s;
        }
        @keyframes lds-ring {
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
    <div class="block-container--xlarge">
        <div id="results">
            
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#results').append(
                '<div class="spinner">'+
                    '<div class="lds-ring"><div></div><div></div><div></div><div></div></div>'+
                '</div>'
            );

            var request = $.ajax({
                dataType: "json",
                contentType: "application/json; charset=utf-8",
                type: "GET",
                url: "eventGet.php"
            });
            
            request.done(function(data) {
                $('#results').empty();
                try {
                    Object.entries(data).forEach(([key, element]) => {
                        $("#results").append(



                            element.title +
                            element.url +
                            element.start_date +
                            element.start_time +
                            element.end_time +
                            element.branch +
                            element.program_type +
                            element.age_group + "<br> "
                        );
                    });
                } catch (e) {
                    $("#results").append("<div class='alert alert-danger'>JSON Parsing Error: "+ e.message +"</div>");
                }
                
            });
            
            request.fail(function (jqXHR, textStatus, errorThrown) {
                $("#results").empty();
                $("#results").append("<div class='alert alert-danger'>AJAX Error: " + 
                    textStatus + errorThrown + "</br>Response Text: " + jqXHR.responseText + "</div>");
            });
        });
    </script>
</body>
</html>
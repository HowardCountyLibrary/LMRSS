<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

//start date, "today" also works
$start =  date('Y-m-d');

//end date
$end = NULL;

//public program types, does not include internal categories
/*
    326     => Adult English Classes,
    93      => Art, Movies & Music,
    59      => Auther & Literacy Events,
    60      => Book Clubs,
    96      => Career, Business & Finance,
    61      => College & Trade Schools,
    92      => Community Events,
    67      => Crafts, Fun & Games!,
    66      => Culture & Language,
    62      => DIY & Makerspace,
    327     => Early Learning,
    63      => Gardening & Nature,
    64      => Health & Wellness,
    328     => High School Diploma,
    65      => History & Genealogy,
    332     => HiTech Classes & Events,
    358     => Library on the Go!,
    329     => Parents & Educators,
    242     => Race, Equity & Inclusion,
    105     => Tech Skills or STEAM,
*/
$type = array(62);

//age group for events, if left empty will include all
/*
    116     => Children,
    119     => Teens,
    57      => Adults,
    56      => Everyone,
*/
$age = array();

//library branches
/*
    97      => Central Branch,
    99      => East Columbia Branch,
    100     => Elkridge Branch,
    101     => Glenwood Branch,
    102     => Miller Branch,
    103     => Savage Branch,
    88      => Administrative Branch,
    305     => Mobile Branch,
    209     => Online Branch,
    89      => Off site,
*/
$branch = array(101);

//amount of results returned, use 36 for default
$quantity = 36;

$result = eventGet(
    $start,
    $end,
    $type,
    $age,
    $branch,
    $quantity,
);

$events = json_decode($result, true);

$event_results = array();

foreach ($events as $event) {
    $id = $event["id"];
    $title = $event['title'];
    $url = $event['url'];
    $start_date = $event['start_date'];

    if (date("Y-m-d", strtotime($start_date)) == date("Y-m-d")) {
        $start_date_formatted = "Today";
    }else {
        $start_date_formatted = date("D, M j", strtotime($start_date));
    }

    $start_time = date("g:i a", strtotime($start_date));

    $end_time = date("g:i a", strtotime($event['end_date'])); 

    $branch = array_values($event['branch']);
    $program_type = array_values($event['program_type']);
    $age_group_class = strtolower(array_values($event['age_group'])[0]);
    $age_group_label = array_values($event['age_group'])[0];

    $event_results[$id] = array(
        'title'         => $title,
        'url'           => $url,         
        'start_date'    => $start_date_formatted,
        'start_time'    => $start_time,
        'end_time'      => $end_time,
        'branch'        => $branch,
        'program_type'  => $program_type,
        'age_group_class'     => $age_group_class,
        'age_group_label'     => $age_group_label,   
    );
}

function eventGet($start, $end, $type, $age, $branch, $quantity)
{
    $url = 'https://howardcounty.librarycalendar.com/events/feed/json?';

    if ($start)    $url .= "start=$start&";
    if ($end)      $url .= "end=$end&";
    if (!empty($type))   $url .= 'program_types=' . implode(',', $type) . '&';
    if (!empty($age))    $url .= 'age_groups='    . implode(',', $age)  . '&';
    if (!empty($branch)) $url .= 'branches='      . implode(',', $branch) . '&';
    if ($quantity) $url .= "quantity=$quantity";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
    ]);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    curl_close($ch);
    return $result;
}

header('Content-Type: application/json');
echo json_encode($event_results, JSON_PRETTY_PRINT);
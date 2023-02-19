<?php

define("WORDLE_START_DATE", "2021-06-20");

$wordle_today = wordle_for_day(new DateTime());

function wordle_day_for($wordle_nr)
{
    return date_create_immutable(WORDLE_START_DATE)->add(
        new DateInterval("P" . ($wordle_nr - 1) . "D")
    );
}

function wordle_for_day($day)
{
    return $day->diff(date_create_immutable(WORDLE_START_DATE))->days + 1;
}

function wordles_in_week($dt)
{
    $start_dt = $dt->modify("Monday this week");
    $end_dt = $start_dt->modify("Sunday this week");
    $diff_days = $start_dt->diff($end_dt)->days;

    $start = wordle_for_day($start_dt);

    $res = [];
    for ($i = 0; $i <= $diff_days; $i++) {
        array_push($res, $start + $i);
    }

    return $res;
}

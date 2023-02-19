<?php

require_once "wordle.php";

function select_streaks_top()
{
    global $db, $wordle_today;

    $stmt = $db->prepare(
        <<<EOF
WITH lags AS (
    SELECT
        user_id,
        name,
        wordle,
        wordle - lag(wordle) OVER (PARTITION BY user_id ORDER BY wordle) AS d
    FROM
        "result"
        JOIN USER ON user_id = user.id
    WHERE
        wordle < :wordle
),
gaps AS (
    SELECT
        *,
        lag(d) OVER (PARTITION BY user_id ORDER BY wordle) > 1 AS gap
    FROM
        lags
),
ids AS (
    SELECT
        *,
        sum(gap) OVER (PARTITION BY user_id ORDER BY wordle) AS streak_id
FROM
    gaps
),
streaks AS (
    SELECT
        user_id,
        name,
        count(*) len
    FROM
        ids
    WHERE
        d = 1
    GROUP BY
        user_id,
        streak_id
),
max_streaks AS (
    SELECT
        user_id,
        name,
        max(len) len
    FROM
        streaks
    GROUP BY
        user_id
)
SELECT
    row_number() OVER (ORDER BY len DESC) AS rank,
    *
FROM
    max_streaks
ORDER BY
    rank
LIMIT 10
EOF
    );

    $stmt->bindValue(":wordle", $wordle_today);

    $result = $stmt->execute();

    $rows = [];
    while ($row = $result->fetcharray(SQLITE3_ASSOC)) {
        array_push($rows, $row);
    }

    $result->finalize();

    return $rows;
}

function bot_action_streaks()
{
    $rows = select_streaks_top();

    if (count($rows) == 0) {
        return "Inga resultat ännu!";
    }

    $table = [];

    $header = [
        EMOJI_OPEN_CIRCLE_ARROWS,
        "Wordles i sträck",
        EMOJI_TEAR_OFF_CALENDAR,
    ];

    array_push($table, implode(" ", $header));

    foreach ($rows as $row) {
        $entry = [$row["rank"], ucfirst($row["name"]), $row["len"]];

        array_push($table, implode(" ", $entry));
    }

    return implode("\n", array_slice($table, 0, 10));
}

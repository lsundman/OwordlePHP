<?php

require_once "db.php";

require_once "date.php";
require_once "emoji.php";

function select_medals_report($week = null)
{
    global $db;

    if (is_null($week)) {
        $sql = <<<EOF
WITH grouped AS (
  SELECT
      name,
      count(guesses) FILTER (WHERE guesses <= 2) diamond,
      count(guesses) FILTER (WHERE guesses = 3) "1st",
      count(guesses) FILTER (WHERE guesses = 4) "2nd",
      count(guesses) FILTER (WHERE guesses = 5) "3rd",
      count(guesses) FILTER (WHERE guesses = 6) "rock"
  FROM
      result JOIN user ON user_id = user.id
  GROUP BY
      user_id
),
counted AS (
  SELECT
      *,
      diamond + "1st" + "2nd" + "3rd" AS total
  FROM
      grouped
)
SELECT
  *
FROM
    counted
ORDER BY
  diamond DESC,
  "1st" DESC,
  "2nd" DESC,
  "3rd" DESC
EOF;
    } else {
        $week_txt = $week->format("o-\WW");
        $sql = <<<EOF
WITH grouped AS (
  SELECT
      wordle_week_for(wordle) AS week,
      name,
      count(guesses) FILTER (WHERE guesses <= 2) diamond,
      count(guesses) FILTER (WHERE guesses = 3) "1st",
      count(guesses) FILTER (WHERE guesses = 4) "2nd",
      count(guesses) FILTER (WHERE guesses = 5) "3rd",
      count(guesses) FILTER (WHERE guesses = 6) "rock"
  FROM
      result JOIN user ON user_id = user.id
  WHERE
    week = '$week_txt'
  GROUP BY
      week,
      user_id
),
counted AS (
  SELECT
      *,
      diamond + "1st" + "2nd" + "3rd" AS total
  FROM
      grouped
)
SELECT
  *
FROM
    counted
ORDER BY
  diamond DESC,
  "1st" DESC,
  "2nd" DESC,
  "3rd" DESC
EOF;
    }

    $result = $db->query($sql);

    $rows = [];
    while ($row = $result->fetcharray(SQLITE3_ASSOC)) {
        array_push($rows, $row);
    }

    $result->finalize();

    return $rows;
}

function bot_action_medals($week_dt)
{
    global $week_year_fmt;

    $rows = select_medals_report($week_dt);

    if (empty($rows)) {
        return "Inga resultat ännu!";
    }

    $table = [];

    $header = [
        EMOJI_TROPHY,
        "Medaljer",
        "(" . datefmt_format($week_year_fmt, $week_dt) . ")",
        EMOJI_TROPHY,
    ];

    array_push($table, implode(" ", $header));

    foreach ($rows as $idx => $row) {
        $name = [$idx + 1, ucfirst($row["name"]), "(" . $row["total"] . ")"];

        $result = [
            EMOJI_GEM_STONE,
            $row["diamond"],
            EMOJI_FIRST_PLACE_MEDAL,
            $row["1st"],
            EMOJI_SECOND_PLACE_MEDAL,
            $row["2nd"],
            EMOJI_THIRD_PLACE_MEDAL,
            $row["3rd"],
            EMOJI_ROCK,
            $row["rock"],
        ];

        array_push($table, implode(" ", $name));
        array_push($table, implode(" ", $result));
    }

    return implode("\n", $table);
}

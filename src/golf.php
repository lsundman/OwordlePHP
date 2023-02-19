<?php

require_once "db.php";

require_once "date.php";
require_once "emoji.php";

define("PAR", 4);

function select_golf_report($week = null)
{
    global $db, $week_fmt;

    $now = new DateTimeImmutable();

    $where = is_null($week)
        ? ""
        : "WHERE week = '" . $week->format("o-\WW") . "'";

    $sql = "
      SELECT
        wordle_week_for(wordle) AS week,
        user_id,
        name,
        json_group_object(wordle, guesses) AS guesses
      FROM result JOIN user ON user_id = user.id
      $where
      GROUP BY 1, user_id
      ORDER BY 1 DESC
    ";

    $result = $db->query($sql);

    $weeks = [];
    while ($row = $result->fetcharray(SQLITE3_ASSOC)) {
        $week = &$weeks[$row["week"]];

        $dt = new DateTimeImmutable($row["week"]);

        if (!isset($week["dt"])) {
            $week["dt"] = $dt;
        }

        if (!isset($week["title"])) {
            $week["title"] = datefmt_format($week_fmt, $dt);
        }

        if (!isset($week["wordles"])) {
            $week["wordles"] = wordles_in_week($dt);
        }

        $row["guesses"] = json_decode($row["guesses"], true);

        $week_done = $now > $week["dt"]->modify("Sunday this week");

        $res = [
            "name" => $row["name"],
            "user_id" => $row["user_id"],
            "specials" => [0, 0, 0],
            "guesses" => $row["guesses"],
            "guesses_txt" => [],
        ];

        $dnf = &$res["dnf"];
        $guesses = &$res["guesses"];
        $strokes = &$res["strokes"];
        $under_par = &$res["under_par"];
        $specials = &$res["specials"];

        $dnf = max(array_keys($guesses)) != max($week["wordles"]);

        foreach ($week["wordles"] as $wordle) {
            $guess = &$guesses[$wordle];

            if (!is_null($guess)) {
                if ($guess < PAR) {
                    $specials[$guess - 1]++;
                }

                $txt = $guess < 7 ? strval($guess) : "X";
            } else {
                $guess = $week_done ? 7 : 0;
                $txt = $week_done ? "-" : "";
            }

            $res["guesses_txt"][] = $txt;
        }

        $strokes = array_sum($guesses);
        $res["strokes_txt"] = $dnf ? "($strokes)" : strval($strokes);

        $under_par = $strokes - PAR * 7;
        $res["under_par_txt"] = $dnf ? "" : sprintf("%+d", $under_par);

        $res["specials_txt"] = implode([
            str_repeat(EMOJI_FLAG_IN_HOLE, $specials[0]),
            str_repeat(EMOJI_EAGLE, $specials[1]),
            str_repeat(EMOJI_BABY_CHICK, $specials[2]),
        ]);

        ksort($res);

        $week["entries"][] = $res;

        unset($week);
    }

    $result->finalize();

    foreach ($weeks as &$week) {
        array_multisort(
            array_column($week["entries"], "dnf"),
            SORT_ASC,
            array_column($week["entries"], "strokes"),
            SORT_ASC,
            array_column($week["entries"], "specials"),
            SORT_DESC,
            array_column($week["entries"], "name"),
            SORT_ASC,
            $week["entries"]
        );
    }

    return $weeks;
}
function select_golf_top($weeks)
{
    $rows = [];
    foreach ($weeks as $week) {
        foreach ($week["entries"] as $entry) {
            $ack = &$rows[$entry["name"]];
            if (!isset($ack)) {
                $ack = [0, 0, 0];
            }

            $keys = array_keys($ack);

            foreach ($entry["specials"] as $idx => $val) {
                $ack[$keys[$idx]] += $val;
            }
        }
    }

    $result = [];
    foreach ($rows as $name => $row) {
        $result[] = [
            "name" => $name,
            "holes_in_one" => $row[0],
            "eagles" => $row[1],
            "birdies" => $row[2],
            "total" => array_sum($row),
        ];
    }

    array_multisort(
        array_column($result, "total"),
        SORT_DESC,
        array_column($result, "holes_in_one"),
        SORT_DESC,
        array_column($result, "eagles"),
        SORT_DESC,
        array_column($result, "birdies"),
        SORT_DESC,
        $result
    );

    $result = array_slice($result, 0, 10);

    return $result;
}

function select_golf_placements($weeks)
{
    $rows = [];
    foreach ($weeks as $week) {
        foreach ($week["entries"] as $idx => $entry) {
            if ($idx > 2) {
                break;
            }

            $ack = &$rows[$entry["name"]];
            if (!isset($ack)) {
                $ack = [0, 0, 0];
            }

            $ack[$idx]++;
        }
    }

    $result = [];
    foreach ($rows as $name => $row) {
        $result[] = [
            "name" => $name,
            "1st" => $row[0],
            "2nd" => $row[1],
            "3rd" => $row[2],
        ];
    }

    array_multisort(
        array_column($result, "1st"),
        SORT_DESC,
        array_column($result, "2nd"),
        SORT_DESC,
        array_column($result, "3rd"),
        SORT_DESC,
        array_column($result, "name"),
        SORT_DESC,
        $result
    );

    $result = array_slice($result, 0, 10);

    return $result;
}

function bot_action_golf($week_dt)
{
    global $week_year_fmt;

    $report = select_golf_report($week_dt);

    if (empty($report)) {
        return "Inga resultat ännu!";
    }

    $rows = $report[array_keys($report)[0]]["entries"];

    $table = [];

    $header = [
        EMOJI_FLAG_IN_HOLE,
        "Golf",
        "(" . datefmt_format($week_year_fmt, $week_dt) . ")",
        EMOJI_FLAG_IN_HOLE,
    ];

    array_push($table, implode(" ", $header));

    foreach ($rows as $idx => $row) {
        $res = [
            $idx + 1,
            $row["name"],
            implode($row["guesses_txt"]),
            $row["strokes_txt"],
        ];

        if (!$row["dnf"]) {
            $res[] = $row["under_par_txt"];
        }

        $res[] = $row["specials_txt"];

        array_push($table, implode(" ", $res));
    }

    return implode("\n", $table);
}

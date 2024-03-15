<?php

require_once "db.php";

require_once "wordle.php";
require_once "emoji.php";
require_once "date.php";

require_once "golf.php";
require_once "medals.php";
require_once "streaks.php";

function handle_message($msg)
{
    global $db;

    $re_action = "/^\/(?P<action>\w+)\s?(?P<week>\d{0,2})$/";

    if (str_starts_with($msg["text"], "Wordle")) {
        $line = explode("\n", $msg["text"], 2)[0];

        $line = str_replace("\u{00a0}", "", $line);
        $line = str_replace(",", "", $line);
        $line = str_replace(".", "", $line);

        $arr = explode(" ", $line);

        $gspec = $arr[count($arr) - 1];

        $wordle = intval($arr[1]);
        $guesses = $gspec[0] == "X" ? 7 : intval($gspec[0]);
        $hard_mode = str_contains($gspec, "*");

        $db->exec("BEGIN;");

        $stmt = $db->prepare(
            "INSERT INTO USER (id, name) VALUES (:user_id, :user_name)"
        );

        $stmt->bindValue(":user_id", $msg["user_id"]);
        $stmt->bindValue(":user_name", $msg["user_name"]);

        $result = $stmt->execute();
        if (!$result) {
            syslog(LOG_ERR, "Could not insert user.");
        }

        $stmt = $db->prepare(
            "INSERT INTO result (wordle, user_id, guesses, hard_mode)" .
                "VALUES (:wordle, :user_id, :guesses, :hard_mode)"
        );

        $stmt->bindValue(":wordle", $wordle);
        $stmt->bindValue(":user_id", $msg["user_id"]);
        $stmt->bindValue(":guesses", $guesses);
        $stmt->bindValue(":hard_mode", $hard_mode);

        $result = $stmt->execute();
        if (!$result) {
            syslog(LOG_ERR, "Could not insert result.");
        }

        $db->exec("COMMIT;");
    } else {
        $week_dt = date_create_immutable()->modify("Monday this week");

        if (!preg_match($re_action, $msg["text"], $matches)) {
            return "";
        }

        syslog(LOG_DEBUG, "Matches: \n" . var_export($matches, true));

        if (isset($matches["week"])) {
            $year = $week_dt->format("Y");
            $week = intval($matches["week"]);
            $new_dt = date_create_immutable(
                $year . "-W" . sprintf("%02d", $week)
            );

            if ($new_dt) {
                $week_dt = $new_dt;
            }
        }

        syslog(LOG_DEBUG, "Week: " . $week_dt->format(DATE_ATOM));

        switch ($matches["action"]) {
            case "medaljer":
                return bot_action_medals($week_dt);
            case "golf":
                return bot_action_golf($week_dt);
            case "streaks":
                return bot_action_streaks();
            case "tabeller":
                $port = &$_SERVER["SERVER_PORT"];
                return $_SERVER["REQUEST_SCHEME"] .
                    "://" .
                    $_SERVER["SERVER_NAME"] .
                    (in_array($port, [80, 443]) ? "" : ":{$port}") .
                    "/reports.php?tok=" .
                    $_SERVER["REPORTS_KEY"];
        }
    }

    return "";
}

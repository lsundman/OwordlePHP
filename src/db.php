<?php

$db_path = realpath(getenv("STATE_DIRECTORY")) . "/wordles.sqlite";

$db_exists = file_exists($db_path);

$db = new SQLite3($db_path);

$db->busyTimeout(30000);

$db->exec('PRAGMA journal_mode = wal;');

$db->createFunction(
    "wordle_week_for",
    function ($wordle) {
        return wordle_day_for($wordle)->format("o-\WW");
    },
    1,
    SQLITE3_DETERMINISTIC
);

if (!$db_exists) {
    $db->exec(
        <<<EOF
BEGIN;

  -- Users
CREATE TABLE user (
    id integer PRIMARY KEY ON CONFLICT REPLACE,
    name text
);

-- Results
CREATE TABLE result (
    wordle integer NOT NULL,
    user_id integer NOT NULL,
    guesses integer NOT NULL,
    hard_mode integer NOT NULL,
    PRIMARY KEY (wordle, user_id) ON CONFLICT REPLACE,
    FOREIGN KEY (user_id) REFERENCES user(id)
);

CREATE INDEX user_wordle_idx ON result(user_id, wordle);

COMMIT;
EOF
    );
}

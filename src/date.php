<?php

date_default_timezone_set("Europe/Helsinki");

$dt_long_fmt = datefmt_create(
    "fi_FI",
    IntlDateFormatter::SHORT,
    IntlDateFormatter::SHORT,
    "Europe/Helsinki",
    IntlDateFormatter::GREGORIAN
);

$date_fmt = datefmt_create(
    "fi_FI",
    IntlDateFormatter::SHORT,
    IntlDateFormatter::SHORT,
    "Europe/Helsinki",
    IntlDateFormatter::GREGORIAN,
    "dd.MM.YYYY"
);

$month_fmt = datefmt_create(
    "sv_SE",
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    "Europe/Helsinki",
    IntlDateFormatter::GREGORIAN,
    "MMMM yyyy"
);

$week_fmt = datefmt_create(
    "sv_SE",
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    "Europe/Helsinki",
    IntlDateFormatter::GREGORIAN,
    "'vecka' w"
);

$week_year_fmt = datefmt_create(
    "sv_SE",
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    "Europe/Helsinki",
    IntlDateFormatter::GREGORIAN,
    "'v.' w - YYYY"
);

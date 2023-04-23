<?php
require_once "../src/db.php";

require_once "../src/date.php";
require_once "../src/wordle.php";

require_once "../src/golf.php";
require_once "../src/medals.php";
require_once "../src/streaks.php";

$now = new DateTime();

$golf_weeks = select_golf_report();

krsort($golf_weeks);

$tables = [];
foreach ($golf_weeks as $week) {
    $dt = $week["dt"];
    $month = &$tables[$dt->format("o-m")];

    if (!isset($month["title"])) {
        $month["title"] = datefmt_format($month_fmt, $dt);
    }

    $month["entries"][] = $week;

    unset($month);
}

$specials = [
    [
        "title" => "Medaljligan",
        "entries" => array_slice(select_medals_report(), 0, 10),
        "header" => [
            "name" => "",
            "diamond" => EMOJI_GEM_STONE,
            "1st" => EMOJI_FIRST_PLACE_MEDAL,
            "2nd" => EMOJI_SECOND_PLACE_MEDAL,
            "3rd" => EMOJI_THIRD_PLACE_MEDAL,
            "rock" => EMOJI_ROCK,
            "total" => "Tot.",
        ],
    ],
    [
        "title" => "Fågelligan",
        "entries" => select_golf_top($golf_weeks),
        "header" => [
            "name" => "",
            "holes_in_one" => EMOJI_FLAG_IN_HOLE,
            "eagles" => EMOJI_EAGLE,
            "birdies" => EMOJI_BABY_CHICK,
            "total" => "Tot.",
        ],
    ],
    [
        "title" => "Golfplaceringar",
        "entries" => select_golf_placements($golf_weeks),
        "header" => [
            "name" => "",
            "1st" => "1:a",
            "2nd" => "2:a",
            "3rd" => "3:e",
        ],
    ],
    [
        "title" => "Wordles i sträck",
        "entries" => select_streaks_top(),
        "header" => [
            "name" => "",
            "len" => "Antal",
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="sv">
<head>
  <title>Ordlympiska spelen</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="static/styles.css" rel="stylesheet">
  <link rel="icon" href="static/favicon.ico">
  <link rel="apple-touch-icon" href="static/apple-touch-icon.png">
</head>
<body>
  <header>
    <img id="rings" alt="logo" src="static/owordle.svg">
    <h1>Ordlympiska spelen</h1>
  </header>
  <main>
    <section class="content">
      <div class="card placement">
        <h3>Ställning</h3>
        <div class="content">
        <?php foreach ($specials as $spnum => $table): ?>
          <table>
            <caption><?= $table["title"] ?></caption>
            <thead>
              <tr>
              <?php if ($spnum == 0): ?>
              <td></td>
              <?php endif; ?>
              <?php foreach ($table["header"] as $col): ?>
                <td><?= $col ?></td>
              <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($table["entries"] as $idx => $row): ?>
              <tr>
              <?php if ($spnum == 0): ?>
              <td class="rank"><?= $idx + 1 ?></td>
              <?php endif; ?>
              <?php foreach (array_keys($table["header"]) as $key): ?>
                <td><?= $row[$key] ?></td>
              <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endforeach; ?>
        </div>
      </div>
    <?php foreach ($tables as $month): ?>
      <div class="card golf">
        <h3><?= ucfirst($month["title"]) ?></h3>
        <div class="content">
        <?php foreach ($month["entries"] as $week): ?>
          <div>
            <table>
              <caption><?= ucfirst($week["title"]) ?></caption>
              <colgroup span="2"></colgroup>
              <colgroup span="7"></colgroup>
              <colgroup span="3"></colgroup>
              <thead>
                <tr>
                  <td></td>
                  <td></td>
                  <?php foreach ($week["wordles"] as $key => $wordle) {
                      $txt = datefmt_format(
                          $date_fmt,
                          $week["wordles_dates"][$key]
                      );
                      echo "<td class='score' title='$txt'>", $wordle, "</td>";
                  } ?>
                  <td></td>
                  <td></td>
                  <td></td>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($week["entries"] as $idx => $row): ?>
                <tr>
                  <td class="rank"><?= $idx + 1 ?></td>
                  <td><?= $row["name"] ?></td>
                  <?php foreach ($row["guesses_txt"] as $guess): ?>
                  <td class="score"><?= $guess ?></td>
                  <?php endforeach; ?>
                  <td><?= $row["strokes_txt"] ?></td>
                  <td><?= $row["under_par_txt"] ?></td>
                  <td class="specials"><?= $row["specials_txt"] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </section>
  </main>
  <footer>
    <span>Rapporten skapad <?= datefmt_format($dt_long_fmt, $now) ?>
     .</span>
  </footer>
</body>
</html>

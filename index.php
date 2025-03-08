<?php

echo "<style>" . file_get_contents('styles.css') . "</style>";
echo "<script>" . file_get_contents('scripts.js') . "</script>";

include '_common.php';
include "_entities.php";

$conn = \connect();


echo "<h1>Главная</h1>";
echo "<hr/>";


// Точка входа


if (!isset($_GET['agency'])) {
    echo "Выберите агенство:";
    echo '<ul>';
    foreach ($conn->query('SELECT * FROM `agencies`') as $row) {
        $id = $row['id'];
        $name = $row['name'];
        echo "<li><a href=\"/?agency=$id\"><strong>$id.</strong> $name</li>";
    }
    echo '</ul>';
    die();
}


// Информация об агенстве. Тут отображаются результаты вычисления правил отбора отелей


$agencyId = (int)$_GET['agency'];
$agency = \Entity\Agency::fromArray(
    $conn->query("SELECT * FROM `agencies` WHERE id = $agencyId")->fetch()
);
echo "<h2>$agency->id. $agency->name</h2>";
echo "<nav><ul>";
echo "<li><a href=\"rules.php?agency=$agencyId\">Правила</a></li>";
echo "<li><a href=\"/\">На главную</a></li>";
echo "</ul></nav>";


$allRules = \Entity\FilterRule::fromArrayMultiple(
    $conn->query("SELECT * FROM `filter_rule` WHERE agency_id = $agencyId")->fetchAll()
);


/**
 * @var array<int,int[]> {правило: отели}
 */
$rulesMatching = [];


foreach ($allRules as $key => $filterRule) {
    $query = $filterRule->value->buildQuery();
    $rulesMatching[$key] = array_map(
        fn ($row) => $row['id'],
        $conn->query($query)->fetchAll()
    );
}

if (!isset($_GET['hotel'])) {
    echo "Выберите отель:";
    echo "<ul class=\"hotels\">";

    $hotels = \Entity\Hotel::fromArrayMultiple(
        $conn->query('SELECT * FROM `hotels`'
    )->fetchAll());

    foreach ($hotels as $hotel) {
        echo "<li class=\"hotel\">";
        echo "<a href=\"/?agency=$agencyId&hotel=$hotel->id\"><strong>$hotel->id.</strong> $hotel->name</a>";
        echo "<div class=\"hotel-tags\">";

        // ищем правила, которым соответсвует данный отель
        $hotelTags = array_filter(
            $rulesMatching,
            fn ($v, $k) => in_array($hotel->id, $v),
            ARRAY_FILTER_USE_BOTH
        );

        foreach(array_keys($hotelTags) as $ruleId) {
            echo "<div class=\"hotel-tag\">" . $allRules[$ruleId]->description . "</div>";
        }

        echo "</li>";
    }
    echo '</ul>';
    die();
}


// Детальная информация по отелю


$hotelId = (int)$_GET['hotel'];
$hotel = \Entity\Hotel::fromArray(
    $conn->query("SELECT * FROM `hotels` WHERE id = $hotelId")->fetch()
);
echo "<h3>$hotel->id. $hotel->name ";

$city = \Entity\City::fromArray(
    $conn->query("SELECT * FROM `cities` WHERE id = $hotel->city_id")->fetch()
);

$country = \Entity\Country::fromArray(
    $conn->query("SELECT * FROM `countries` WHERE id = $city->country_id")->fetch()
);

$optionsRaw = $conn->query("
    SELECT *
    FROM `agency_hotel_options`
    WHERE agency_id = $agencyId AND hotel_id = $hotelId
")->fetch();
$options = $optionsRaw ? \Entity\AgencyHotelOptions::fromArray($optionsRaw) : null;

$agreements = \Entity\HotelAgreements::fromArrayMultiple(
    $conn->query("
        SELECT *
        FROM `hotel_agreements`
        WHERE company_id = $agencyId AND hotel_id = $hotelId
    ")->fetchAll()
);

$stars = [
    '☆☆☆☆☆',
    '★☆☆☆☆',
    '★★☆☆☆',
    '★★★☆☆',
    '★★★★☆',
    '★★★★★',
][$hotel->stars];

echo "$stars</h3>";

echo "<table>";
echo "<tr><th>Город</th><td>$city->name</td></tr>";
echo "<tr><th>Страна</th><td>$country->name</td></tr>";
echo "</table>";

if ($options) {
    echo "<table>";
    echo "<tr><th>Процент</th><td>$options->percent%</td></tr>";
    echo "<tr><th>В черном списке</th><td>" . ($options->is_black ? '✅' : '❌') . "</td></tr>";
    echo "<tr><th>Рекомендованный</th><td>" . ($options->is_recomend ? '✅' : '❌') . "</td></tr>";
    echo "<tr><th>В белом списке</th><td>" . ($options->is_white ? '✅' : '❌') . "</td></tr>";
    echo "</table>";
}

if ($agreements) {
    echo "<table>";
    echo "  <tr>";
    echo "      <th>ID</br>договора</th>";
    echo "      <th>Скидка</th>";
    echo "      <th>Комиссия</th>";
    echo "      <th>Договор</br>по</br>умолчанию</th>";
    echo "      <th>Процент</br>НДС</th>";
    echo "      <th>Процент</br>НДС1</th>";
    echo "      <th>Значение</br>НДС</th>";
    echo "      <th>ID</br>компании</th>";
    echo "      <th>Начало</br>действия</br>договора</th>";
    echo "      <th>Конец</br>действия</br>договора</th>";
    echo "      <th>Наличные</th>";
    echo "  </tr>";
    foreach ($agreements as $a) {
        echo "  <tr>";
        echo "      <td>$a->id</td>";
        echo "      <td>$a->discount_percent%</td>";
        echo "      <td>$a->comission_percent%</td>";
        echo "      <td>" . ($a->is_default ? '✅' : '❌') . "</td>";
        echo "      <td>$a->vat_percent%</td>";
        echo "      <td>$a->vat1_percent%</td>";
        echo "      <td>$a->vat1_value</td>";
        echo "      <td>$a->company_id</td>";
        echo "      <td>{$a->date_from->format('d.m.Y')}</td>";
        echo "      <td>{$a->date_to->format('d.m.Y')}</td>";
        echo "      <td>" . ($a->is_cash_payment ? '✅' : '❌') . "</td>";
        echo "  </tr>";
    }
    echo "</table>";
}

?>

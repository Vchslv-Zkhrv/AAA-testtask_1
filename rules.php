<?php

/**
 * страница создания и редактирования правил
 */

define('HOME', '<a href="/">На главную</a>');
define('RULES', '<a href="/rules.php">Все правила</a>');


// если агенство не указано - на главную
if (!isset($_GET['agency'])) {
    header("Location: /", true, 301);
    die();
}

echo "<style>" . file_get_contents('styles.css') . "</style>";
echo "<script>" . file_get_contents('scripts.js') . "</script>";

include '_common.php';
include "_entities.php";


$conn = \connect();


echo "<h1>Правила</h1>";
echo "<hr/>";


$agencyId = (int)$_GET['agency'];
$agency = \Entity\Agency::fromArray(
    $conn->query("SELECT * FROM `agencies` WHERE id = $agencyId")->fetch()
);
echo "<h2>$agency->id. $agency->name</h2>";


echo "<nav><ul>";
echo "<li><a href=\"/\">На главную</a></li>";
echo "</ul></nav>";


/**
 * Нарисовать HTML правила
 */
function makeRule(\Entity\FilterRule $filterRule): string
{
    $sql = "<div class=\"filter-rule\" id=\"filter-rule-$filterRule->id\">";
    $sql .= "<div class=\"filter-rule-header\">";
    $sql .= "<button
        class=\"filter-rule-delete-button\"
        id=\"filter-rule-delete-button-$filterRule->id\"
        data-id=\"$filterRule->id\"
        onclick=\"onFilterRuleDeleteButtonClick(event)\"
    >❌</button>";
    $sql .= "<h5 class=\"filter-rule-name\">$filterRule->description</h5>";
    $sql .= "</div>";

    $rules = [];
    foreach ($filterRule->value->rules as $rule) {
        $rules[] = "<span class=\"filter-rule-rule\">{$rule->getDescription()}</span>";
    }

    $sql .= "<div class=\"filter-rule-rules\">" . implode(' и ', $rules) . "</div>";
    $sql .= "</div>";

    return $sql;
}


$allRules = \Entity\FilterRule::fromArrayMultiple(
    $conn->query("SELECT * FROM `filter_rule` WHERE agency_id = $agencyId")->fetchAll()
);


echo "<ul class=\"filter-rules\">";
foreach ($allRules as $filterRule) {
    echo "<li>" . makeRule($filterRule) . "</li>";
}
echo "</ul>";


echo "<hr/>";


echo "
<form action=\"rules.php?agency=$agencyId\" method=\"POST\" class=\"rules-set\" id=\"create-form\">
    <h4>Создать новое правило</h4>
    <p id=\"create-form-error\"></p>
    <fieldset id=\"create-form-main-inputs\">

        <input
            type=\"text\"
            name=\"description\"
            placeholder=\"Название\"
            id=\"create-form-name\"
            oninput=\"onCreateFormNameChange(event)\"
        />

        <button
            id=\"create-form-add-button\"
            onclick=\"onCreateFormAddButtonClick(event)\"
        >
            Добавить условие
        </button>

        <button
            id=\"create-form-submit-button\"
            onclick=\"onCreateFormSubmitButtonClick(event)\"
            disabled
        >
            Сохранить
        </button>

    </fieldset>
</form>
";


?>

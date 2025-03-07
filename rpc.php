<?php

namespace RPC;

define('RPC_BODY', json_decode(file_get_contents('php://input'), true));
define('RPC_METHOD', RPC_BODY['method']);
define('RPC_PARAMS', RPC_BODY['params']);
define('RPC_ID',     RPC_BODY['id']);

include '_common.php';
include '_rules.php';


function getVariants(): array
{
    $conn = \connect();

    /** @var class-string<\Rule\Rule> */
    $rule = \Rule\RULES[RPC_PARAMS['rule']];

    return [
        'operator' => $rule::getAllowedOperators(),
        'value' => $rule::getTargetValues($conn)
    ];
}


function createRule(): array
{
    $ruleSet = \Rule\RulesSet::fromArray(RPC_PARAMS['rules']);
    $agencyId = (int)RPC_PARAMS['agency'];
    $description = RPC_PARAMS['name'];

    if (empty($ruleSet->rules) || $agencyId == 0 || $description == '') {
        http_response_code(429);
        die();
    }

    $conn = \connect();

    $statement = $conn->prepare("INSERT INTO `filter_rule` (
        agency_id, description, value
    ) VALUES (
        :agency, :description, :value
    )");

    try {
        $statement->execute([
            'agency' => $agencyId,
            'description' => $description,
            'value' => $ruleSet->toJSON()
        ]);
        return [
            'success' => true,
        ];
    } catch (\Throwable $e) {
        return [
            'success' => false,
            '_message' => $e->getMessage(),
            '_trace' => $e->getTraceAsString(),
            'message' => "Не удалось создать правило. Попробуйте другое имя"
        ];
    }
}


function deleteRule(): array
{
    $ruleId = (int)RPC_PARAMS['id'] ?? 0;

    if ($ruleId == 0) {
        http_response_code(429);
        die();
    }

    $conn = \connect();

    try {
        $conn->exec("DELETE FROM `filter_rule` WHERE id = $ruleId");
        return [
            'success' => true,
        ];
    } catch (\Throwable $e) {
        return [
            'success' => false,
            '_message' => $e->getMessage(),
            '_trace' => $e->getTraceAsString(),
            'message' => "Не удалось удалить правило"
        ];
    }
}


$function = "\RPC\\" . RPC_METHOD;


if (function_exists($function)) {
    try {
        echo json_encode($function(), JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
        die();
    } catch (\Throwable $e) {
        http_response_code(500);
        throw $e;
    }
} else {
    http_response_code(404);
}

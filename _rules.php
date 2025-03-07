<?php

namespace Rule;


enum Operator
{
    case eq;
    case nq;
    case lt;
    case gt;
}

const OPERATOR_DESCRIPTION = [
    'eq' => 'равно',
    'nq' => 'не равно',
    'lt' => 'меньше',
    'gt' => 'больше'
];


const OPERATOR_FORMULA = [
    'eq' => '=',
    'nq' => '!=',
    'lt' => '<',
    'gt' => '>'
];


abstract class JsonObject implements \JsonSerializable
{
    /**
     * Прочитать JSON
     *
     * @param array<string,mixed> $array
     * @return static
     */
    abstract static public function fromArray(array $array): static;

    /**
     * Прочитать ассоциативный массив
     *
     * @param array<string,mixed> $json
     * @return static
     */
    public static function fromJSON(string $json): static
    {
        $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return static::fromArray($array);
    }

    /**
     * Записать как ассоциативный массив
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Записать как JSON
     */
    public function toJSON(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}


class RuleVariants extends JsonObject
{
    /**
     * @param int|string          $id   id для отображения в `<option value=`
     * @param string              $text текст для пользователя
     * @param array<string,mixed> $meta дополнительные поля с данными
     */
    public function __construct(
        public int|string $id,
        public string $text,
        public array $meta = []
    ) {
    }

    public static function fromArray(array $array): static
    {
        $id = (int)$array['id'];
        unset($array['id']);

        $text = (string)$array['text'];
        unset($array['text']);

        return new static($id, $text, $array);
    }
}


abstract class Rule extends JsonObject
{
    public function __construct(
        public string $type,
    ) {
    }

    /**
     * Записать `Rule` как SQL SELECT
     *
     * @return string
     */
    public function toSqlSelect(): ?string
    {
        return null;
    }

    /**
     * Записать `Rule` как SQL - условие WHERE
     *
     * @return string
     */
    public function toSqlWhere(): ?string
    {
        return null;
    }

    /**
     * Записать `Rule` как SQL - условие HAVING
     *
     * @return string
     */
    public function toSqlHaving(): ?string
    {
        return null;
    }

    /**
     * Записать `Rule` как строку на русском
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * SQL для `getTargetValues`
     *
     * @param PDO $conn
     * @return array<int,mixed> {id, text, ...}
     */
    abstract static protected function _getTargetValues(): string;

    /**
     * Вернуть все возможные варианты значения
     *
     * @param PDO $conn
     * @return RuleVariants[]
     */
    static public function getTargetValues(\PDO $conn): array
    {
        return array_map(
            fn (array $a) => RuleVariants::fromArray(
                // PDO::fetchAll возвращает массив,
                // в котором все значения продублированы текстовым и числовым ключом
                array_filter($a, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY)
            ),
            $conn->query(static::_getTargetValues())->fetchAll()
        );
    }

    /**
     * @return Operator[]
     */
    abstract static protected function _getAllowedOperators(): array;

    /**
     * Вернуть возможные операторы
     *
     * @return RuleVariants[]
     */
    public static function getAllowedOperators(): array
    {
        return array_map(
            fn (Operator $o) => new RuleVariants(
                $o->name,
                OPERATOR_DESCRIPTION[$o->name]
            ),
            static::_getAllowedOperators()
        );
    }
}


class CountryRule extends Rule
{
    /**
     * @param int      $country_id
     * @param string   $country_name
     * @param 'eq'|'nq' $operator
     */
    public function __construct(
        public int $country_id,
        public string $country_name,
        public string $operator,
    ) {
        parent::__construct('country');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['country_id'],
            $array['country_name'],
            $array['operator'],
        );
    }

    public function toSqlWhere(): ?string
    {
        return "countries.id" . OPERATOR_FORMULA[$this->operator] .  $this->country_id;
    }

    public function getDescription(): string
    {
        return "Страна " .
            \bold(OPERATOR_DESCRIPTION[$this->operator]) . ' ' .
            \code($this->country_name)
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "
            SELECT id, name AS text, name AS country_name, id AS country_id
            FROM `countries`
        ";
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::eq,
            Operator::nq
        ];
    }
}


class CityRule extends Rule
{
    /**
     * @param int       $city_id
     * @param int       $country_id
     * @param string    $city_name
     * @param string    $country_name
     * @param 'eq'|'nq' $operator
     */
    public function __construct(
        public int $city_id,
        public int $country_id,
        public string $country_name,
        public string $city_name,
        public string $operator,
    ) {
        parent::__construct('city');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['city_id'],
            (int)$array['country_id'],
            $array['country_name'],
            $array['city_name'],
            $array['operator'],
        );
    }

    public function toSqlWhere(): ?string
    {
        $sql = "cities.id = $this->city_id AND cities.country_id = $this->country_id";

        if ($this->operator == 'nq') {
            $sql = "NOT ($sql)";
        }

        return $sql;
    }

    public function getDescription(): string
    {
        return "Город " .
            \bold(OPERATOR_DESCRIPTION[$this->operator]) . ' ' .
            \code($this->city_name)
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "
            SELECT
                cities.id AS id,
                concat(cities.name, \" (\", countries.name, \")\") AS text,
                cities.id AS city_id,
                cities.name AS city_name,
                cities.country_id AS country_id,
                countries.name AS country_name
            FROM
                `cities`
                INNER JOIN `countries` ON (
                    cities.country_id = countries.id
                )
        ";
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::eq,
            Operator::nq
        ];
    }
}


class StarsRule extends Rule
{
    /**
     * @param 0|1|2|3|4|5 $stars
     * @param 'eq'|'nq'   $operator
     */
    public function __construct(
        public int $stars,
        public string $operator,
    ) {
        parent::__construct('stars');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['stars'],
            (string)$array['operator']
        );
    }

    public function toSqlWhere(): ?string
    {
        $operator = OPERATOR_FORMULA[$this->operator];
        return "hotels.stars $operator $this->stars";
    }

    public function getDescription(): string
    {
        return "Звездность отеля " .
            \bold(OPERATOR_DESCRIPTION[$this->operator]) . ' ' .
            \code($this->stars)
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "";
    }

    public static function getTargetValues(\PDO $conn): array
    {
        return [
            new RuleVariants(0, '☆☆☆☆☆', ['stars' => '0']),
            new RuleVariants(1, '★☆☆☆☆', ['stars' => '1']),
            new RuleVariants(2, '★★☆☆☆', ['stars' => '2']),
            new RuleVariants(3, '★★★☆☆', ['stars' => '3']),
            new RuleVariants(4, '★★★★☆', ['stars' => '4']),
            new RuleVariants(5, '★★★★★', ['stars' => '5']),
        ];
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::nq,
            Operator::eq
        ];
    }
}


/**
 * Не совсем понятно, зачем такое правило.
 * Bидимо, чисто чтобы показать, что я умею пользоваться OR в mysql
 */
class PercentRule extends Rule
{
    /**
     * @param int                 $percent
     * @param 'eq'|'nq'|'lt'|'gt' $operator
     */
    public function __construct(
        public int $percent,
        public string $operator,
    ) {
        parent::__construct('percent');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['percent'],
            (string)$array['operator']
        );
    }

    public function toSqlWhere(): ?string
    {
        $operator = OPERATOR_FORMULA[$this->operator];
        return "
            hotel_agreements.discount_percent $operator $this->percent
            OR hotel_agreements.comission_percent $operator $this->percent
        ";
    }

    public function getDescription(): string
    {
        return "Комиссия или скидка " .
            \bold(OPERATOR_DESCRIPTION[$this->operator]) . ' ' .
            \code($this->percent . "%") 
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "";
    }

    public static function getTargetValues(\PDO $conn): array
    {
        return [
            new RuleVariants(0, '0%',  ['percent' => '0' ]),
            new RuleVariants(1, '10%', ['percent' => '10']),
            new RuleVariants(2, '20%', ['percent' => '20']),
            new RuleVariants(3, '30%', ['percent' => '30']),
            new RuleVariants(4, '40%', ['percent' => '40']),
            new RuleVariants(5, '50%', ['percent' => '50']),
            new RuleVariants(6, '60%', ['percent' => '60']),
            new RuleVariants(7, '70%', ['percent' => '70']),
            new RuleVariants(8, '80%', ['percent' => '80']),
            new RuleVariants(9, '90%', ['percent' => '90']),
        ];
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::nq,
            Operator::eq,
            Operator::lt,
            Operator::gt,
        ];
    }
}


class DefaultContractRule extends Rule
{
    /**
     * @param bool $has_default_contract
     * @param 'eq' $operator
     */
    public function __construct(
        public bool $has_default_contract,
        public string $operator,
    ) {
        parent::__construct('default_contract');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (bool)(int)$array['has_default_contract'],
            (string)$array['operator']
        );
    }

    public function toSqlSelect(): ?string
    {
        return "MAX(hotel_agreements.is_default) AS has_default_contract";
    }

    public function toSqlHaving(): ?string
    {
        return "has_default_contract = " . (int)$this->has_default_contract;
    }

    public function getDescription(): string
    {
        return $this->has_default_contract
            ? "Имеет договор по умолчанию"
            : "Не имеет договора по умолчанию"
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "";
    }

    public static function getTargetValues(\PDO $conn): array
    {
        return [
            new RuleVariants(0, 'Есть', ['has_default_contract' => '1' ]),
            new RuleVariants(1, 'Нету', ['has_default_contract' => '0']),
        ];
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::eq,
        ];
    }
}


class CompanyRule extends Rule
{
    /**
     * @param int       $company_id
     * @param string    $company_name
     * @param 'eq'|'nq' $operator
     */
    public function __construct(
        public int $company_id,
        public string $company_name,
        public string $operator,
    ) {
        parent::__construct('company');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['company_id'],
            (string)$array['company_name'],
            $array['operator'],
        );
    }

    public function toSqlWhere(): ?string
    {
        $sql = "companies.id" . OPERATOR_FORMULA[$this->operator] . "$this->company_id";

        return $sql;
    }

    public function getDescription(): string
    {
        return "Компания " .
            \bold(OPERATOR_DESCRIPTION[$this->operator]) . ' ' .
            \code($this->company_id . ". " . $this->company_name)
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "
            SELECT
                companies.id AS id,
                concat(companies.id, \". \", companies.name) AS text,
                companies.id AS company_id,
                companies.name AS company_name
            FROM
                `companies`
        ";
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::eq,
            Operator::nq
        ];
    }
}


class WhitelistRule extends Rule
{
    /**
     * @param bool $in_whitelist
     * @param 'eq' $operator
     */
    public function __construct(
        public bool $in_whitelist,
        public string $operator,
    ) {
        parent::__construct('whitelist');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (bool)(int)$array['in_whitelist'],
            (string)$array['operator']
        );
    }

    public function toSqlWhere(): ?string
    {
        return "agency_hotel_options.is_white = " . (int)$this->in_whitelist;
    }

    public function getDescription(): string
    {
        return $this->in_whitelist
            ? "В белом списке"
            : "Не в белом списке"
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "";
    }

    public static function getTargetValues(\PDO $conn): array
    {
        return [
            new RuleVariants(0, '✅', ['in_whitelist' => '1']),
            new RuleVariants(1, '❌', ['in_whitelist' => '0']),
        ];
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::eq
        ];
    }
}


class BlacklistRule extends Rule
{
    /**
     * @param bool $in_blacklist
     * @param 'eq' $operator
     */
    public function __construct(
        public bool $in_blacklist,
        public string $operator,
    ) {
        parent::__construct('blacklist');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (bool)(int)$array['in_blacklist'],
            (string)$array['operator']
        );
    }

    public function toSqlWhere(): ?string
    {
        return "agency_hotel_options.is_black = " . (int)$this->in_blacklist;
    }

    public function getDescription(): string
    {
        return $this->in_blacklist
            ? "В черном списке"
            : "Не в черном списке"
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "";
    }

    public static function getTargetValues(\PDO $conn): array
    {
        return [
            new RuleVariants(0, '✅', ['in_blacklist' => '1']),
            new RuleVariants(1, '❌', ['in_blacklist' => '0']),
        ];
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::eq
        ];
    }
}


class RecommendRule extends Rule
{
    /**
     * @param bool $in_recommended
     * @param 'eq' $operator
     */
    public function __construct(
        public bool $in_recommended,
        public string $operator,
    ) {
        parent::__construct('recommended');
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (bool)(int)$array['in_recommended'],
            (string)$array['operator']
        );
    }

    public function toSqlWhere(): ?string
    {
        return "agency_hotel_options.is_recomend = " . (int)$this->in_recommended;
    }

    public function getDescription(): string
    {
        return $this->in_recommended
            ? "В списке рекомендованных"
            : "Не в списке рекомендованных"
        ;
    }

    protected static function _getTargetValues(): string
    {
        return "";
    }

    public static function getTargetValues(\PDO $conn): array
    {
        return [
            new RuleVariants(0, '✅', ['in_recommended' => '1']),
            new RuleVariants(1, '❌', ['in_recommended' => '0']),
        ];
    }

    protected static function _getAllowedOperators(): array
    {
        return [
            Operator::eq
        ];
    }
}


const RULES = [
    'country' => CountryRule::class,
    'city' => CityRule::class,
    'stars' => StarsRule::class,
    'percent' => PercentRule::class,
    'default_contract' => DefaultContractRule::class,
    'company' => CompanyRule::class,
    'whitelist' => WhitelistRule::class,
    'blacklist' => BlacklistRule::class,
    'recommended' => RecommendRule::class,
];


class RulesSet extends JsonObject
{
    /**
     * @param Rule[] $rules
     */
    public function __construct(
        /** @var Rule[] */
        public array $rules
    ) {
    }

    /**
     * @param array[] $array
     * @return static
     */
    public static function fromArray(array $array): static
    {
        $rules = [];
        foreach ($array as $a) {
            $type = $a['type'];
            /** @var class-string<Rule> */
            $class = RULES[$type];
            $rules[] = $class::fromArray($a);
        }

        return new static($rules);
    }

    public function toArray(): array
    {
        return array_map(
            fn (Rule $r) => $r->toArray(),
            $this->rules
        );
    }

    public function buildQuery(): string
    {
        // набор таблиц для FROM можно сделать динамическим, добавив метод getDependencies(): string[]

        $sql = "
        SELECT
            hotels.id AS id
        ";

        foreach ($this->rules as $rule) {
            if ($rule->toSqlSelect()) {
                $sql .= ", " . $rule->toSqlSelect();
            }
        }

        $sql .= "
        FROM
            `hotels`
            LEFT JOIN `hotel_agreements` ON (
                hotel_agreements.hotel_id = hotels.id
            )
            LEFT JOIN `companies` ON (
                companies.id = hotel_agreements.company_id
            )
            LEFT JOIN `cities` ON (
                hotels.city_id = cities.id
            )
            LEFT JOIN `countries` ON (
                countries.id = cities.country_id
            )
            LEFT JOIN `agency_hotel_options` ON (
                agency_hotel_options.hotel_id = hotels.id
            )
            LEFT JOIN `agencies` ON (
                agency_hotel_options.agency_id = agencies.id
            )
        ";

        $where = [];
        foreach ($this->rules as $rule) {
            if ($rule->toSqlWhere()) {
                $where[] = $rule->toSqlWhere();
            }
        }

        if ($where) {
            $sql .= " WHERE (" . implode(') AND (', $where) . ") ";
        }

        $sql .= " GROUP BY id ";

        $having = [];
        foreach ($this->rules as $rule) {
            if ($rule->toSqlHaving()) {
                $having[] = $rule->toSqlHaving();
            }
        }

        if ($having) {
            $sql .= " HAVING (" . implode(') AND (', $having) . ") ";
        }

        return $sql;
    }
}


?>

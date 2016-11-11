<h1>Класс для авторизации и получения данных из amoCRM.ru</h1>
Класс удобен при выгрузке данных из аккаунта на amoCRM.ru в таблицу. 
Все, что связано со сделкой, в одной строке таблицы.

Требует cURL.

<h2>Использование</h2>
Создать файл <code>amoTable.php</code>, в котором будут считываться сделки и связанные с ними данные.
Когда данные готовы, формируются строки таблицы.
<pre>
require(dirname(__FILE__) . '/amoREST.php');
class amoTable {
    const DELIMITER = "\t";
    const BATCH_SIZE = 100;
    // needed data for making a table
    public $account;
    public $leads;
    public $events;
    public $notes;
    ...
    public function __construct($config)
    {
        $maxrows = $config['limit_rows'];
        $offset = $config['limit_offset'];
        $amo = new amoREST(['account' =&gt; $config['account']]);
        $this-&gt;amo = $amo;
        if($amo-&gt;auth($config['email'], $config['api_key'])) {
            // account
            $response = $amo-&gt;get('accounts/current');
            $this-&gt;account = $response['account'];
            // leads
            $response = $amo-&gt;get('leads/list', [
                'limit_rows' =&gt; $maxrows,
                'limit_offset' =&gt; $offset,
            ]);
            $this-&gt;leads = $response['leads'];
            ...
    }
    /*
     * CSV table maker
     */
    public function make()
    {
        // Make a table
        foreach($this->leads as $lead) {
        ...
    }</pre>

Теперь остается положить в корень файл <code>index.php</code> примерно следующего содержания

<pre>
require(dirname(__FILE__) . '/amoTable.php');
$amoTable = new amoTable([
    'account' =&gt; 'your-amoCRM-account',
    'email' =&gt; 'registered-email', 
    'api_key' =&gt; 'your-api-key',
    'limit_rows' =&gt; getParam('maxleads', 500),
    'limit_offset' =&gt; getParam('offset', 0),
]);
$f = fopen('amo.csv', 'w');
foreach($amoTable-&gt;make() as $line)
    fwrite($f, "$line\n");
fclose($f);
echo "Fine! Please, check amo.csv file.";
function getParam($var, $default) {
    return isset($_GET[$var]) ? $_GET[$var] : $default;
}
</pre>

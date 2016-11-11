<?php
/**
* @author sergmoro1@ya.ru
* @license MIT
* 
* Class execute authorization and get request to amoCRM.ru REST API. 
* 
*/

class amoREST {
	public $http_code;
	public $subdomain; // subdomain is a first part of a web address - https://subdomain.amoCRM.ru 
	// paths to API
    private static $urls = [
		'auth' => '/private/api/auth.php?type=json',
		'api' => '/private/api/v2/json/',
    ];
    // Error messages
    private static $errors = [
        301 => 'Moved permanently',
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    ];
    // Curl defaults
    private static $default_options = [
        CURLOPT_HEADER => false,
        CURLOPT_USERAGENT => 'amoCRM-API-client/1.0',
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RETURNTRANSFER => true,
    ];
    
	public function __construct($config)
	{
		$this->subdomain = $config['account'];
		self::$default_options[CURLOPT_COOKIEFILE] = dirname(__FILE__) . '/cookie.txt';
		self::$default_options[CURLOPT_COOKIEJAR] = dirname(__FILE__) . '/cookie.txt';
	}
	
    /*
     * Authorization or getting a cookie for next API queries.
     * @param login - user email
     * @param hash - user api key
     * @return - authorized or not
     */
    public function auth($login, $hash)
    {
        $fields = json_encode(['USER_LOGIN' => $login, 'USER_HASH' => $hash]);
        
        $http_data = $this->curl([
            CURLOPT_URL => $this->getUrl('auth'),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $fields,
        ]);
        $this->checkCurlResponse();

        $a = json_decode($http_data, true);
        $response = $a['response'];
        return isset($response['auth']);
    }

    /*
     * Get request.
     * @param action - controller/action (ex: 'contacts/list')
     * @param query - key => value array (ex: ['email' => 'manager@example.com'])
     * @return response
     * 
     * example usage:
     * 
     * $response = $amo->get('leads/list', [
     *     'limit_rows' => $maxrows,
     *     'limit_offset' => $offset,
     * ]);
     * $leads = $response['leads'];
     * 
     */
    public function get($action, $query = [])
    {
        $http_data = $this->curl([
            CURLOPT_URL => $this->getUrl('api', $action, $query),
        ]);
        $this->checkCurlResponse();

        $a = json_decode($http_data, true);
        $response = $a['response'];
        return $response;
    }

    /*
     * Make url.
     * @param url - api path
     * @param action - controller/action
     * @param query - array of key => value pairs
     * @return string url
     */
    public function getUrl($url, $action = '', $query = [])
    {
        return 'https://' . $this->subdomain . '.amocrm.ru' . self::$urls[$url] . $action . ($query
			? '?' . http_build_query($query)
			: '');
    }
    
    /*
     * CURL utility request.
     * @param options
     */
    public function curl($options)
    {
        $ch = curl_init();
        // merge default options that equal for all requests and specific options and set it
        foreach(self::$default_options as $i => $option)
			$options[$i] = $option;
        curl_setopt_array($ch, $options);
        // send http request
        $http_data = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        // save response code
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->http_code = (int)$code;
        curl_close($ch);
        return $http_data;
    }  

    /*
     * Check http request response code.
     */
    public function checkCurlResponse()
    {
		$code = $this->http_code;
        try {
            if($code != 200 && $code != 204)
                throw new Exception(isset(self::$errors[$code]) 
                    ? self::$errors[$code] 
                    : 'Undescribed error', $code);
        } catch(Exception $e) {
            die('Error: ' . $e->getMessage() . PHP_EOL . 'Code: ' . $e->getCode());
        }
    }
}

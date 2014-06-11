<?php

class Moves
{

    public $client_id;
    public $client_secret;
    public $api_url;
    public $redirect_url;
    public $oauth_url;

    public function __construct
    (
            $client_id, #Client ID, get this by creating an app
            $client_secret, #Client Secret, get this by creating an app
            $redirect_url, #Callback URL for getting an access token
            $oauth_url = 'https://api.moves-app.com/oauth/v1/', $api_url = 'https://api.moves-app.com/api/1.1'
    )
    {
        $this->api_url = $api_url;
        $this->oauth_url = $oauth_url;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_url = $redirect_url;
    }

    #Generate an request URL
    public function requestURL()
    {
        $u = $this->oauth_url . 'authorize?response_type=code';
        $c = '&client_id=' . urlencode($this->client_id);
        $r = '&redirect_uri' . urlencode($this->redirect_url);
        $s = '&scope=' . urlencode('activity location'); # Assuming we want both activity and locations
        $url = $u . $c . $s . $r;
        return $url;
    }

    #Validate access token
    public function validate_token($token)
    {
        $u = $this->oauth_url . 'tokeninfo?access_token=' . $token;
        $r = $this->get_http_response_code($u);
        if ($r === "200") {
            return json_decode($this->geturl($u), True);
        } else {
            return False;
        }
    }

    #Get access_token 
    public function auth($request_token)
    {
        $u = $this->oauth_url . "access_token";
        $d = array('grant_type' => 'authorization_code', 'code' => $request_token, 'client_id' => $this->client_id, 'client_secret' => $this->client_secret, 'redirect_uri' => $this->redirect_url);
        $o = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($d),
            ),
        );
        $context = stream_context_create($o);
        $result = $this->geturl($u, false, $context);
        $token = json_decode($result, True);
        return $token['access_token'];
    }

    #Base request
    private function get($token, $endpoint)
    {
        $token = '?access_token=' . $token;
        return json_decode($this->geturl($this->api_url . $endpoint . $token), True);
    }

    #/user/profile
    public function get_profile($token)
    {
        $root = '/user/profile';
        return $this->get($token, $root);
    }

    #Range requests
    #/user/summary/daily
    #/user/activities/daily
    #/user/places/daily
    #/user/storyline/daily
    #date: date in yyyyMMdd or yyyy-MM-dd format
    public function get_range($access_token, $endpoint, $start, $end)
    {
        $export = $this->get($access_token . '&from=' . $start . '&to=' . $end, $endpoint);
        return $export;
    }

    private function get_http_response_code($url)
    {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }

    private function geturl($url){
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($session);
        curl_close($session);
        return $data;
    }

}
?>

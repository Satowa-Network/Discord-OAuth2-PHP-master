<?php namespace Xwilarg\Discord;
class OAuth2 {
    function __construct($clientId, $secret, $redirectUrl) {
        $this->_clientId = $clientId;
        $this->_secret = $secret;
        $this->_redirectUrl = $redirectUrl;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function startRedirection($scope) {
        $randomString = OAuth2::generateToken();
        $_SESSION['oauth2state'] = $randomString;
        header('Location: https://discord.com/api/oauth2/authorize?client_id=' . $this->_clientId . '&redirect_uri=' . urlencode($this->_redirectUrl) . '&response_type=code&scope=' . join('%20', $scope) . "&state=" . $randomString);
    }
    public function addMemberGuild($uid, $gid){
        if ($this->_accessToken === null) {
                $response = $this->loadToken();
                if ($response !== true) {
                    return ["code" => 0, "message" => $response];
                }
            }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://discord.com/api/v6/guilds/909599485204779028/members/'.$uid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS =>'{"access_token": "'.$this->_accessToken.'"}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bot OTE0NjUzNzgwMDE0OTMyMDEw.YaQLtg.fZUVXFfiToMocBuChZBGdqFz5TQ',
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
    }
    public function isRedirected() {
        return isset($_GET['code']);
    }

    public function getCustomInformation($endpoint) {
        return $this->getInformation($endpoint);
    }

    public function getUserInformation() {
        return $this->getInformation('users/@me');
    }

    public function getConnectionsInformation() {
        return $this->getInformation('users/@me/connections');
    }

    public function getGuildsInformation() {
        return $this->getInformation('users/@me/guilds');
    }

    private function getInformation($endpoint) {
        if ($this->_accessToken === null) {
            $response = $this->loadToken();
            if ($response !== true) {
                return ["code" => 0, "message" => $response];
            }
        }
        $curl = curl_init('https://discord.com/api/v6/' . $endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, "false");
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->_accessToken
        ));
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $response;
    }

    public function loadToken() {
        if (!isset($_SESSION['oauth2state']) || empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            return 'Invalid state';
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://discord.com/api/v6/oauth2/token",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "client_id=" . $this->_clientId . "&client_secret=" . $this->_secret . "&grant_type=authorization_code&code=" . $_GET['code'] . "&redirect_uri=" . urlencode($this->_redirectUrl),
            CURLOPT_RETURNTRANSFER => "false"
        ));
        $response = json_decode(curl_exec($curl), true);
        if ($response === null) {
            return 'Invalid state';
        }
        if (array_key_exists('error_description', $response)) {
            return $response['error_description'];
        }
        $this->_accessToken = $response['access_token'];
        curl_close($curl);
        return true;
    }

    private static function generateToken() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLen = strlen($characters);
        $randomString = "";
        for ($i = 0; $i < 20; $i++) {
            $randomString .= $characters[rand(0, $charactersLen - 1)];
        }
        return $randomString;
    }

    private $_clientId;
    private $_secret;
    private $_redirectUrl;
    private $_accessToken = null;
}
?>

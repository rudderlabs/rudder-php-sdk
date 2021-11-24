<?php

require_once __DIR__ . '/Rudder/Client.php';

class Rudder {
  private static $client;

  /**
   * Initializes the default client to use. Uses the libcurl consumer by default.
   * @param  string $secret   your project's secret key
   * @param  array  $options  passed straight to the client
   */
  public static function init($secret, $options = array()) {
    self::assert($secret, "Rudder::init() requires secret");
    $optionsClone = unserialize(serialize($options));
    // check if ssl is here --> check if it is http or https
    if(isset($optionsClone['data_plane_url'])) {
      $optionsClone["data_plane_url"] = self::handleSSL($optionsClone);
    } else {
      // log error
      $errstr = ("'data_plane_url' option is required");
      throw new Exception($errstr);
    }
    
    self::$client = new Rudder_Client($secret, $optionsClone);
  }

  /**
   * checks the dataplane url format only is ssl key is present
   * @param  array  $options  passed straight to the client
   */
  private static function handleSSL($options) {
    
    $urlComponentArray = parse_url($options["data_plane_url"]);
    if (!(isset($urlComponentArray["scheme"]))) {
      $options["data_plane_url"] = 'https://' . $options["data_plane_url"] ;
    }
    if(filter_var($options["data_plane_url"], FILTER_VALIDATE_URL)){
      $protocol = "https";
      if(isset($options["ssl"]) && $options["ssl"]== false) {
        $protocol = "http";
      }
      $urlWithoutProtocol = self::handleUrl($options["data_plane_url"], $protocol);
      return $urlWithoutProtocol;
    } else {
       // log error
      $errstr = ("'data_plane_url' input is invalid");
      throw new Exception($errstr);
    }
}
/**
   * checks the dataplane url format only is ssl key is present
   * @param string $data_plane_url  dataplane url entered in the init() function
   * @param string $protocol the protocol needs to be used according to the ssl configuration
   */
private static function handleUrl($data_plane_url, $protocol) {
  $urlComponentArray = parse_url($data_plane_url);
  if($urlComponentArray['scheme'] == $protocol){
    // if the protocol does not exist then error is not thrown, rather added with https:// later on
   return preg_replace("(^https?://)", "", $data_plane_url );
 } else {
   // log error
   $errstr = ("Data plane URL and SSL options are incompatible with each other");
   throw new Exception($errstr);
 }
 
}

  /**
   * Tracks a user action
   *
   * @param  array $message
   * @return boolean whether the track call succeeded
   */
  public static function track(array $message) {
    self::checkClient();
    $event = !empty($message["event"]);
    self::assert($event, "Rudder::track() expects an event");
    self::validate($message, "track");

    return self::$client->track($message);
  }

  /**
   * Tags traits about the user.
   *
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  public static function identify(array $message) {
    self::checkClient();
    $message["type"] = "identify";
    self::validate($message, "identify");

    return self::$client->identify($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  public static function group(array $message) {
    self::checkClient();
    $groupId = !empty($message["groupId"]);
    self::assert($groupId, "Rudder::group() expects groupId");
    self::validate($message, "group");

    return self::$client->group($message);
  }

  /**
   * Tracks a page view
   *
   * @param  array $message
   * @return boolean whether the page call succeeded
   */
  public static function page(array $message) {
    self::checkClient();
    self::validate($message, "page");

    return self::$client->page($message);
  }

  /**
   * Tracks a screen view
   *
   * @param  array $message
   * @return boolean whether the screen call succeeded
   */
  public static function screen(array $message) {
    self::checkClient();
    self::validate($message, "screen");

    return self::$client->screen($message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one
   *
   * @param  array $from      user id to alias from
   * @return boolean whether the alias call succeeded
   */
  public static function alias(array $message) {
    self::checkClient();
    $userId = !empty($message["userId"]);
    $previousId = !empty($message["previousId"]);
    self::assert($userId && $previousId, "Rudder::alias() requires both userId and previousId");

    return self::$client->alias($message);
  }

  /**
   * Validate common properties.
   *
   * @param array $msg
   * @param string $type
   */
  public static function validate($msg, $type){
    $userId = !empty($msg["userId"]);
    $anonId = !empty($msg["anonymousId"]);
    self::assert($userId || $anonId, "Rudder::${type}() requires userId or anonymousId");
  }

  /**
   * Flush the client
   */

  public static function flush(){
    self::checkClient();

    return self::$client->flush();
  }

  /**
   * Check the client.
   *
   * @throws Exception
   */
  private static function checkClient(){
    if (null != self::$client) {
      return;
    }

    throw new Exception("Rudder::init() must be called before any other tracking method.");
  }

  /**
   * Assert `value` or throw.
   *
   * @param array $value
   * @param string $msg
   * @throws Exception
   */
  private static function assert($value, $msg) {
    if (!$value) {
      throw new Exception($msg);
    }
  }
}

if (!function_exists('json_encode')) {
  throw new Exception('Rudder needs the JSON PHP extension.');
}

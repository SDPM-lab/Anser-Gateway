<?php

namespace Config;

use AnserGateway\Config\BaseConfig;

class ZeroTrust extends BaseConfig
{
   /**
    * host
    *
    * @var string
    */
  public string $host = '';

  /**
   * Realm of Zero Trust
   * 
   * @var string
   */
  public string $realm = '';

  /**
   * master Client ID of  realm
   *
   * @var string 
   */
  public string $gatewayClientID = '';

  /**
   * master clientSecret of  realm
   *
   * @var string 
   */
  public string $gatewayClientSecret = '';

  /**
   * 使用者自訂的驗證client
   *
   * @var array
   */
  public array $customClient = [];

  /**
   * 服務名稱對應clientID
   *
   * @var array<string,string>
   */
  public array $serviceClientRelation = [];

  /**
   * Account of ZT realm
   *
   * @var string 
   */
   public string $username='';

  /**
   * Password of ZT realm
   *
   * @var string 
   */
  public string $password='';

  public function __construct()
  {
      parent::__construct();
      // 因.env傳入為字串，故使用explode作切割
      if(getenv('zerotrust.customClient') !== ''){
        $parts = explode(',', getenv('zerotrust.customClient'));
        foreach ($parts as $part) {
            // 再將每個部分根據等號分割
            $keyValue = explode('=', $part);
            // 如果陣列中有兩個元素，將它們放入結果陣列
            if (count($keyValue) == 2) {
                $this->customClient[$keyValue[0]] = [
                  "clientID" => $keyValue[0],
                  "clientSecret" => $keyValue[1]
                ];
            }
        }
      }

      if(getenv('zerotrust.serviceClientRelation') !== ''){
        $parts = explode(',', getenv('zerotrust.serviceClientRelation'));
        foreach ($parts as $part) {
            // 再將每個部分根據等號分割
            $keyValue = explode('=', $part);
            // 如果陣列中有兩個元素，將它們放入結果陣列
            if (count($keyValue) == 2) {
                $this->serviceClientRelation[$keyValue[0]] = $keyValue[1];
            }
        }
      }
  }
}

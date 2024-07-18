<?php 
namespace Config;
use SDPMlab\Anser\Service\ServiceList;

ServiceList::addLocalService("ansergateway_productservice", getenv("PRODUCT_SERVICE_IP"), getenv("PRODUCT_SERVICE_PORT"), true);
// ServiceList::addLocalService("ansergateway_userservice", getenv("USER_SERVICE_IP"), getenv("USER_SERVICE_PORT"), true);
ServiceList::addLocalService("ansergateway_userservice", getenv("USER_SERVICE_IP"), getenv("USER_SERVICE_PORT"), true);
ServiceList::addLocalService("product", "10.1.1.7", 8081, false);

?>
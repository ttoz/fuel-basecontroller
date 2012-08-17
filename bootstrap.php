<?php

Autoloader::add_classes(array(
	// controllerのactionメソッドでreturnしなかった時にafterメソッドで自動でviewmodelのresponseをreturnする
	'Base_Controller_AutoResponse'  => __DIR__.'/classes/base/controller/autoresponse.php',
	// simpleauth利用時に継承することでbeforeメソッドで自動で認証を行う
	'Base_Controller_SimpleAuth'  => __DIR__.'/classes/base/controller/simpleauth.php',
));
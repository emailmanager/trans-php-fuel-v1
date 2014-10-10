<?php

/**
 * Emailmanager Email Delivery library for Fuel
 *
 * @package		Emailmanager
 * @version		1.0
 * @author		Tom Schlick (tom@tomschlick.com)
 * @link		http://github.com/tomschlick/fuel-emailmanager
 * 
 */

Autoloader::add_core_namespace('Emailmanager');

Autoloader::add_classes(array(
	'Emailmanager\\Email_Emailmanager' => __DIR__.'/classes/email/emailmanager.php'
));
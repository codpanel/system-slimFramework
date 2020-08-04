<?php 
    
define('SCRIPTURL','http://imrashop.website/');
define('SCRIPTDIR', BASEPATH.'/');

return  [
    
    'app' => [
        'version'            => '2.8.3',
        'debug'              => false,
    ],
      
      
      
    'db_live' => [
        'driver'             => 'mysql',
        'host'               => 'localhost',
        'name'               => 'imrabpfk_sys',
        'username'           => 'imrabpfk_sys',
        'password'           => '8(=#E{]YBi^D',
        'charset'            => 'utf8',
        'collation'          => 'utf8_general_ci',
        'strict'             => 'false',
        'prefix'             => 'na_'
    ],
    
    
    
    
    'db_sandbox' => [
        'driver'             => 'mysql',
        'host'               => 'localhost',
        'name'               => 'done',
        'username'           => 'root',
        'password'           => '',
        'charset'            => 'utf8',
        'collation'          => 'utf8_general_ci',
        'strict'             => 'false',
        'prefix'             => 'na_'
    ],
    
    'views'              => '',
  
    'url' => [
        'base'               => SCRIPTURL,
        'ads'                => SCRIPTURL.'uploads/undetected/',
        'admin_assets'       => SCRIPTURL.'admin_assets/',
        'website_assets'     => SCRIPTURL.'assets/',
        'assets'             => '/assets/',
        'avatars'            => SCRIPTURL.'uploads/avatar/',
        'media'              => SCRIPTURL.'uploads/media/',    
        'uploads'            => SCRIPTURL.'uploads/',    
    ],
    
    'dir' => [
        'base'               => SCRIPTDIR,
        'media'              => SCRIPTDIR.'public/uploads/media/',
        'filat'              => SCRIPTDIR.'public/uploads/filat/',
        'csv'              => SCRIPTDIR.'public/uploads/csv/',
    ] 

    
    
];
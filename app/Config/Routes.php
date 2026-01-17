<?php
$routes->get('/', 'Auth::login');
$routes->match(['get','post'],'login', 'Auth::login');
$routes->match(['get','post'],'register', 'Auth::register');
$routes->get('logout', 'Auth::logout');

$routes->get('create', 'Game::create', ['filter'=>'auth']);
$routes->get('join/(:any)', 'Game::join/$1', ['filter'=>'auth']);
$routes->get('play/(:any)', 'Game::play/$1', ['filter'=>'auth']);
//$routes->post('set-code', 'Game::setCode', ['filter'=>'auth']);

$routes->post('set-code', 'Game::setCode');
$routes->post('guess', 'Game::makeGuess');
$routes->get('state/(:any)', 'Game::state/$1'); // added

$routes->get('dashboard', 'Game::dashboard', ['filter'=>'auth']);
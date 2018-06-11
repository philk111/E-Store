<?php

DB::$dbName  = 'homework2';
DB::$user = 'homework2';
DB::$password ='admin';

DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

function nonsql_error_handler($params) {
    $errorList = array();
    array_push($errorList, "Database error 1.");
    global $app, $log;
    // $_SERVER[] has info about client IP, etc.
    $log->error("Database error: " . $params['error']);
    http_response_code(500);
    $app->render('error_internal.html.twig', array(
         'errorList' => $errorList,
    ));
    die;
}
function sql_error_handler($params) {
    $errorList = array();
    array_push($errorList, "Database error 2.");
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error(" in query: " . $params['query']);
    http_response_code(500);
    $app->render('error_internal.html.twig', array(
         'errorList' => $errorList,
    ));
    die; 
}
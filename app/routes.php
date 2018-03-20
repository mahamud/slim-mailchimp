<?php

use \App\middleware;

//API Routes - Version 1

$application->group('/api/v1',function () use ($container) {

    //Login Endpoint
    $this->map(['POST','OPTIONS'],'/login/', AuthController::class . ':login');

    //List Endpints
    $this->map(['GET','OPTIONS'],'/mail/lists', MailController::class . ':loadLists');
    $this->map(['POST','OPTIONS'],'/mail/list', MailController::class . ':createList');
    $this->map(['PUT','OPTIONS'],'/mail/list/update/{list_id}', MailController::class . ':updateList');
    $this->map(['DELETE','OPTIONS'],'/mail/list/{list_id}', MailController::class . ':removeList');
    $this->map(['POST','OPTIONS'],'/mail/list/{list_id}/member', MailController::class . ':addMember');
    $this->map(['DELETE','OPTIONS'],'/mail/list/{list_id}/members/{member_hash}', MailController::class . ':removeMember');


})->add(new middleware\AuthMiddleware($container));


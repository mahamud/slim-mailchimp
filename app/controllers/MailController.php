<?php

namespace App\controllers;

use App\helper\Functions;
use App\helper\MailClient;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/**
 * Class MailController
 * @package App\controllers
 */
class MailController extends BaseController
{

    /**
     * MailController constructor.
     * @param $container
     */
    public function __construct($container){
        parent::__construct($container);
    }


    /**
     * Though not part of the task, this one is implemented to test the MailChim API
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @return mixed
     * @throws \Exception
     */
    public function loadLists(Request $request, Response $response){
        try {
            $mail = new MailClient();
            $lists = $mail->getList();
            $metadata = Functions::generateMetaData($this->offset, $this->limit, sizeof($lists));
            $output = Functions::output(array('metadata' => $metadata, 'lists' => $lists), API_RESPONSE_SUCCESS);
            return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
        }
        catch(\Exception $exception){
            $output = Functions::output(null, API_RESPONSE_FAILED, $exception->getMessage());
            return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
        }
    }


    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param array $arguments
     * @return mixed
     * @throws \App\libraries\Exception
     */
    public function createList(Request $request, Response $response, Array $arguments){
        $parameters = $request->getParsedBody();
        if (empty($parameters)) {
            $output = Functions::output(null, API_RESPONSE_FAILED, EMPTY_POST_PUT_PARAMETERS_MESSAGE);
            return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
        }
        $validationRules = array(
            'name' => 'alpha_numeric_space|required',
            'company' => 'alpha_numeric_space|required',
            'address1' => 'alpha_numeric_space|required',
            'city' => 'alpha_numeric_space|required',
            'state' => 'alpha_numeric_space|required',
            'zip' => 'alpha_numeric_space|required',
            'country' => 'alpha_numeric_space|required',
            'permission_reminder' => 'alpha_numeric_space|required',
            'from_name' => 'alpha_numeric_space|required',
            'from_email' => 'valid_email|required',
            'subject' => 'alpha_numeric_space|required',
            'language' => 'alpha_numeric_space|required',
            'email_type_option' => 'required|boolean'
        );
        $filterFormatRules = array();
        try {
            //Note : Filters first and then validate
            $data = Functions::validateParameters($parameters, $validationRules, $filterFormatRules);
            $mail = new MailClient();
            $mail->manageList($data);
            $output = Functions::output(array('message' => SUCCESS_ON_RECORD_INSERT), API_RESPONSE_SUCCESS);
            return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
        }
        catch(\Exception $exception){
            $output = Functions::output(array('errors' => $exception->getMessage()), API_RESPONSE_FAILED,INVALID_PARAMETER_ERROR);
            return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
        }
    }


    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param array $arguments
     * @return mixed
     * @throws \App\libraries\Exception
     */
    public function updateList(Request $request, Response $response, Array $arguments){
        $listId = filter_var($arguments['list_id'], FILTER_SANITIZE_STRING);
        $parameters = $request->getParsedBody();
        if (empty($parameters)) {
            $output = Functions::output(null, API_RESPONSE_FAILED, EMPTY_POST_PUT_PARAMETERS_MESSAGE);
            return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
        }
        $validationRules = array(
            'name' => 'alpha_numeric_space|required',
            'company' => 'alpha_numeric_space|required',
            'address1' => 'alpha_numeric_space|required',
            'city' => 'alpha_numeric_space|required',
            'state' => 'alpha_numeric_space|required',
            'zip' => 'alpha_numeric_space|required',
            'country' => 'alpha_numeric_space|required',
            'permission_reminder' => 'alpha_numeric_space|required',
            'from_name' => 'alpha_numeric_space|required',
            'from_email' => 'valid_email|required',
            'subject' => 'alpha_numeric_space|required',
            'language' => 'alpha_numeric_space|required',
            'email_type_option' => 'required|boolean'
        );
        $filterFormatRules = array();
        try {
            //Note : Filters first and then validate
            $data = Functions::validateParameters($parameters, $validationRules, $filterFormatRules);
            $mail = new MailClient();
            $mail->manageList($listId, $data);
            $output = Functions::output(array('message' => SUCCESS_ON_RECORD_INSERT), API_RESPONSE_SUCCESS);
            return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
        }
        catch(\Exception $exception){
            $output = Functions::output(array('errors' => $exception->getMessage()), API_RESPONSE_FAILED,INVALID_PARAMETER_ERROR);
            return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
        }
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return mixed
     * @throws \App\libraries\Exception
     */
    public function addMember(Request $request, Response $response, Array $arguments){
        $listId = filter_var($arguments['list_id'], FILTER_SANITIZE_STRING);
        $parameters = $request->getParsedBody();
        $validationRules = array(
            'email_address' => 'valid_email|required',
            'status' => 'alpha_numeric_space|required',
        );
        $filterFormatRules = array();
        try {
            $data = Functions::validateParameters($parameters, $validationRules, $filterFormatRules);
            $mail = new MailClient();
            $mail->addMemberToList($listId,$data);
            $output = Functions::output(array('message' => SUCCESS_ON_MEMBER_LINK), API_RESPONSE_SUCCESS);
            return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
        }
        catch(\Exception $exception){
            $output = Functions::output(array('errors' => $exception->getMessage()), API_RESPONSE_FAILED,INVALID_PARAMETER_ERROR);
            return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
        }
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return mixed
     * @throws \App\libraries\Exception
     */
    public function removeMember(Request $request, Response $response, Array $arguments){
        $listId = filter_var($arguments['list_id'], FILTER_SANITIZE_STRING);
        $memberHash = filter_var($arguments['member_hash'], FILTER_SANITIZE_STRING);
        try {
            $mail = new MailClient();
            $mail->removeMemberFromList($listId, $memberHash);
            $output = Functions::output(array('message' => SUCCESS_ON_MEMBER_UNLINK), API_RESPONSE_SUCCESS);
            return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
        }
        catch(\Exception $exception){
            $output = Functions::output(array('errors' => $exception->getMessage()), API_RESPONSE_FAILED,INVALID_PARAMETER_ERROR);
            return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
        }
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return mixed
     * @throws \App\libraries\Exception
     */
    public function removeList(Request $request, Response $response, Array $arguments){
        $listId = filter_var($arguments['list_id'], FILTER_SANITIZE_STRING);
        try {
            $mail = new MailClient();
            $mail->deleteList($listId);
            $output = Functions::output(array('message' => SUCCESS_ON_RECORD_DELETE), API_RESPONSE_SUCCESS);
            return Functions::generateResponse($output, $response, HTTP_STATUS_OK);
        }
        catch(\Exception $exception){
            $output = Functions::output(array('errors' => $exception->getMessage()), API_RESPONSE_FAILED,INVALID_PARAMETER_ERROR);
            return Functions::generateResponse($output, $response, HTTP_STATUS_BAD_REQUEST);
        }
    }

}
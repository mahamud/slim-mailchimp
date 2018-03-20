<?php

namespace App\models;
use App\helper\Functions;
use App\libraries\session\Session;
use Psr\Http\Message\ResponseInterface;

/**
 * @property Manager $db;
 * @property Session $session
 * @property ResponseInterface $response
 *
 * Class BaseModel
 * @package App\models
 */
class BaseModel
{
    protected $container;
    protected $db;
    protected $session;
    protected $response;
    protected $accountid;
    protected $userid;
    protected $limit;
    protected $offset;
    protected $filteredColumns = array();


    /**
     * BaseModel constructor.
     * @param $container
     */
    public function __construct($container){
        $this->container = $container;
        $this->offset = 0;
        $this->limit = DEFAULT_DB_LIMIT;
        $this->initializeBaseElements();
    }


    /**
     * @param $offset
     * @param $limit
     * @throws \Exception
     */
    public function setOffsetAndLimit($offset, $limit){
        $this->setOffset($offset);
        $this->setLimit($limit);
    }


    /**
     * @param $limit
     * @throws \Exception
     */
    public function setLimit($limit){
        if(empty($limit) || (int)$limit > DEFAULT_DB_LIMIT){
            $limit = DEFAULT_DB_LIMIT;
        }
        $this->limit = $limit;
    }


    /**
     * @param $offset
     */
    public function setOffset($offset){
        $this->offset = $offset;
    }


    /**
     * @param array $columns
     */
    public function setFilteredColumns(array $columns){
        $this->filteredColumns = $columns;
    }


    /**
     * @param $data
     * @param bool $skipFiltering
     * @return array
     */
    protected function result($data, $skipFiltering = false){
        if($skipFiltering === false) {
        return $this->filterOutputData($data);
        }else{
            return $data;
        }
    }


    /**
     * @param array $data
     * @return array
     */
    private function filterOutputData(array $data){
        return Functions::filterOutputData($this->filteredColumns, $data);
    }


    /**
     *
     */
    private function initializeBaseElements(){
        $this->db = $this->container->database;
        $this->session = $this->container->session;
        $this->response = $this->container->response;
    }


    /**
     * @param $exception
     */
    protected function logException($exception) {
        Functions::logException($exception, 'Application');
    }

}
<?php

namespace App\models;

use App\exception\ApplicationException;
use Lazer\Classes\Database as Lazer;
/**
 * Class UserModel
 * @package App\models
 */
class UserModel extends BaseModel{

    /**
     * @param $parameters
     * @return mixed
     * @throws \Exception
     */
    public function login($parameters){
        try {
            $user = Lazer::table('user')
                ->where('username', '=', $parameters['username'])
                ->andWhere('password', '=', md5($parameters['password']))
                ->find();
            //Fail, password mismatch
            if(empty($user)) {
                throw new ApplicationException(LOGIN_INCORRECT_ERROR_MSG);
            }
            $user = array('ID' => $user->id, 'username' => $user->username);
            return $this->result($user);
        }
        catch(ApplicationException $exception){
            throw new \Exception($exception->getMessage());
        }
        catch(\Exception $exception){
            $this->logException($exception);
            throw new \Exception(UNEXPECTED_DB_ERROR);
        }
    }

}
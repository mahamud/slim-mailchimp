<?php

namespace App\helper;

use DrewM\MailChimp\MailChimp;

/**
 * Wrapper Class built on top fo the PHP-Mailchimp library
 *
 * Class MailAutomation
 * @package App\helper
 */
class MailClient
{

    public $mailchimp;

    /**
     * MailChimp constructor.
     */
    public function __construct(){
        $this->mailchimp = new MailChimp(MAIL_CHIMP_API_KEY);
    }


    /**
     * @return array|false
     */
    public function getList(){
        return $this->mailchimp->get('lists');
    }


    /**
     * @param $data
     * @param null $listId
     * @return bool
     * @throws \Exception
     */
    public function manageList($data, $listId = null){
        $data['contact'] = $this->buildContactObject($data);
        $data['campaign_defaults'] = $this->buildCampaignObject($data);
        if(empty($listId)) {
            $this->mailchimp->post('lists', $data);
        }else{
            $this->mailchimp->patch('lists/'.$listId, $data);
        }
        if($this->mailchimp->success()) {
           return true;
        } else {
           throw new \Exception($this->getErrors());
        }
    }


    /**
     * @param $listId
     * @return bool
     * @throws \Exception
     */
    public function deleteList($listId){
        $this->mailchimp->delete('/lists/'.$listId);
        if($this->mailchimp->success()) {
            return true;
        } else {
            throw new \Exception($this->getErrors());
        }
    }


    /**
     * @param $listId
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function addMemberToList($listId, $data){
        $this->mailchimp->post('/lists/'.$listId.'/members', $data);
        if($this->mailchimp->success()) {
            return true;
        } else {
            throw new \Exception($this->getErrors());
        }
    }


    /**
     * @param $listId
     * @param $memberHash
     * @return bool
     * @throws \Exception
     */
    public function removeMemberFromList($listId, $memberHash){
        $this->mailchimp->delete('/lists/'.$listId.'/members/'.$memberHash);
        if($this->mailchimp->success()) {
            return true;
        } else {
            throw new \Exception($this->getErrors());
        }
    }


    /**
     * @return mixed
     */
    protected function getErrors(){
        $errorObject = json_decode($this->mailchimp->getLastResponse()['body']);
        return $errorObject->errors[0]->message;
    }


    /**
     * @param $data
     * @return \stdClass
     */
    private function buildContactObject($data){
        $contact = new \stdClass();
        $contact->company = $data['company'];
        $contact->address1 = $data['address1'];
        $contact->city = $data['city'];
        $contact->state = $data['state'];
        $contact->zip = $data['zip'];
        $contact->country = $data['country'];
        return $contact;
    }


    /**
     * @param $data
     * @return \stdClass
     */
    private function buildCampaignObject($data){
        $campaign = new \stdClass();
        $campaign->from_name = $data['from_name'];
        $campaign->from_email = $data['from_email'];
        $campaign->subject = $data['subject'];
        $campaign->language = $data['language'];
        return $campaign;
    }


    /**
     *
     */
    public function updateList(){

    }

}
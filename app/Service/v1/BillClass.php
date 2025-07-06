<?php

namespace App\Service\v1;

use App\Helper\OpenParliamentClass;
use App\Helper\XmlReaderClass;

class BillClass
{
    private $openParliamentClass;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->openParliamentClass = new OpenParliamentClass();
    }

    public function getAllBills(){
        return $this->openParliamentClass->getPolicyInformation('/bills/?session=45-1');
    }

    public function getGovernmentBills(){
        return $this->openParliamentClass->getPolicyInformation('/bills/?session=45-1&private_member_bill=false');
    }

    public function getPrivateMemberBills(){
        return $this->openParliamentClass->getPolicyInformation('/bills/?session=45-1&private_member_bill=true');
    }

    public function nextBillsPageUrl($url){
        return $this->openParliamentClass->getPolicyInformation($url);
    }

    public function previousBillsPageUrl($url){
        return $this->openParliamentClass->getPolicyInformation($url);
    }

    public function getBill($url){
        return $this->openParliamentClass->getPolicyInformation($url);
    }

    public function getBillSummary($url){
        return $this->openParliamentClass->getParlCaInformation($url);
    }

    public function getBillVotes($vote_urls = []){
        $descriptions = [];
        foreach($vote_urls as $vote_url){
            $descriptions[] = $this->openParliamentClass->getPolicyInformation($vote_url)['description']['en'];
        }

        return $descriptions;
    }

    

    


}

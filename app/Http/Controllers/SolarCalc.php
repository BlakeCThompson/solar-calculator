<?php

namespace App\Http\Controllers;

use App\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Request;

class SolarCalc extends Controller
{
    public function __invoke()
    {

    }

    /**
     * @var $RMPTiers array
     * tiers represent the tiered rates.
     * s1-kwh is the number of kWh that the first Summer tier encompasses. (Summer is June - Sept inclusive)
     * s2-kwh is the number of kWh that the second Summer tier encompasses, or a ridiculously high number to represent infinite for all values greater than previous.
     * s1-cost is the cost in dollars per kWh purchased in the first tier in summer months
     * s2-cost is the cost in dollars per kWh purchased in the second tier in summer months
     * Winter months follow a similar format, but with a 'w' instead of an 's'. (Winter is Oct - May inclusive)
     * e.g. w1-kwh, w1-cost, etc.
     */
    private $RMPTiers = [
        's1-kwh' => 400,
        's2-kwh' => 10000000000000000,
        's1-cost' => 0.092802,
        's2-cost' => 0.119733,
        's-solar-buyback' => 0.05817,
        'w1-kwh' => 400,
        'w2-kwh' => 10000000000000000,
        'w1-cost' => 0.082126,
        'w2-cost' => 0.105959,
        'w-solar-buyback' => 0.05487,
    ];
    private $fixedSolarCost =0;
    private $tiers = [];
    private $seasonBlock;
    private $runningTotal;

    private function setConfigs($configs = '')
    {
        if (!$configs) {
            $this->seasonBlock = 's';
            $this->tiers = $this->RMPTiers;
            $this->fixedSolarCost = 115;
        }
        else{
            $this->tiers = $configs['tiers']?? $this->RMPTiers;
            $this->seasonBlock = $configs['seasonBlock'];
            $this->fixedSolarCost = $configs['fixed-solar-cost'] ?? 115;
        }
    }

    public function calcSavings(Request $request)
    {
        $this->setConfigs($request->input());
        $kWhPurchased = $request->input('kWhPurchased');
        $kWhPushed = $request->input('kWhPushed');
        $kWhProduced = $request->input('kWhProduced');
        $highestTier = $this->findStartingTier($kWhPurchased);



        $kwhUsedLocally = $kWhProduced - $kWhPushed;
        $this->runningTotal = $kwhUsedLocally;
        $savingsInLocalUse = $this->getSavingsFromTier($highestTier,$kwhUsedLocally,$kWhPurchased - $this->tiers[$this->seasonBlock . $highestTier . '-kwh']);
        $savings = $savingsInLocalUse + $kWhPushed * $this->tiers[$this->seasonBlock.'-solar-buyback'] - $this->fixedSolarCost;


        return ['savings' => $savings];
    }

    private function findStartingTier($kwhUsed){
        $highestTierUsed = 1;

        while($kwhUsed > $this->tiers[$this->seasonBlock . $highestTierUsed . '-kwh']){
            $highestTierUsed++;
        }
        return $highestTierUsed;
    }

    private function getSavingsFromTier($tierNumber, $kwhCredits, $creditsPurchasedInTier=0){
        $tierLimit = $this->tiers[$this->seasonBlock . $tierNumber . '-kwh'];
        $toNextTier = $tierLimit - $creditsPurchasedInTier;
        //if we have not got enough credits to get to the next tier:


        if($toNextTier - $kwhCredits >= 0){
            return $kwhCredits * $this->tiers[$this->seasonBlock . $tierNumber . '-cost'];
        }
        else{//We have more credits than the limit of this tier
             /**
              *How many credits were used in this tier?
              */
            $usedInThisTier = $tierLimit - $creditsPurchasedInTier;
            $kwhCredits = $kwhCredits - $usedInThisTier;
            error_log($tierNumber. " " .$usedInThisTier * $this->tiers[$this->seasonBlock . $tierNumber . '-cost'],3,'/Users/blakethompson/projects/mylogfile');

            return ($usedInThisTier * $this->tiers[$this->seasonBlock . $tierNumber . '-cost']) + $this->getSavingsFromTier($tierNumber+1,$kwhCredits);
        }

}

}

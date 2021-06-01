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
     * s2-kwh is the number of kWh that the second Summer tier encompasses, or "inf" for all values greater than previous.
     * s1-cost is the cost in dollars per kWh purchased in the first tier in summer months
     * s2-cost is the cost in dollars per kWh purchased in the second tier in summer months
     * Winter months follow a similar format, but with a 'w' instead of an 's'. (Winter is Oct - May inclusive)
     * e.g. w1-kwh, w1-cost, etc.
     */
    private $RMPTiers = [
        's1-kwh' => 400,
        's2-kwh' => 'inf',
        's1-cost' => 0.092802,
        's2-cost' => 0.119733,
        's-solar-buyback' => 0.05817,
        'w1-kwh' => 400,
        'w2-kwh' => 'inf',
        'w1-cost' => 0.082126,
        'w2-cost' => 0.105959,
        'w-solar-buyback' => 0.05487,
    ];
    private $configs = [];

    private function setConfigs($tiers = '')
    {
        if (!$tiers) {
            $this->configs = [
                'tiers' => $this->RMPTiers,
                'fixed-solar-cost' => 115,
            ];
        }
    }

    public function calcSavings(Request $request)
    {
        $this->setConfigs();
        $kWhPurchased = $request->input('kWhPurchased');
        $kWhPushed = $request->input('kWhPushed');
        $kWhProduced = $request->input('kWhProduced');
        $seasonBlock = $request->input('seasonBlock');
        $t1kwh = 0;
        $t2kwh = 0;
        $t1cost = 0;
        $t2cost = 0;
        $savings = 0;
        $buyBackRate = 0;
        switch ($seasonBlock) {
            case('w'):
            {
                $t1kwh = $this->configs['tiers']['w1-kwh'];
                $t2kwh = $this->configs['tiers']['w2-kwh'];
                $t1cost = $this->configs['tiers']['w1-cost'];
                $t2cost = $this->configs['tiers']['w2-cost'];
                $buyBackRate = $this->configs['tiers']['w-solar-buyback'];
            }
            case('s'):
            {
                $t1kwh = $this->configs['tiers']['s1-kwh'];
                $t2kwh = $this->configs['tiers']['s2-kwh'];
                $t1cost = $this->configs['tiers']['s1-cost'];
                $t2cost = $this->configs['tiers']['s2-cost'];
                $buyBackRate = $this->configs['tiers']['s-solar-buyback'];
            }
        }
        $highTier = max(($kWhPurchased - $t1kwh), 0);
        $lowTier = $kWhPurchased - $highTier;
        $kwhUsedLocally = $kWhProduced - $kWhPushed;

        //return $t2cost;
        if ($highTier > 0) {
            $savings = ($kwhUsedLocally * $t2cost + $kWhPushed * $buyBackRate) - $this->configs['fixed-solar-cost'];
        } else {
            $savings = 0;
        }

        return ['savings' => $savings];
    }
}

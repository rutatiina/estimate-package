<?php

namespace Rutatiina\Estimate\Classes;

use Rutatiina\Estimate\Models\Estimate;

class Read
{

    public function __construct()
    {}

    public function run($id)
    {
        $Txn = Estimate::find($id);

        if ($Txn) {
            //txn has been found so continue normally
        } else {
            $this->errors[] = 'Transaction not found';
            return false;
        }

        $Txn->load('contact', 'financial_account', 'items.taxes');
        $Txn->setAppends(['taxes']);

        $txnDescription = [];

        foreach ($Txn->items as &$item) {

            if (empty($item->name)) {
                $txnDescription[] = $item->description;
            }
            else {
                $txnDescription[] = (empty($item->description)) ? $item->name : $item->name . ': ' . $item->description;
            }

            //If item is a transaction, get the transaction details
            if ($item->type == 'txn') {
                $item->transaction = Txn::with('type', 'debit_account', 'credit_account')->find($item->type_id);
            }
        }

        $Txn->description = implode(',', $txnDescription);

        $f = new \NumberFormatter( locale_get_default(), \NumberFormatter::SPELLOUT );
        $Txn->total_in_words = ucfirst($f->format($Txn->total));

        return $Txn->toArray();

    }

}

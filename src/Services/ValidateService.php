<?php

namespace Rutatiina\Estimate\Services;

use Illuminate\Support\Facades\Validator;
use Rutatiina\Contact\Models\Contact;
use Rutatiina\Estimate\Models\Setting;

class ValidateService
{
    public static $errors = [];

    public static function run($requestInstance)
    {
        //$request = request(); //used for the flash when validation fails
        $user = auth()->user();


        // >> data validation >>------------------------------------------------------------

        //validate the data
        $customMessages = [
            //'total.in' => "Item total is invalid:\nItem total = item rate x item quantity",

            'items.*.taxes.*.code.required' => "Tax code is required",
            'items.*.taxes.*.total.required' => "Tax total is required",
            //'items.*.taxes.*.exclusive.required' => "Tax exclusive amount is required",
        ];

        $rules = [
            'contact_id' => 'required|numeric',
            'date' => 'required|date',
            'base_currency' => 'required',
            'document_id' => 'numeric|nullable',
            'salesperson_contact_id' => 'numeric|nullable',
            'memo' => 'string|nullable',

            'items' => 'required|array',
            'items.*.name' => 'required_without:type_id',
            'items.*.rate' => 'required|numeric',
            'items.*.quantity' => 'required|numeric|gt:0',
            //'items.*.total' => 'required|numeric|in:' . $itemTotal, //todo custom validator to check this
            'items.*.units' => 'numeric|nullable',
            'items.*.taxes' => 'array|nullable',

            'items.*.taxes.*.code' => 'required',
            'items.*.taxes.*.total' => 'required|numeric',
            //'items.*.taxes.*.exclusive' => 'required|numeric',
        ];

        $validator = Validator::make($requestInstance->all(), $rules, $customMessages);

        if ($validator->fails())
        {
            self::$errors = $validator->errors()->all();
            return false;
        }

        // << data validation <<------------------------------------------------------------

        $settings = Setting::has('financial_account')->with(['financial_account'])->firstOrFail();
        //Log::info($this->settings);


        $contact = Contact::findOrFail($requestInstance->contact_id);



        $data['user_id'] = $user->id;
        $data['tenant_id'] = $user->tenant->id;
        $data['created_by'] = $user->name;
        $data['app'] = 'web';
        $data['document_name'] = $settings->document_name;
        $data['number_prefix'] = $settings->number_prefix;
        $data['number'] = $requestInstance->input('number');
        $data['number_length'] = $settings->minimum_number_length;
        $data['number_postfix'] = $settings->number_postfix;
        $data['date'] = $requestInstance->input('date');
        $data['financial_account_code'] = $settings->financial_account->code;
        $data['contact_id'] = $requestInstance->contact_id;
        $data['contact_name'] = $contact->name;
        $data['contact_address'] = trim($contact->shipping_address_street1 . ' ' . $contact->shipping_address_street2);
        $data['reference'] = $requestInstance->input('reference', null);
        $data['base_currency'] =  $requestInstance->input('base_currency');
        $data['quote_currency'] =  $requestInstance->input('quote_currency', $data['base_currency']);
        $data['exchange_rate'] = $requestInstance->input('exchange_rate', 1);
        $data['salesperson_contact_id'] = $requestInstance->input('salesperson_contact_id', null);
        $data['branch_id'] = $requestInstance->input('branch_id', null);
        $data['store_id'] = $requestInstance->input('store_id', null);
        $data['expiry_date'] = $requestInstance->input('expiry_date', null);
        $data['terms_and_conditions'] = $requestInstance->input('terms_and_conditions', null);
        $data['memo'] = $requestInstance->input('memo', null);
        $data['status'] = $requestInstance->input('status', null);


        //set the transaction total to zero
        $txnTotal = 0;
        $taxableAmount = 0;

        //Formulate the DB ready items array
        $data['items'] = [];
        foreach ($requestInstance->items as $key => $item)
        {
            $itemTaxes = $requestInstance->input('items.'.$key.'.taxes', []);

            $txnTotal           += ($item['rate']*$item['quantity']);
            $taxableAmount      += ($item['rate']*$item['quantity']);
            $itemTaxableAmount   = ($item['rate']*$item['quantity']); //calculate the item taxable amount

            foreach ($itemTaxes as $itemTax)
            {
                $txnTotal           += $itemTax['exclusive'];
                $taxableAmount      -= $itemTax['inclusive'];
                $itemTaxableAmount  -= $itemTax['inclusive']; //calculate the item taxable amount more by removing the inclusive amount
            }

            $data['items'][] = [
                'tenant_id' => $data['tenant_id'],
                'created_by' => $data['created_by'],
                'contact_id' => $item['contact_id'],
                'item_id' => $item['item_id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'rate' => $item['rate'],
                'total' => $item['total'],
                'taxable_amount' => $itemTaxableAmount,
                'units' => $requestInstance->input('items.'.$key.'.units', null),
                'batch' => $requestInstance->input('items.'.$key.'.batch', null),
                'expiry' => $requestInstance->input('items.'.$key.'.expiry', null),
                'taxes' => $itemTaxes,
            ];
        }

        $data['taxable_amount'] = $taxableAmount;
        $data['total'] = $txnTotal;


        $data['ledgers'][] = [
            'financial_account_code' => $settings->financial_account->code,
            'effect' => 'debit',
            'total' => $data['total'],
            'contact_id' => $data['contact_id']
        ];

        //print_r($data); exit;

        //Now add the default values to items and ledgers

        foreach ($data['ledgers'] as &$ledger)
        {
            $ledger['tenant_id'] = $data['tenant_id'];
            $ledger['date'] = date('Y-m-d', strtotime($data['date']));
            $ledger['base_currency'] = $data['base_currency'];
            $ledger['quote_currency'] = $data['quote_currency'];
            $ledger['exchange_rate'] = $data['exchange_rate'];
        }
        unset($ledger);

        //Return the array of txns
        //print_r($data); exit;

        return $data;

    }

}

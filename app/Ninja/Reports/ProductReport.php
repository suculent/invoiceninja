<?php

namespace App\Ninja\Reports;

use App\Models\Client;
use Auth;
use Utils;

class ProductReport extends AbstractReport
{
    public function getColumns()
    {
        $columns = [
            'client' => [],
            'invoice_number' => [],
            'invoice_date' => [],
            'product' => [],
            'description' => [],
            'qty' => [],
            'cost' => [],
            //'tax_rate1',
            //'tax_rate2',
        ];

        $account = auth()->user()->account;

        if ($account->invoice_item_taxes) {
            $columns['tax'] = ['columnSelector-false'];
            if ($account->enable_second_tax_rate) {
                $columns['tax'] = ['columnSelector-false'];
            }
        }

        if ($account->custom_invoice_item_label1) {
            $columns[$account->present()->customProductLabel1] = ['columnSelector-false', 'custom'];
        }

        if ($account->custom_invoice_item_label2) {
            $columns[$account->present()->customProductLabel2] = ['columnSelector-false', 'custom'];
        }

        return $columns;
    }

    public function run()
    {
        $account = Auth::user()->account;
        $statusIds = $this->options['status_ids'];

        $clients = Client::scope()
                        ->orderBy('name')
                        ->withArchived()
                        ->with('contacts')
                        ->with(['invoices' => function ($query) use ($statusIds) {
                            $query->invoices()
                                  ->withArchived()
                                  ->statusIds($statusIds)
                                  ->where('invoice_date', '>=', $this->startDate)
                                  ->where('invoice_date', '<=', $this->endDate)
                                  ->with(['invoice_items']);
                        }]);

        foreach ($clients->get() as $client) {
            foreach ($client->invoices as $invoice) {
                foreach ($invoice->invoice_items as $item) {
                    $row = [
                        $this->isExport ? $client->getDisplayName() : $client->present()->link,
                        $this->isExport ? $invoice->invoice_number : $invoice->present()->link,
                        $invoice->present()->invoice_date,
                        $item->product_key,
                        $item->notes,
                        Utils::roundSignificant($item->qty, 0),
                        Utils::roundSignificant($item->cost, 2),
                    ];

                    if ($account->invoice_item_taxes) {
                        $row[] = $item->present()->tax1;
                        if ($account->enable_second_tax_rate) {
                            $row[] = $item->present()->tax2;
                        }
                    }

                    if ($account->custom_invoice_item_label1) {
                        $row[] = $item->custom_value1;
                    }

                    if ($account->custom_invoice_item_label2) {
                        $row[] = $item->custom_value2;
                    }

                    $this->data[] = $row;

                }

                //$this->addToTotals($client->currency_id, 'paid', $payment ? $payment->getCompletedAmount() : 0);
                //$this->addToTotals($client->currency_id, 'amount', $invoice->amount);
                //$this->addToTotals($client->currency_id, 'balance', $invoice->balance);
            }
        }
    }
}

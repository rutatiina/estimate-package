<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRgEstimatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->create('rg_estimates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();

            //>> default columns
            $table->softDeletes();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            //<< default columns

            //>> table columns
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('external_key', 100)->nullable();
            $table->unsignedBigInteger('app_id')->nullable();
            $table->string('document_name', 50)->default('Quotation');
            $table->string('number_prefix', 50)->nullable();
            $table->unsignedBigInteger('number');
            $table->unsignedTinyInteger('number_length');
            $table->string('number_postfix', 50)->nullable();
            $table->date('date');
            $table->time('time')->nullable();
            $table->unsignedBigInteger('financial_account_code')->nullable(); //this is a non accounting entry so no need for debit or credit
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('contact_name', 50);
            $table->string('contact_address', 50);
            $table->string('reference', 100)->nullable();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3)->nullable();
            $table->unsignedDecimal('exchange_rate', 20,10);
            $table->unsignedDecimal('taxable_amount', 20,5);
            $table->unsignedDecimal('total', 20, 5);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->date('due_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->nullable();
            $table->unsignedTinyInteger('sent')->nullable();
            $table->unsignedBigInteger('salesperson_contact_id')->nullable();
            $table->string('memo', 250)->nullable();
            $table->string('terms_and_conditions', 250)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->dropIfExists('rg_estimates');
    }
}

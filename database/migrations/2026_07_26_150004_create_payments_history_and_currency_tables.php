<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->longText('expense_type');
                $table->unsignedBigInteger('expense_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->string('payment_method');
                $table->decimal('amount', 10, 2);
                $table->string('status');
                $table->longText('transaction_keys');
                // Model has $timestamps = false and treats these as plain
                // manually-assigned string columns, not real Eloquent timestamps.
                $table->string('created_at')->nullable();
                $table->string('updated_at')->nullable();
                $table->unsignedBigInteger('school_id')->index();
            });
        }

        if (!Schema::hasTable('payment_history')) {
            Schema::create('payment_history', function (Blueprint $table) {
                $table->id();
                $table->string('payment_type')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('course_id')->nullable();
                $table->unsignedBigInteger('package_id')->nullable()->index();
                $table->float('amount')->nullable();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->longText('transaction_keys')->nullable();
                $table->string('document_image')->nullable();
                $table->string('paid_by')->nullable();
                $table->string('status')->nullable();
                $table->integer('timestamp')->nullable();
                $table->timestamps();
            });
        }

        // Global lookup table (no school_id): superadmin- and admin-facing
        // currency dropdowns. Not per-school.
        if (!Schema::hasTable('currency')) {
            Schema::create('currency', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('code')->nullable();
                $table->string('symbol')->nullable();
                $table->tinyInteger('paypal_supported')->default(0);
                $table->tinyInteger('stripe_supported')->default(0);
                $table->tinyInteger('flutterwave_supported')->default(0);
            });
        }

        // Seed the standard currency list (recovered from the original vendor
        // install.sql) so the payment-settings currency dropdown isn't empty.
        if (Schema::hasTable('currency') && DB::table('currency')->count() === 0) {
            DB::table('currency')->insert(
[
            ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Albanian Lek', 'code' => 'ALL', 'symbol' => 'Lek', 'paypal_supported' => 0, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Algerian Dinar', 'code' => 'DZD', 'symbol' => 'دج', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Angolan Kwanza', 'code' => 'AOA', 'symbol' => 'Kz', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Argentine Peso', 'code' => 'ARS', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Armenian Dram', 'code' => 'AMD', 'symbol' => '֏', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Aruban Florin', 'code' => 'AWG', 'symbol' => 'ƒ', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Australian Dollar', 'code' => 'AUD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Azerbaijani Manat', 'code' => 'AZN', 'symbol' => 'm', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bahamian Dollar', 'code' => 'BSD', 'symbol' => 'B$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bahraini Dinar', 'code' => 'BHD', 'symbol' => '.د.ب', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bangladeshi Taka', 'code' => 'BDT', 'symbol' => '৳', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Barbadian Dollar', 'code' => 'BBD', 'symbol' => 'Bds$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Belarusian Ruble', 'code' => 'BYR', 'symbol' => 'Br', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'Belgian Franc', 'code' => 'BEF', 'symbol' => 'fr', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Belize Dollar', 'code' => 'BZD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bermudan Dollar', 'code' => 'BMD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bhutanese Ngultrum', 'code' => 'BTN', 'symbol' => 'Nu.', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bitcoin', 'code' => 'BTC', 'symbol' => '฿', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bolivian Boliviano', 'code' => 'BOB', 'symbol' => 'Bs.', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bosnia', 'code' => 'BAM', 'symbol' => 'KM', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Botswanan Pula', 'code' => 'BWP', 'symbol' => 'P', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Brazilian Real', 'code' => 'BRL', 'symbol' => 'R$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'British Pound Sterling', 'code' => 'GBP', 'symbol' => '£', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Brunei Dollar', 'code' => 'BND', 'symbol' => 'B$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Bulgarian Lev', 'code' => 'BGN', 'symbol' => 'Лв.', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Burundian Franc', 'code' => 'BIF', 'symbol' => 'FBu', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Cambodian Riel', 'code' => 'KHR', 'symbol' => 'KHR', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Canadian Dollar', 'code' => 'CAD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Cape Verdean Escudo', 'code' => 'CVE', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Cayman Islands Dollar', 'code' => 'KYD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'CFA Franc BCEAO', 'code' => 'XOF', 'symbol' => 'CFA', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'CFA Franc BEAC', 'code' => 'XAF', 'symbol' => 'FCFA', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'CFP Franc', 'code' => 'XPF', 'symbol' => '₣', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Chilean Peso', 'code' => 'CLP', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Chinese Yuan', 'code' => 'CNY', 'symbol' => '¥', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Colombian Peso', 'code' => 'COP', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Comorian Franc', 'code' => 'KMF', 'symbol' => 'CF', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Congolese Franc', 'code' => 'CDF', 'symbol' => 'FC', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Costa Rican Colón', 'code' => 'CRC', 'symbol' => '₡', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Croatian Kuna', 'code' => 'HRK', 'symbol' => 'kn', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Cuban Convertible Peso', 'code' => 'CUC', 'symbol' => '$, CUC', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Czech Republic Koruna', 'code' => 'CZK', 'symbol' => 'Kč', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Danish Krone', 'code' => 'DKK', 'symbol' => 'Kr.', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Djiboutian Franc', 'code' => 'DJF', 'symbol' => 'Fdj', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Dominican Peso', 'code' => 'DOP', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'East Caribbean Dollar', 'code' => 'XCD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Egyptian Pound', 'code' => 'EGP', 'symbol' => 'ج.م', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Eritrean Nakfa', 'code' => 'ERN', 'symbol' => 'Nfk', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Estonian Kroon', 'code' => 'EEK', 'symbol' => 'kr', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Ethiopian Birr', 'code' => 'ETB', 'symbol' => 'Nkf', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Falkland Islands Pound', 'code' => 'FKP', 'symbol' => '£', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Fijian Dollar', 'code' => 'FJD', 'symbol' => 'FJ$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Gambian Dalasi', 'code' => 'GMD', 'symbol' => 'D', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Georgian Lari', 'code' => 'GEL', 'symbol' => 'ლ', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'German Mark', 'code' => 'DEM', 'symbol' => 'DM', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Ghanaian Cedi', 'code' => 'GHS', 'symbol' => 'GH₵', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Gibraltar Pound', 'code' => 'GIP', 'symbol' => '£', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Greek Drachma', 'code' => 'GRD', 'symbol' => '₯, Δρχ, Δρ', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Guatemalan Quetzal', 'code' => 'GTQ', 'symbol' => 'Q', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Guinean Franc', 'code' => 'GNF', 'symbol' => 'FG', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Guyanaese Dollar', 'code' => 'GYD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Haitian Gourde', 'code' => 'HTG', 'symbol' => 'G', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Honduran Lempira', 'code' => 'HNL', 'symbol' => 'L', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Hong Kong Dollar', 'code' => 'HKD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Hungarian Forint', 'code' => 'HUF', 'symbol' => 'Ft', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Icelandic Króna', 'code' => 'ISK', 'symbol' => 'kr', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Indian Rupee', 'code' => 'INR', 'symbol' => '₹', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Indonesian Rupiah', 'code' => 'IDR', 'symbol' => 'Rp', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Iranian Rial', 'code' => 'IRR', 'symbol' => '﷼', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Iraqi Dinar', 'code' => 'IQD', 'symbol' => 'د.ع', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Israeli New Sheqel', 'code' => 'ILS', 'symbol' => '₪', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Italian Lira', 'code' => 'ITL', 'symbol' => 'L,£', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Jamaican Dollar', 'code' => 'JMD', 'symbol' => 'J$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Japanese Yen', 'code' => 'JPY', 'symbol' => '¥', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Jordanian Dinar', 'code' => 'JOD', 'symbol' => 'ا.د', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Kazakhstani Tenge', 'code' => 'KZT', 'symbol' => 'лв', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Kenyan Shilling', 'code' => 'KES', 'symbol' => 'KSh', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Kuwaiti Dinar', 'code' => 'KWD', 'symbol' => 'ك.د', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Kyrgystani Som', 'code' => 'KGS', 'symbol' => 'лв', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Laotian Kip', 'code' => 'LAK', 'symbol' => '₭', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Latvian Lats', 'code' => 'LVL', 'symbol' => 'Ls', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'Lebanese Pound', 'code' => 'LBP', 'symbol' => '£', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Lesotho Loti', 'code' => 'LSL', 'symbol' => 'L', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Liberian Dollar', 'code' => 'LRD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Libyan Dinar', 'code' => 'LYD', 'symbol' => 'د.ل', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Lithuanian Litas', 'code' => 'LTL', 'symbol' => 'Lt', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'Macanese Pataca', 'code' => 'MOP', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Macedonian Denar', 'code' => 'MKD', 'symbol' => 'ден', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Malagasy Ariary', 'code' => 'MGA', 'symbol' => 'Ar', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Malawian Kwacha', 'code' => 'MWK', 'symbol' => 'MK', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Malaysian Ringgit', 'code' => 'MYR', 'symbol' => 'RM', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Maldivian Rufiyaa', 'code' => 'MVR', 'symbol' => 'Rf', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Mauritanian Ouguiya', 'code' => 'MRO', 'symbol' => 'MRU', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Mauritian Rupee', 'code' => 'MUR', 'symbol' => '₨', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Mexican Peso', 'code' => 'MXN', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Moldovan Leu', 'code' => 'MDL', 'symbol' => 'L', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Mongolian Tugrik', 'code' => 'MNT', 'symbol' => '₮', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Moroccan Dirham', 'code' => 'MAD', 'symbol' => 'MAD', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Mozambican Metical', 'code' => 'MZM', 'symbol' => 'MT', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Myanmar Kyat', 'code' => 'MMK', 'symbol' => 'K', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Namibian Dollar', 'code' => 'NAD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Nepalese Rupee', 'code' => 'NPR', 'symbol' => '₨', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Netherlands Antillean Guilder', 'code' => 'ANG', 'symbol' => 'ƒ', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'New Taiwan Dollar', 'code' => 'TWD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'New Zealand Dollar', 'code' => 'NZD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Nicaraguan Córdoba', 'code' => 'NIO', 'symbol' => 'C$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'North Korean Won', 'code' => 'KPW', 'symbol' => '₩', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'Norwegian Krone', 'code' => 'NOK', 'symbol' => 'kr', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Omani Rial', 'code' => 'OMR', 'symbol' => '.ع.ر', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'Pakistani Rupee', 'code' => 'PKR', 'symbol' => '₨', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Panamanian Balboa', 'code' => 'PAB', 'symbol' => 'B/.', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Papua New Guinean Kina', 'code' => 'PGK', 'symbol' => 'K', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Paraguayan Guarani', 'code' => 'PYG', 'symbol' => '₲', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Peruvian Nuevo Sol', 'code' => 'PEN', 'symbol' => 'S/.', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Philippine Peso', 'code' => 'PHP', 'symbol' => '₱', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Polish Zloty', 'code' => 'PLN', 'symbol' => 'zł', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Qatari Rial', 'code' => 'QAR', 'symbol' => 'ق.ر', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Romanian Leu', 'code' => 'RON', 'symbol' => 'lei', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Russian Ruble', 'code' => 'RUB', 'symbol' => '₽', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Rwandan Franc', 'code' => 'RWF', 'symbol' => 'FRw', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Salvadoran Colón', 'code' => 'SVC', 'symbol' => '₡', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'Samoan Tala', 'code' => 'WST', 'symbol' => 'SAT', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => '﷼', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Serbian Dinar', 'code' => 'RSD', 'symbol' => 'din', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Seychellois Rupee', 'code' => 'SCR', 'symbol' => 'SRe', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Sierra Leonean Leone', 'code' => 'SLL', 'symbol' => 'Le', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Singapore Dollar', 'code' => 'SGD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Slovak Koruna', 'code' => 'SKK', 'symbol' => 'Sk', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Solomon Islands Dollar', 'code' => 'SBD', 'symbol' => 'Si$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Somali Shilling', 'code' => 'SOS', 'symbol' => 'Sh.so.', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'South African Rand', 'code' => 'ZAR', 'symbol' => 'R', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'South Korean Won', 'code' => 'KRW', 'symbol' => '₩', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Special Drawing Rights', 'code' => 'XDR', 'symbol' => 'SDR', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Sri Lankan Rupee', 'code' => 'LKR', 'symbol' => 'Rs', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'St. Helena Pound', 'code' => 'SHP', 'symbol' => '£', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Sudanese Pound', 'code' => 'SDG', 'symbol' => '.س.ج', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Surinamese Dollar', 'code' => 'SRD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Swazi Lilangeni', 'code' => 'SZL', 'symbol' => 'E', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Swedish Krona', 'code' => 'SEK', 'symbol' => 'kr', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Swiss Franc', 'code' => 'CHF', 'symbol' => 'CHf', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Syrian Pound', 'code' => 'SYP', 'symbol' => 'LS', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'São Tomé and Príncipe Dobra', 'code' => 'STD', 'symbol' => 'Db', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Tajikistani Somoni', 'code' => 'TJS', 'symbol' => 'SM', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Tanzanian Shilling', 'code' => 'TZS', 'symbol' => 'TSh', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Thai Baht', 'code' => 'THB', 'symbol' => '฿', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Tongan pa\'anga', 'code' => 'TOP', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Trinidad & Tobago Dollar', 'code' => 'TTD', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Tunisian Dinar', 'code' => 'TND', 'symbol' => 'ت.د', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Turkish Lira', 'code' => 'TRY', 'symbol' => '₺', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Turkmenistani Manat', 'code' => 'TMT', 'symbol' => 'T', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Ugandan Shilling', 'code' => 'UGX', 'symbol' => 'UGX', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'Ukrainian Hryvnia', 'code' => 'UAH', 'symbol' => '₴', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'United Arab Emirates Dirham', 'code' => 'AED', 'symbol' => 'إ.د', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Uruguayan Peso', 'code' => 'UYU', 'symbol' => '$', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Afghan Afghani', 'code' => 'AFA', 'symbol' => '؋', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Uzbekistan Som', 'code' => 'UZS', 'symbol' => 'лв', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Vanuatu Vatu', 'code' => 'VUV', 'symbol' => 'VT', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Venezuelan Bolívar', 'code' => 'VEF', 'symbol' => 'Bs', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 0],
            ['name' => 'Vietnamese Dong', 'code' => 'VND', 'symbol' => '₫', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Yemeni Rial', 'code' => 'YER', 'symbol' => '﷼', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 0],
            ['name' => 'Zambian Kwacha', 'code' => 'ZMK', 'symbol' => 'ZK', 'paypal_supported' => 1, 'stripe_supported' => 1, 'flutterwave_supported' => 1],
            ['name' => 'PesosColombian Peso', 'code' => 'COP', 'symbol' => '$', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 1],
            ['name' => 'SEPA', 'code' => 'EUR', 'symbol' => '€', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 1],
            ['name' => 'Mozambican Metical', 'code' => 'MZN', 'symbol' => 'MT', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 1],
            ['name' => 'Peruvian Sol', 'code' => 'SOL', 'symbol' => 'S/', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 1],
            ['name' => 'Zambian Kwacha', 'code' => 'ZMW', 'symbol' => 'ZK', 'paypal_supported' => 0, 'stripe_supported' => 0, 'flutterwave_supported' => 1],
        ]
            );
        }
    }

    public function down()
    {
        Schema::dropIfExists('currency');
        Schema::dropIfExists('payment_history');
        Schema::dropIfExists('payments');
    }
};
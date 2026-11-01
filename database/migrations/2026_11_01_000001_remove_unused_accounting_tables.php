<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');

        DB::table('roles')->where('name', 'finance')->update([
            'description' => 'Reports',
        ]);
    }

    public function down(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->index('entry_date', 'idx_je_date');
            $table->index(['source_type', 'source_id'], 'idx_je_source');
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index('journal_entry_id', 'idx_jl_entry');
            $table->index('account_id', 'idx_jl_account');
        });

        DB::table('roles')->where('name', 'finance')->update([
            'description' => 'Journals and reports',
        ]);
    }
};

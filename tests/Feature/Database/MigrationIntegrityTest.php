<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->originalDefaultConnection = config('database.default');

    config([
        'database.connections.migration_audit' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('migration_audit');
    DB::setDefaultConnection('migration_audit');
});

afterEach(function (): void {
    DB::purge('migration_audit');
    DB::setDefaultConnection($this->originalDefaultConnection);
});

function createMigrationAuditContactSchema(): void
{
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('third_parties', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('contacts', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
        $table->foreignId('third_party_id')->constrained('third_parties')->cascadeOnDelete();
    });
}

it('makes vehicle condition report signature fields nullable', function (): void {
    Schema::create('vehicle_condition_reports', function (Blueprint $table): void {
        $table->id();
        $table->string('signature_checksum');
        $table->dateTime('signed_at');
    });

    $migration = require database_path('migrations/2026_10_02_000001_make_vehicle_condition_report_signature_nullable.php');
    $migration->up();

    $columns = collect(DB::select("PRAGMA table_info('vehicle_condition_reports')"))->keyBy('name');

    expect((int) $columns['signature_checksum']->notnull)->toBe(0)
        ->and((int) $columns['signed_at']->notnull)->toBe(0);

    DB::table('vehicle_condition_reports')->insert([
        'signature_checksum' => null,
        'signed_at' => null,
    ]);

    expect(DB::table('vehicle_condition_reports')->whereNull('signature_checksum')->count())->toBe(1);
});

it('adds a nullable laser order foreign key to manufacturing orders and nulls it on parent deletion', function (): void {
    Schema::create('laser_orders', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('manufacturing_orders', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('customer_order_id')->nullable();
    });

    $migration = require database_path('migrations/2026_10_07_000001_add_laser_order_id_to_manufacturing_orders.php');
    $migration->up();

    $laserOrderId = DB::table('laser_orders')->insertGetId([]);
    $withoutLaserOrderId = DB::table('manufacturing_orders')->insertGetId(['customer_order_id' => null]);
    $withLaserOrderId = DB::table('manufacturing_orders')->insertGetId([
        'customer_order_id' => null,
        'laser_order_id' => $laserOrderId,
    ]);

    expect(DB::table('manufacturing_orders')->where('id', $withoutLaserOrderId)->value('laser_order_id'))->toBeNull()
        ->and(DB::table('manufacturing_orders')->where('id', $withLaserOrderId)->value('laser_order_id'))->toBe($laserOrderId);

    DB::table('laser_orders')->where('id', $laserOrderId)->delete();

    expect(DB::table('manufacturing_orders')->where('id', $withLaserOrderId)->value('laser_order_id'))->toBeNull();

    $migration->down();

    expect(Schema::hasColumn('manufacturing_orders', 'laser_order_id'))->toBeFalse();
});

it('backfills one account mapping per user and enforces the company pair on contacts', function (): void {
    createMigrationAuditContactSchema();

    DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    DB::table('third_parties')->insert([['id' => 10], ['id' => 20]]);
    DB::table('contacts')->insert([
        ['user_id' => 1, 'third_party_id' => 10],
        ['user_id' => 1, 'third_party_id' => 10],
        ['user_id' => 2, 'third_party_id' => 10],
        ['user_id' => null, 'third_party_id' => 20],
    ]);

    $migration = require database_path('migrations/2026_10_10_000001_enforce_single_company_per_user_contacts.php');
    $migration->up();

    expect(DB::table('user_third_party_accounts')->count())->toBe(2)
        ->and(DB::table('user_third_party_accounts')->where('user_id', 1)->value('third_party_id'))->toBe(10)
        ->and(DB::table('user_third_party_accounts')->where('user_id', 2)->value('third_party_id'))->toBe(10);

    DB::table('contacts')->insert(['user_id' => 1, 'third_party_id' => 10]);

    expect(fn () => DB::table('contacts')->insert([
        'user_id' => 1,
        'third_party_id' => 20,
    ]))->toThrow(QueryException::class);

    DB::table('users')->where('id', 2)->delete();

    expect(DB::table('user_third_party_accounts')->where('user_id', 2)->exists())->toBeFalse()
        ->and(DB::table('contacts')->where('user_id', 2)->exists())->toBeFalse();

    DB::table('third_parties')->where('id', 10)->delete();

    expect(DB::table('user_third_party_accounts')->where('third_party_id', 10)->exists())->toBeFalse()
        ->and(DB::table('contacts')->where('third_party_id', 10)->exists())->toBeFalse();

    $migration->down();

    expect(Schema::hasTable('user_third_party_accounts'))->toBeFalse()
        ->and(DB::table('contacts')->count())->toBe(1);
});

it('aborts account mapping migration when a user is linked to multiple companies', function (): void {
    createMigrationAuditContactSchema();

    DB::table('users')->insert(['id' => 1]);
    DB::table('third_parties')->insert([['id' => 10], ['id' => 20]]);
    DB::table('contacts')->insert([
        ['user_id' => 1, 'third_party_id' => 10],
        ['user_id' => 1, 'third_party_id' => 20],
    ]);

    $migration = require database_path('migrations/2026_10_10_000001_enforce_single_company_per_user_contacts.php');

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'users linked to multiple companies: 1');

    expect(Schema::hasTable('user_third_party_accounts'))->toBeFalse();
});

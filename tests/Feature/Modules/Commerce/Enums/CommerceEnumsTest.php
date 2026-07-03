<?php

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\PaymentMethod;
use App\Enums\Commerce\PaymentStatus;
use App\Enums\Commerce\PaymentType;
use App\Enums\Commerce\QuoteStatus;

test('QuoteStatus enum works correctly', function () {
    expect(QuoteStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(QuoteStatus::DRAFT->getColor())->toBe('gray');
        
    expect(QuoteStatus::cases())->not->toBeEmpty();
});

test('InvoiceStatus enum works correctly', function () {
    expect(InvoiceStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(InvoiceStatus::DRAFT->getColor())->toBe('gray');
        
    expect(InvoiceStatus::cases())->not->toBeEmpty();
});

test('OrderStatus enum works correctly', function () {
    expect(OrderStatus::CONFIRMED->getLabel())->toBe('Confirmé')
        ->and(OrderStatus::CONFIRMED->getColor())->toBe('primary');
        
    expect(OrderStatus::cases())->not->toBeEmpty();
});

test('PaymentMethod enum works correctly', function () {
    expect(PaymentMethod::BANK_TRANSFER->getLabel())->toBe('Virement bancaire');
        
    expect(PaymentMethod::cases())->not->toBeEmpty();
});

test('Other enums work correctly', function () {
    expect(DeliveryStatus::cases())->not->toBeEmpty()
        ->and(InvoiceType::cases())->not->toBeEmpty()
        ->and(PaymentStatus::cases())->not->toBeEmpty()
        ->and(PaymentType::cases())->not->toBeEmpty();
});

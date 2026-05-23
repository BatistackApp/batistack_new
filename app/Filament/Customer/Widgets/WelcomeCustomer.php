<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\Widget;

class WelcomeCustomer extends Widget
{
    protected string $view = 'filament.customer.widgets.welcome-customer';
    protected int | string | array $columnSpan = 'full';
}

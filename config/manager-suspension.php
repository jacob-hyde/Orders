<?php

return [
    \App\Models\CuratorOrder::class => [
        'manager_table'     => 'curators',
        'manager_id_column' => 'curator_id',
        'change'            => 'curator_orders.status',
        'change_operation'  => '=',
        'change_value'      => 'pending',
        'manager_relation'  => 'curator',
        'subscriptions'     => ['curator'],
        'times'             => [
            [
                'type'    => '24 HOUR NO FEEDBACK',
                'hours'   => [24, 48],
                'hours_subscription' => [48, 72],
                'count'   => 1,
                'suspend' => false,
                'email'   => App\Mail\PendingOrderStatusChangeEmail::class,
            ],
            [
                'type'    => '48 HOUR NO FEEDBACK/ACCOUNT SUSPENDED',
                'hours'   => [48, 72],
                'hours_subscription' => [72, 96],
                'count'   => 2,
                'suspend' => true,
                'email'   => \App\Mail\AccountSuspendedEmail::class
            ],
        ],
    ],
];
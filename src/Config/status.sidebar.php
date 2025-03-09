<?php

return [
	'status' => [
		'name' => 'Endpoint Status',
		'permission' => 'global.superuser',
		'route_segment' => 'status',
		'icon' => 'fa fa-hand-spock',
		'entries' => [
			[
				'name'  => 'EndpointStatus',
				'label' => 'Endpoint Status',
				'icon' => 'fa fa-hand-spock',
				'route' => 'cryptaendpointstatus::status',
				'permission' => 'global.superuser',
			],
		],
	],
];

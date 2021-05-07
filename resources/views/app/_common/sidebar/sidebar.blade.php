<aside class="sidebar">
    <div class="sidebar-container">
        @include('app/_common/sidebar/header/header')
        <nav class="menu">
            <ul class="sidebar-menu metismenu" id="sidebar-menu">
                <li @if(Route::currentRouteName() == 'home')class="active"@endif>
                    <a href="{{ route('home') }}">{{ __('Home') }}</a>
                </li>
                @if(auth()->user()->hasAnyPermission(['product-list','manufacturer-list','categories-list','substitutes-list','presta-product-attributes-list','product-attributes-list','product-characteristics-list']))
                    <li @if(preg_match('/^(products|manufacturers)\./', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Catalog') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            @can('product-list')
                                <li @if(preg_match('/^products\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('products.index') }}">{{ __('Products') }}</a>
                                </li>
                            @endcan
                            @can('manufacturer-list')
                                <li @if(preg_match('/^manufacturers\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('manufacturers.index') }}">{{ __('Manufacturers') }}</a>
                                </li>
                            @endcan
                            @can('categories-list')
                                <li @if(preg_match('/^categories\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('categories.index') }}">{{ __('Categories') }}</a>
                                </li>
                            @endcan
                            @can('product-attributes-list')
                                <li @if(preg_match('/^product-attributes\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('product-attributes.index') }}">{{ __('Attributes') }}</a>
                                </li>
                            @endcan
                            @can('product-characteristics-list')
                                <li @if(preg_match('/^product-characteristics\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('product-characteristics.index') }}">{{ __('Characteristics') }}</a>
                                </li>
                            @endcan
                            @can('certificate-list')
                                    <li @if(preg_match('/^certificates\./', Route::currentRouteName()))class="active"@endif>
                                        <a href="{{ route('certificates.index') }}">{{ __('Certificates') }}</a>
                                    </li>
                                @endcan
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->hasAnyPermission(['customer-list','orderState-list','orderDetailState-list','order-list','carrier-list','channel-list']))
                    <li @if(preg_match('/^(customers|order-states|order-detail-states|orders|hidden-orders|carriers|channels|product-return-states|product-returns|product-exchange-states|product-exchanges|fast-message-templates|order-alerts)\./', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Orders') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            @can('order-list')
                                <li @if(preg_match('/^orders\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('orders.index') }}">{{ __('Orders') }}</a>
                                </li>
                                <li @if(preg_match('/^hidden-orders\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('hidden-orders.index') }}">{{ __('Hidden Orders') }}</a>
                                </li>
                            @endcan
                            @can('order-list')
                                <li @if(preg_match('/^product-returns\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('product-returns.index') }}">{{ __('Returns') }}</a>
                                </li>
                            @endcan
                            @can('order-list')
                                <li @if(preg_match('/^product-exchanges\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('product-exchanges.index') }}">{{ __('Exchanges') }}</a>
                                </li>
                            @endcan
                            @can('customer-list')
                                <li @if(preg_match('/^customers\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
                                </li>
                            @endcan
                            @can('orderState-list')
                                <li @if(preg_match('/^order-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('order-states.index') }}">{{ __('Order States') }}</a>
                                </li>
                            @endcan
                            @can('orderDetailState-list')
                                <li @if(preg_match('/^order-detail-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('order-detail-states.index') }}">{{ __('Order Detail States') }}</a>
                                </li>
                            @endcan
                            @can('orderState-list')
                                <li @if(preg_match('/^product-return-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('product-return-states.index') }}">{{ __('Return States') }}</a>
                                </li>
                            @endcan
                            @can('orderState-list')
                                <li @if(preg_match('/^product-exchange-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('product-exchange-states.index') }}">{{ __('Exchange States') }}</a>
                                </li>
                            @endcan
                            @can('carrier-list')
                                <li @if(preg_match('/^carriers\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('carriers.index') }}">{{ __('Carriers') }}</a>
                                </li>
                                <li @if(preg_match('/^carrier-groups\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('carrier-group.index') }}">{{ __('Carrier groups') }}</a>
                                </li>
                                <li @if(preg_match('/^cities\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('city.index') }}">{{ __('Cities') }}</a>
                                </li>
                            @endcan
                            @can('channel-list')
                                <li @if(preg_match('/^channels\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('channels.index') }}">{{ __('Order Channels') }}</a>
                                </li>
                            @endcan
                            @can('references-companies-templates-list')
                                <li @if(preg_match('/^references-companies-templates\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('references-companies-templates.index') }}">{{ __('References companies templates') }}</a>
                                </li>
                            @endcan
                            @can('fast-message-template-list')
                                <li @if(preg_match('/^fast-message-templates\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('fast-message-templates.index') }}">{{ __('Fast message templates') }}</a>
                                </li>
                            @endcan
                                @can('substitutes-list')
                                    <li @if(preg_match('/^substitutes\./', Route::currentRouteName()))class="active"@endif>
                                        <a href="{{ route('substitute.index') }}">{{ __('Replacement templates') }}</a>
                                    </li>
                                @endcan
                            @can('orderAlert-list')
                            <li @if(preg_match('/^order-alerts\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('order-alerts.index') }}">{{ __('Orders tasks alerts') }}</a>
                                
                                </li>
                            @endcan
                            @can('substitutes-list')
                                <li @if(preg_match('/^substitutes\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('substitute.index') }}">{{ __('Replacement templates') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->hasAnyPermission(['order-list', 'route-lists-show-own','orderState-list','cdek-states-list']))
                    <li @if(preg_match('/^(route-lists|route-own-lists|route-list-states|route-list-manage|route-point-states)\./', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Shipping') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            @can('order-list')
                                <li @if(preg_match('/^route-list-manage\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('route-list-manage.index') }}">{{ __('Manage Route Lists') }}</a>
                                </li>
                            @endcan
                            @can('order-list')
                                <li @if(preg_match('/^route-lists\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('route-lists.index') }}">{{ __('Route Lists') }}</a>
                                </li>
                            @endcan
                            @can('orderState-list')
                                <li @if(preg_match('/^route-list-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('route-list-states.index') }}">{{ __('Route List States') }}</a>
                                </li>
                                <li @if(preg_match('/^route-point-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('route-point-states.index') }}">{{ __('Route Point States') }}</a>
                                </li>
                            @endcan
                            @can('courier-task')
                                <li @if(preg_match('/^courier-task\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('courier-tasks.index') }}">{{ __('Courier tasks') }}</a>
                                </li>
                                    <li @if(preg_match('/^courier-task-states\./', Route::currentRouteName()))class="active"@endif>
                                        <a href="{{ route('courier-task-states.index') }}">{{ __('Courier task states') }}</a>
                                    </li>
                            @endcan
                            @can('route-lists-show-own')
                                <li @if(preg_match('/^route-own-lists\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('route-own-lists.index') }}">{{ __('My Route List') }}</a>
                                </li>
                            @endcan
                            @can('cdek-states-list')
                                <li @if(preg_match('/^cdek-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('cdek-states.index') }}">{{ __('CDEK states') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->hasAnyPermission(['order-list','orderState-list', 'system-settings']))
                    <li @if(preg_match('/^(task-|tasks|overdue-tasks)\./', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Tasks') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            @can('order-list')
                                <li @if(preg_match('/^tasks\.actual.user$/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('tasks.actual.user', Auth::user()) }}">{{ __('My Actual Tasks') }}</a>
                                </li>
                                <li @if(preg_match('/^tasks\.index/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('tasks.index') }}">{{ __('Tasks') }}</a>
                                </li>
                                <li @if(preg_match('/^tasks\.actual$/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('tasks.actual') }}">{{ __('Actual Tasks') }}</a>
                                </li>
                            @endcan
                            @can('task-manage')
                                <li @if(preg_match('/^task-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('task-states.index') }}">{{ __('Task States') }}</a>
                                </li>
                                <li @if(preg_match('/^task-priorities\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('task-priorities.index') }}">{{ __('Task Priorities') }}</a>
                                </li>
                            @endcan
                            @can('system-settings')
                                <li @if(preg_match('/^task-types\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('task-types.index') }}">{{ __('Task Types') }}</a>
                                </li>
                            @endcan
                            @can('overdueTask-list')
                            <li @if(preg_match('/^overdue-tasks\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('overdue-tasks.index') }}">{{ __('Overdue tasks alerts') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->hasAnyPermission(['store-list','cashbox-list','currency-list','cashbox-search','currency-list','store-autotransfer-setting-list','transfer-iterations-list']))
                    <li @if(preg_match('/^(stores|cashboxes|store-operations|currencies)\./', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Store & Cashbox') }}<i class="fa arrow"></i>
                        </a>
                        <ul class="sidebar-nav">
                            @can('store-list')
                                <li @if(preg_match('/^(stores|store-operations)\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('stores.index') }}">{{ __('Stores') }}</a>
                                </li>
                            @endcan
                            @can('cashbox-list')
                                <li @if(preg_match('/^cashboxes\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('cashboxes.index') }}">{{ __('Cashboxes') }}</a>
                                </li>
                            @endcan
                            @can('cashbox-search')
                                <li @if(preg_match('/^(cashboxes-search)\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('cashbox.search') }}">{{ __('Cashbox Search') }}</a>
                                </li>
                            @endcan
                            @can('currency-list')
                                <li @if(preg_match('/^currencies\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('currencies.index') }}">{{ __('Currencies') }}</a>
                                </li>
                            @endcan
                            @can('store-autotransfer-setting-list')
                                <li @if(preg_match('/^store-autotransfer-settings\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('store-autotransfer-settings.index') }}">{{ __('Stores auto transfer settings') }}</a>
                                </li>
                            @endcan
                            @can('transfer-iterations-list')
                                <li @if(preg_match('/^transfer-iterations\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('transfer-iterations.index') }}">{{ __('Transfer iterations') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->hasAnyPermission(['store-show-own','cashbox-show-own']))
                    @can('store-show-own')
                        <li @if(preg_match('/^(own-stores)\./', Route::currentRouteName()))class="active open"@endif>
                            <a href=""><i class="fa fa-th-large"></i> {{ __('My Stores') }}<i class="fa arrow"></i></a>
                            <ul class="sidebar-nav">
                                @foreach(auth()->user()->stores as $store)
                                    @if(!$store->is_hidden)
                                    <li @if(preg_match('/^own-stores\./', Route::currentRouteName()) && Route::input('store')->id == $store->id)class="active"@endif>
                                        <a href="{{ route('own-stores.show', ['store' => $store]) }}">{{ $store->name }}</a>
                                    </li>
                                    @endif
                                @endforeach
                            </ul>
                        </li>
                    @endcan
                    @can('cashbox-show-own')
                        <li @if(preg_match('/^(own-cashboxes)\./', Route::currentRouteName()))class="active open"@endif>
                            <a href=""><i class="fa fa-th-large"></i> {{ __('My Cashboxes') }}<i class="fa arrow"></i>
                            </a>
                            <ul class="sidebar-nav">
                                @foreach(auth()->user()->cashboxes as $cashbox)
                                    @if(!$cashbox->is_hidden)
                                    <li @if(preg_match('/^own-cashboxes\./', Route::currentRouteName()) && Route::input('cashbox')->id == $cashbox->id)class="active"@endif>
                                        <a href="{{ route('own-cashboxes.show', ['cashbox' => $cashbox]) }}">{{ $cashbox->name }}</a>
                                    </li>
                                    @endif   
                                @endforeach
                            </ul>
                        </li>
                    @endcan
                @endif
                @if(auth()->user()->hasAnyPermission(['ticket-list', 'ticketState-list', 'ticketPriority-list', 'ticketTheme-list','ticketEventCriteria-list','ticketEventActions-list']))
                    <li @if(preg_match('/^ticket\.*/', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Tickets') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            @can('ticket-list')
                                <li @if(preg_match('/^tickets\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('tickets.index') }}">{{ __('Tickets') }}</a>
                                </li>
                            @endcan
                            @can('ticketState-list')
                                <li @if(preg_match('/^ticket-states\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('ticket-states.index') }}">{{ __('Ticket states') }}</a>
                                </li>
                            @endcan
                            @can('ticketPriority-list')
                                <li @if(preg_match('/^ticket-priorities\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('ticket-priorities.index') }}">{{ __('Ticket priorities') }}</a>
                                </li>
                            @endcan
                            @can('ticketTheme-list')
                                <li @if(preg_match('/^ticket-themes\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('ticket-themes.index') }}">{{ __('Ticket themes') }}</a>
                                </li>
                            @endcan
                            @can('ticketEventCriteria-list')
                                <li @if(preg_match('/^ticket-event-criteria\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('ticket-event-criteria.index') }}">{{ __('Ticket event criteria') }}</a>
                                </li>
                            @endcan
                            @can('ticketEventActions-list')
                                <li @if(preg_match('/^ticket-event-actions\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('ticket-event-actions.index') }}">{{ __('Ticket event actions') }}</a>
                                </li>
                            @endcan
                            @can('ticketEventSubscriptions-list')
                                <li @if(preg_match('/^ticket-event-subscriptions\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('ticket-event-subscriptions.index') }}">{{ __('Ticket event subscriptions') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->hasAnyPermission(['analytics-report','parsers-list','analytics-products-list','telegram-report-settings-list','phone-calls-list']))
                    <li @if(preg_match('/^(analytics|utm-groups|campaign-ids)\./', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Analytics') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            @can('parsers-list')
                                <li @if(preg_match('/^product\/parsers\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('product-parsers.index') }}">{{ __('Parsers') }}</a>
                                </li>
                            @endcan
                            @can('expense-setting')
                                <li @if(preg_match('/^expense\/settings\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('expense-settings.index') }}">{{ __('Expense settings') }}</a>
                                </li>
                                    <li @if(preg_match('/^expense\/category\.*/', Route::currentRouteName()))class="active"@endif>
                                        <a href="{{ route('expense-category.index') }}">{{ __('Expense categories') }}</a>
                                    </li>
                                    <li @if(preg_match('/^expense\/settings.*/', Route::currentRouteName()))class="active"@endif>
                                        <a href="{{ route('expense-settings.states.edit') }}">{{ __('Setting states for expenses') }}</a>
                                    </li>
                            @endcan
                            @can('analytics-report')
                                <li @if(preg_match('/^analytics\/report\/sales-by-brands\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.report.sales.by.brands') }}">{{ __('Sales report') }}</a>
                                </li>
                                <li @if(preg_match('/^analytics\/report\/sales-by-products\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.report.sales.by.products') }}">{{ __('Sales report by products') }}</a>
                                </li>
                                <li @if(preg_match('/^analytics\/report\/ads-by-channels\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.report.ads.by.channels') }}">{{ __('Ads report') }}</a>
                                </li>
                                    <li @if(preg_match('/^analytics\/report\/ads-by-channels-with-expenses\.*/', Route::currentRouteName()))class="active"@endif>
                                        <a href="{{ route('analytics.report.ads.by.channels.with.expenses') }}">{{ __('Ads report (expenses)') }}</a>
                                    </li>
                                <li @if(preg_match('/^analytics\/report\/ads-graph-by-channels\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.report.ads.graph.by.channels') }}">{{ __('Ads Graphs') }}</a>
                                </li>
                                <li @if(preg_match('/^analytics\/report\/cdek-delivery\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.report.cdek.delivery') }}">{{ __('CDEK report') }}</a>
                                </li>
                                <li @if(preg_match('/^analytics\/report\/delivery\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.report.delivery.main') }}">{{ __('Delivery report') }}</a>
                                </li>
                                <li @if(preg_match('/^analytics\/users\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.users') }}">{{ __('Users') }}</a>
                                </li>
                                <li @if(preg_match('/^utm-groups\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('utm-groups.index') }}">{{ __('UTM Groups') }}</a>
                                </li>
                            @endcan
                            @if(auth()->user()->can('analytics-products-list') || auth()->user()->can('analytics-products-list-without-wholesale'))
                                <li @if(preg_match('/^analytics\/products\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('analytics.products.show') }}">{{ __('Products') }}</a>
                                </li>
                            @endif
                            @if(auth()->user()->can('telegram-report-settings-list'))
                                <li @if(preg_match('/^telegram\/report\/settings\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('telegram-report-settings.index') }}">{{ __('Telegram report settings') }}</a>
                                </li>
                            @endif
                            @can('analytics-report')
                                <li @if(preg_match('/^campaign-ids\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('campaign-ids.index') }}">{{ __('Campaign ids') }}</a>
                                </li>
                            @endcan
                            @can('phone-calls-list')
                            <li @if(preg_match('/^phone-calls\.*/', Route::currentRouteName()))class="active"@endif>
                                <a href="{{ route('phone.calls') }}">{{ __('Phone calls') }}</a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->can('test'))
                    <li @if(preg_match('/^http-test\.*/', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Tests') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            <li @if(preg_match('/^http-tests\.*/', Route::currentRouteName()))class="active"@endif>
                                <a href="{{ route('http-tests.index') }}">{{ __('HTTP Tests') }}</a>
                            </li>
                            <li @if(preg_match('/^http-test-incidents\.*/', Route::currentRouteName()))class="active"@endif>
                                <a href="{{ route('http-test-incidents.index') }}">{{ __('Incidents') }}</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->can('user-list') || auth()->user()->can('role-list'))
                    <li @if(preg_match('/^(users|roles|telephony-accounts|telephony-account-groups)\./', Route::currentRouteName()))class="active open"@endif>
                        <a href=""><i class="fa fa-th-large"></i> {{ __('Users') }}<i class="fa arrow"></i></a>
                        <ul class="sidebar-nav">
                            @can('user-list')
                                <li @if(preg_match('/^users\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('users.index') }}">{{ __('Users') }}</a>
                                </li>
                            @endcan
                            @can('user-edit')
                                <li @if(preg_match('/^urgent-calls\.*/', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('urgent-call.edit') }}">{{ __('Urgent calls') }}</a>
                                </li>
                            @endcan
                            @can('role-list')
                                <li @if(preg_match('/^roles\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('roles.index') }}">{{ __('Roles') }}</a>
                                </li>
                            @endcan
                                @can('role-list')
                                    <li @if(preg_match('/^roles\./', Route::currentRouteName()))class="active"@endif>
                                        <a href="{{ route('rule-order-permission.index') }}">{{ __('Access to orders') }}</a>
                                    </li>
                                @endcan
                            @can('telephonyAccounts-list')
                                <li @if(preg_match('/^telephony-accounts\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('telephony-accounts.index') }}">{{ __('Telephony accounts') }}</a>
                                </li>
                                <li @if(preg_match('/^telephony-account-groups\./', Route::currentRouteName()))class="active"@endif>
                                    <a href="{{ route('telephony-account-groups.index') }}">{{ __('Telephony account groups') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @can('translate')
                    <li @if(preg_match('/^Barryvdh\\\TranslationManager\\\Controller/', Route::current()->getActionName()))class="active"@endif>
                        <a href="{{ action('\Barryvdh\TranslationManager\Controller@getIndex') }}">{{ __('Translations') }}</a>
                    </li>
                @endcan
            </ul>
        </nav>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="sidebar-mobile-menu-handle" id="sidebar-mobile-menu-handle"></div>

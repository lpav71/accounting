<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');

Route::group(['middleware' => ['auth', 'work-time']], function () {
    Route::resource('roles', 'RoleController')->except(['destroy']);
    Route::resource('users', 'UserController')->except(['destroy']);

    Route::post('users/set-time/{user}', 'UserController@setWorkingTime')->name('users.set-time');

    Route::resource('products', 'ProductController');
    Route::resource('manufacturers', 'ManufacturerController');
    Route::resource('customers', 'CustomerController');
    Route::resource('order-states', 'OrderStateController');
    Route::resource('order-detail-states', 'OrderDetailStateController');
    Route::resource('orders', 'OrderController')->except('destroy');
    Route::post('orders/fast-close', 'OrderController@fastClose')->name('fast.close.order');
    Route::post('orders/{order}/add-comment', 'OrderCommentController@store')->name('orders.comment.add');
    Route::get('hidden-orders', 'OrderController@hiddenIndex')->name('hidden-orders.index');
    Route::get('orders/{order}/pdf', 'OrderController@getPDF')->name('orders.pdf.get');
    Route::get('expense-orders', 'OrderController@expenseOrders')->name('orders.expense.orders');
    Route::get('records/{call}.mp3', 'PhoneController@record')->name('records.call');
    Route::resource('stores', 'StoreController');
    Route::get('stores/find-reserve/{store}', 'StoreController@findReserve')->name('stores.find-reserve');
    Route::get('stores/{store}/current-products', 'StoreController@currentProducts')->name('stores.current.products');
    Route::get('stores/{store}/full-products-brands', 'StoreController@fullProductsBrands')->name('stores.current.products.brands');
    Route::get('stores/{store}/full-products', 'StoreController@fullProducts')->name('stores.full.products');
    Route::get('stores/{store}/csv', 'StoreController@getCSV')->name('stores.csv.get');
    Route::get('stores/{store}/current/csv', 'StoreController@getCurrentCSV')->name('stores.current.csv.get');
    Route::get('stores/csv/dictionary', 'StoreController@getCSVReferenceDictionary')->name('stores.csv.dictionary');
    Route::post('stores/{store}/csv', 'StoreController@importCSV')->name('stores.csv.import');
    Route::get('stores/{store}/transfer', 'StoreController@transfer')->name('stores.transfer');
    Route::patch('stores/{store}/transfer', 'StoreController@transferProduct')->name('stores.transfer.products');
    Route::get('stores/{store}/multi-transfer', 'StoreController@multiTransfer')->name('stores.transfer.multi');
    Route::patch('stores/{store}/multi-transfer', 'StoreController@multiTransferProducts')->name('stores.transfer.multi.products');
    Route::get('stores/{store}/transfer-by-order', 'StoreController@transferByOrderChooseOrder')->name('stores.transfer.by.order.order');
    Route::post('stores/{store}/transfer-by-order', 'StoreController@transferByOrderChooseProduct')->name('stores.transfer.by.order.product');
    Route::patch('stores/{store}/transfer-by-order', 'StoreController@transferByOrderStore')->name('stores.transfer.by.order');
    Route::get('stores/{store}/transfer/csv', 'StoreController@transferCSV')->name('stores.transfer.csv');
    Route::post('stores/{store}/transfer/csv', 'StoreController@transferProductsCSV')->name('stores.transfer.products.csv');
    Route::post('stores/products/quantity', 'StoreController@getProductQuantity')->name('stores.products.quantity');
    Route::get('own-stores/{store}', 'StoreController@showOwn')->name('own-stores.show');
    Route::get('store-operations/{store}/create', 'StoreOperationController@create')->name('store-operations.create');
    Route::post('store-operations/{store}', 'StoreOperationController@store')->name('store-operations.store');
    Route::get('stores/{store}/hide', 'StoreController@hideInMenu')->name('store.hide-in-menu');
    Route::get('stores/{store}/show', 'StoreController@showInMenu')->name('store.show-in-menu');
    Route::resource('tasks', 'TaskController')->except(['show', 'destroy']);
    Route::get('tasks-actual', 'TaskController@actual')->name('tasks.actual');
    Route::get('tasks-actual-user/{user}', 'TaskController@actualForUser')->name('tasks.actual.user');
    Route::resource('task-states', 'TaskStateController')->except(['show', 'destroy']);
    Route::resource('task-priorities', 'TaskPriorityController')->except(['show', 'destroy']);
    Route::resource('task-types', 'TaskTypeController')->except(['show', 'destroy']);
    Route::post('tasks/{task}/add-comment', 'TaskCommentController@store')->name('tasks.comment.add');
    Route::get('tasks/create-from-order/{order}', 'TaskController@create')->name('tasks.create.from.order');
    Route::get('tasks/create-from-customer/{customer}', 'TaskController@create')->name('tasks.create.from.customer');

    Route::resource('/product-parsers', 'ParserController');
    Route::resource('certificates', 'CertificateController')->except(['show']);
    Route::post('product-parsers/csvPrices/{parser}', 'ParserController@csvPrices')->name('parser.csv.prices');
    Route::resource('cashboxes', 'CashboxController');
    Route::get('cashbox-search', 'CashboxController@cashboxSearch')->name('cashbox.search');
    Route::get('cashbox-show-operation', 'CashboxController@showSearchOperation')->name('cashbox.show.operation');
    Route::get('own-cashboxes/{cashbox}', 'CashboxController@showOwn')->name('own-cashboxes.show');
    Route::get('cashbox-operations/{cashbox}/create', 'CashboxOperationController@create')->name('cashbox-operations.create');
    Route::post('cashbox-operations/{cashbox}', 'CashboxOperationController@store')->name('cashbox-operations.store');
    Route::get('cashbox/{cashbox}/transfer', 'CashboxController@transfer')->name('cashbox.transfer');
    Route::patch('cashbox/{cashbox}/transfer', 'CashboxController@transferCashbox')->name('cashbox.transfer.validate');
    Route::get('cashboxes/{cashbox}/hide', 'CashboxController@hideInMenu')->name('cashbox.hide-in-menu');
    Route::get('cashboxes/{cashbox}/show', 'CashboxController@showInMenu')->name('cashbox.show-in-menu');
    Route::resource('currencies', 'CurrencyController');
    Route::post('carriers/calculate-tariff', 'CarrierController@calculateTariff')->name('carriers.tariff');
    Route::resource('carriers', 'CarrierController');
    Route::post('pickups/list', 'PickupPointController@list')->name('pickups.list');
    Route::resource('channels', 'ChannelController')->except(['destroy']);
    Route::get('analytics/products', 'AnalyticsController@productsShow')->name('analytics.products.show');
    Route::get('analytics/products/csv', 'AnalyticsController@productsGetCSV')->name('analytics.products.csv.get');
    Route::post('analytics/products/csv', 'AnalyticsController@productsImportCSV')->name('analytics.products.csv.import');
    Route::get('analytics/report/sales-by-brands', 'AnalyticsController@reportSalesByBrands')->name('analytics.report.sales.by.brands');
    Route::get('analytics/report/sales-by-products', 'AnalyticsController@reportSalesByProducts')->name('analytics.report.sales.by.products');
    Route::get('analytics/report/ads-by-channels', 'AnalyticsController@reportAdsByChannels')->name('analytics.report.ads.by.channels');
    Route::get('analytics/report/ads-by-channels-with-expenses', 'AnalyticsController@reportAdsByChannelsWithExpenses')->name('analytics.report.ads.by.channels.with.expenses');
    Route::resource('expense-settings', 'ExpenseSettingsController')->except('show');
    Route::resource('expense-category', 'ExpenseCategoryController')->except('show');
    Route::get('expense-settings/{expenseSettings}/copy', 'ExpenseSettingsController@copy')->name('expense-settings.copy');
    Route::get('expense-settings/states', 'ExpenseSettingsController@editStates')->name('expense-settings.states.edit');
    Route::post('expense-settings/states', 'ExpenseSettingsController@storeStates')->name('expense-settings.states.store');
    Route::resource('courier-tasks', 'CourierTaskController')->except('show', 'destroy');
    Route::resource('courier-task-states', 'CourierTaskStateController')->except('show');
    Route::get('analytics/report/ads-graph-by-channels', 'AnalyticsController@reportAdsGraphByChannels')->name('analytics.report.ads.graph.by.channels');
    Route::get('analytics/report/cdek-delivery', 'AnalyticsController@reportCdekDelivery')->name('analytics.report.cdek.delivery');
    Route::get('analytics/report/delivery', 'AnalyticsController@reportDeliveryMain')->name('analytics.report.delivery.main');
    Route::get('analytics/users', 'AnalyticsController@users')->name('analytics.users');
    Route::resource('product-return-states', 'ProductReturnStateController')->except(['show', 'destroy']);
    Route::get('product-returns/{order}/create', 'ProductReturnController@create')->name('product-returns.create');
    Route::resource('product-returns', 'ProductReturnController')->except(['create', 'show', 'destroy']);
    Route::resource('product-exchange-states', 'ProductExchangeStateController')->except(['show', 'destroy']);
    Route::get('product-exchanges/{order}/create', 'ProductExchangeController@create')->name('product-exchanges.create');
    Route::resource('product-exchanges', 'ProductExchangeController')->except(['create', 'show', 'destroy']);
    Route::get('product-exchanges/{productExchange}/pdf', 'ProductExchangeController@getPDF')->name('product-exchanges.pdf.get');
    Route::resource('route-lists', 'RouteListController')->except(['show', 'destroy']);
    Route::get('route-lists/pay', 'RouteListController@pay')->name('route-lists.pay');
    Route::get('route-list-manage', 'RouteListController@manage')->name('route-list-manage.index');
    Route::post('route-list-manage', 'RouteListController@manageUpdate')->name('route-list-manage.update');
    Route::post('courier-left/{route_list}', 'RouteListController@courierLeft')->name('courier-left');
    Route::get('route-list-manage/search/{address}', 'RouteListController@search')->name('route-list-manage.search');
    Route::get('own-route-lists', 'RouteListController@indexOwn')->name('route-own-lists.index');
    Route::get('own-route-lists/{route_list}', 'RouteListController@viewOwn')->name('route-own-lists.view');
    Route::post('action-route-lists/{orderDetail}/{orderDetailState}', 'RouteListController@action')->name('route-lists.action');
    Route::post('action-route-lists-point/{routePoint}/{pointObjectState}/{store}', 'RouteListController@actionRoutePoint')->name('route-lists.actionRoutePoint');
    Route::post('action-route-lists-point-object/{routePoint}/{pointObjectRouteList}', 'RouteListController@actionRoutePointObject')->name('route-lists.actionRoutePointObject');
    Route::post('action-own-route-lists/{orderDetail}/{orderDetailState}', 'RouteListController@actionOwn')->name('route-own-lists.action');
    Route::post('action-own-route-lists-task/{courierTask}/{courierTaskState}', 'RouteListController@actionOwnTask')->name('route-own-lists.actionTask');
    Route::resource('route-list-states', 'RouteListStateController')->except(['show', 'destroy']);
    Route::resource('route-point-states', 'RoutePointStateController')->except(['show', 'destroy']);
    Route::post('action-route-point-states/{routePoint}/{routePointState}', 'RoutePointStateController@action')->name('route-point-states.actionRoutePoint');
    Route::post('confirm-operation/{operation}', 'CashboxController@confirmOperationAjax')->name('cashbox.confirm-operation');

    Route::prefix('channels/notificationTemplate')->group(function () {
        Route::get('{channelId}','NotificationTemplateController@index')->name('notificationTemplate.index');
        Route::post('{channelId}/save','NotificationTemplateController@save')->name('notificationTemplate.save');
        Route::post('{channelId}/delete','NotificationTemplateController@ajaxDelete')->name('notificationTemplate.ajaxDelete');
    });

    Route::resource('carrier-types','CarrierTypeController');
    Route::resource('utm-groups', 'UtmGroupController')->except(['show', 'destroy']);

    Route::get('/channelsAjax','ChannelController@indexAjax');
    Route::resource('/presta-product','PrestaProductController');
    Route::resource('/categories','CategoryController');
    Route::resource('/fast-message-templates','FastMessageTemplateController');
    Route::post('/fast-message-send','FastMessageTemplateController@sendByOrder')->name('fast.message.send');
    Route::resource('/product-pictures','ProductPictureController');
    Route::resource('/product-attributes','ProductAttributeController');
    Route::resource('/product-characteristics','ProductCharacteristicController');
    Route::get('/product/importcsv', 'ProductController@getCSV')->name('products.csv.get');
    Route::post('/product/importcsv', 'ProductController@postCsv')->name('products.csv.post');
    Route::post('/product/importcsv-availability', 'ProductController@postCsvAvailability')->name('products.csv.post.availability');
    Route::post('/product/importcsv-availability', 'ProductController@postCsvAvailability')->name('products.csv.post.availability');
    Route::post('/product/importcsv-banned', 'ProductController@postCsvBanned')->name('products.csv.post.banned');
    Route::get('/product/download-csv', 'ProductController@downloadCSV')->name('products.csv.downloadСSV');
    Route::resource('cdek-states','CdekStatesController')->except(['create', 'store', 'destroy']);
    Route::resource('carrier-group', 'CarrierGroupController')->except(['show']);

    Route::prefix('http-tests')->name('http-tests.')->group(function () {
        Route::get('', 'HttpTestController@index')->name('index');
        Route::get('create/{type}', 'HttpTestController@create')->name('create');
        Route::post('store/{type}', 'HttpTestController@store')->name('store');
        Route::get('{http_test}', 'HttpTestController@edit')->name('edit');
        Route::post('{http_test}', 'HttpTestController@update')->name('update');
    });

    Route::prefix('http-test-incidents')->name('http-test-incidents.')->group(function () {
        Route::get('', 'HttpTestIncidentController@index')->name('index');
        Route::get('{http_test_incident}', 'HttpTestIncidentController@view')->name('view');
    });

    Route::post('/map-geo-codes/{mapGeoCode}/add-geo', 'MapGeoCodeController@ajaxAddGeo')->name('api.map-geo-code.add');
    Route::post('/channel-ref-comp/{channel}','ChannelController@referenceCompany')->name('channels.ref-comp');
    Route::resource('/telegram-report-settings','TelegramReportSettingController');
    Route::post('/presta-product/download','PrestaProductController@download')->name('presta-product-download');
    Route::post('/presta-product/upload','PrestaProductController@upload')->name('presta-product-upload');
    Route::post('/presta-product/update-from-channel','PrestaProductController@updateFromChannel')->name('presta-product-update');
    Route::post('/presta-product/copy-to-channel','PrestaProductController@copyToChannel')->name('presta-product-copy');
    Route::post('/presta-product/price-reduction','PrestaProductController@reductionPrice')->name('presta-product-price-reduction');
    Route::post('/presta-product/enable-only-csv','PrestaProductController@enableOnlyCsv')->name('presta-product-enable-only-csv');
    Route::post('/presta-product/new-enable-only-csv','PrestaProductController@newEnableOnlyCsv')->name('presta-product-new-enable-only-csv');
    Route::post('/presta-product/xlsx-prices','PrestaProductController@updatePricesXlsx')->name('presta-product-xlsx-prices');
    Route::post('/presta-product/xlsx-sale-prices','PrestaProductController@updateSalePricesXlsx')->name('presta-product-xlsx-sale-prices');
    Route::post('/presta-product/xlsx-reduction-prices','PrestaProductController@reductionPricesXlsx')->name('presta-product-xlsx-reduction-prices');
    Route::post('/presta-product/update-all-availability','PrestaProductController@uploadAvailability')->name('presta-product-upload-availability');
    Route::resource('/references-companies-templates','ReferencesCompaniesTemplateController');
    Route::post('/product/mass-process', 'ProductController@massProcess')->name('products.mass.process');
    Route::get('async-selector/customers','CustomerController@asyncSelector')->name('customers.async.selector');
    Route::get('async-selector/orders','OrderController@asyncSelector')->name('orders.async.selector');
    Route::get('products/copy/{product}','ProductController@copyProduct')->name('products.copy');
    Route::resource('/campaign-ids','CampaignIdController')->except(['show']);
    Route::get('/phone-calls','PhoneController@calls')->name('phone.calls');
    Route::resource('city', 'CityController');

    Route::prefix('dev')->name('http-tests.')->group(function () {
        Route::get('/lost-products', 'DevController@lostProducts');
        Route::get('/dev', 'DevController@debugging');
    });

    Route::resource('tickets', 'TicketController')->except('edit', 'delete');
    Route::resource('ticket-states', 'TicketStateController')->except('show','delete');
    Route::resource('ticket-priorities', 'TicketPriorityController')->except('show','delete');
    Route::resource('ticket-themes', 'TicketThemeController')->except('show','delete');
    Route::resource('ticket-messages','TicketMessageController')->only('index','store');
    Route::resource('ticket-event-criteria','TicketEventCriterionController')->except('show');
    Route::resource('ticket-event-actions','TicketEventActionController')->except('show');
    Route::resource('ticket-event-subscriptions','TicketEventSubscriptionController')->except('show');
    Route::resource('telephony-accounts','TelephonyAccountController')->except('show');
    Route::resource('telephony-account-groups','TelephonyAccountGroupController')->except('show');
    Route::resource('store-autotransfer-settings','StoreAutotransferSettingController');
    Route::get('store-autotransfer-settings/{id}/back','StoreAutotransferSettingController@showFromReserve')->name('store-autotransfer-settings.show.back');
    Route::resource('transfer-iterations','TransferIterationController')->only('show','index','store');
    Route::post('transfer-iterations/process','TransferIterationController@process')->name('transfer-iterations.process');
    Route::resource('order-alerts', 'OrderAlertController');
    Route::resource('overdue-tasks', 'OverdueTaskController');
    
    Route::get('urgent-call/edit', 'UrgentAlCallController@editNumber')->name('urgent-call.edit');
    Route::post('urgent-call/update/{configuration}', 'UrgentAlCallController@updateNumber')->name('urgent-call.update');

    Route::resource('substitute', 'SubstituteController');
    Route::resource('rule-order-permission', 'RuleOrderPermissionController');

    Route::get('telegram-delivery', 'TelegramDeliveryController@TelegramDeliveryTomorrow');
    Route::resource('substitute', 'SubstituteController');
});

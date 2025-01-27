<script>
    $(document).ready(function () {

        $('#filter_date').click(function () {
            orders_table.ajax.reload(); // Reload DataTable with the new date filters
        });

        $('#clear_date').click(function () {
            $('#start_date').val('');
            $('#end_date').val('');
            $('#business_location').val('');
            $('#delivery_name').val('');
            $('#status').val('all');
            $('#payment_status').val('all');
            $('#order_status').val('');
            orders_table.ajax.reload();
        });


        //Orders table
        var orders_table = $('#orders_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ action("ApplicationDashboard\OrderController@index") }}',
                data: function (d) {
                    d.status = $('#status').val();
                    d.order_status = $('#order_status').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.business_location = $('#business_location').val();
                    d.delivery_name = $('#delivery_name').val();
                    d.payment_status = $('#payment_status').val();
                }
            },
            columns: [
                {
                    data: null,
                    name: 'related_orders',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return `<button class="btn btn-sm btn-primary show-related-orders-btn" data-order-id="${row.id}">
                            View Related Orders
                        </button>`;
                    }
                },
                { data: 'id', name: 'id' },
                {
                    data: 'business_location_name', name: 'business_location_name'
                },
                { data: 'number', name: 'number' },
                { data: 'invoice_no', name: 'invoice_no' },
                {
                    data: 'client_contact_name', name: 'client_contact_name', orderable: true,
                    searchable: true
                }, // Ensure this matches the added column name
                { data: 'client_contact_mobile', name: 'client_contact_mobile' }, // Ensure this matches the added column name


                {
                    data: 'order_status',
                    name: 'order_status',
                    render: function (data, type, row) {
                        let badgeClass;
                        switch (data) {
                            case 'pending': badgeClass = 'badge btn-warning'; break;
                            case 'processing': badgeClass = 'badge btn-info'; break;
                            case 'shipped': badgeClass = 'badge btn-primary'; break;
                            case 'completed': badgeClass = 'badge btn-success'; break;
                            case 'cancelled': badgeClass = 'badge btn-danger'; break;
                            default: badgeClass = 'badge badge-secondary'; // For any other statuses
                        }

                        // Display only the badge for completed or cancelled statuses
                        if (data === 'shipped' || data === 'completed' || data === 'cancelled') {
                            return `<span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        }

                        // Otherwise, display both the badge and the select dropdown
                        if (data !== 'shipped' || data !== 'completed' || data !== 'cancelled') {
                            return `
                        <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                        <select class="form-control change-order-status" data-order-id="${row.id}">
                            <option value="pending" ${data === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="processing" ${data === 'processing' ? 'selected' : ''}>Processing</option>
                        </select>`;
                        }


                        // <option value="shipped" ${data === 'shipped' ? 'selected' : ''}>Shipped</option>
                        //     <option value="completed" ${data === 'completed' ? 'selected' : ''}>Completed</option>
                        //     <option value="cancelled" ${data === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    }
                },

                {
                    data: 'payment_status',
                    name: 'payment_status',
                    render: function (data, type, row) {
                        let value = '';  // Initialize value for badge
                        let select = ''; // Initialize select for dropdown

                        // Display badge based on payment_status
                        if (data === 'paid') {
                            value = `<span class="badge btn-success">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        } else if (data === 'failed') {
                            value = `<span class="badge btn-danger">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        } else {
                            value = `<span class="badge btn-info">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        }

                        // Show select dropdown only if order_status is completed and payment_status is not paid
                        if (row.order_status === 'completed' && data !== 'paid') {
                            select = `
                            <select class="form-control change-payment-status" data-order-id="${row.id}">
                                <option value="" selected disabled>Select Status</option>
                                <option value="paid" ${data === 'paid' ? 'selected' : ''}>Paid</option>
                                <option value="failed" ${data === 'failed' ? 'selected' : ''}>Failed</option>
                            </select>`;
                        }

                        return value + select;
                    }
                },

                { data: 'shipping_cost', name: 'shipping_cost' },
                { data: 'sub_total', name: 'sub_total' },
                { data: 'total', name: 'total' },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function (data) {
                        // Format the date using JavaScript
                        if (data) {
                            const date = new Date(data);
                            return date.toLocaleString(); // Adjust format as needed
                        }
                        return '';
                    }
                },
                {
                    data: 'has_delivery',
                    name: 'has_delivery',
                    render: function (data, type, row) {
                        // Case 1: If the order status is 'processing' and has no delivery assigned
                        if (row.order_status === 'processing' && row.has_delivery === false) {
                            return `<button class="btn btn-primary assign-delivery-btn" 
                    data-order-id="${row.id}" 
                    data-contact-name="${row.client_contact_name
                                } ">
                    @lang('lang_v1.assign_delivery')
                </button > `;
                        }
                        if (row.has_delivery === true) {
                            return `<div>
                        <span class="badge btn-secondary">
                        @lang('lang_v1.delivery_assigned')
                    </span>
                    <span class="badge btn-success">
                    ${row.delivery_name}
                    </span>
                        </div>`;
                        }

                        return '';
                    },
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'id',
                    name: 'id',
                    render: function (data, type, row) {
                        // Generate the "View Order Info" button
                        let buttons = `<button class="btn btn-info view-order-info-btn" data-order-id="${row.id}">
                          @lang('lang_v1.view_order_info')
                       </button>`;

                        // Conditionally add the "Refund Order" button
                        if (row.order_status === 'completed') {
                            buttons += `<button class="btn btn-warning refund-order-btn" data-order-id="${data}">
                            @lang('lang_v1.refund_order')
                        </button>`;
                        }

                        return buttons;
                    },
                    orderable: false,
                    searchable: false
                }



            ],
        });



        // Handle related orders button click
        $(document).on('click', '.show-related-orders-btn', function () {
            const button = $(this);
            const orderId = button.data('order-id');
            const tr = button.closest('tr');
            const row = orders_table.row(tr);

            // Check if related orders are already shown
            if (tr.next('.related-orders-row').length) {
                // If already shown, remove it
                tr.next('.related-orders-row').remove();
                return;
            }

            // Create a new row for related orders
            const relatedOrdersRow = $(`
        <tr class="related-orders-row">
            <td colspan="${orders_table.columns().count()}">
                <div id="relatedOrdersWrapper-${orderId}">
                    <table id="relatedOrdersTable-${orderId}" class="table table-striped">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.id')</th>
                                <th>@lang('lang_v1.order_type')</th>
                                <th>@lang('lang_v1.business_location')</th>
                                <th>@lang('lang_v1.number')</th>
                                 <th>@lang('lang_v1.invoice_no')</th>
                                <th>@lang('lang_v1.client')</th>
                                <th>@lang('lang_v1.client_number')</th>
                                <!-- <th>@lang('lang_v1.payment_method')</th> -->
                                <th>@lang('lang_v1.order_status')</th>
                                <th>@lang('lang_v1.payment_status')</th>
                                <th>@lang('lang_v1.shipping_cost')</th>
                                <th>@lang('lang_v1.sub_total')</th>
                                <th>@lang('lang_v1.total')</th>
                                <th>@lang('lang_v1.order_date_time')</th>
                                <th>@lang('lang_v1.assign_delivery')</th>
                                <th>@lang('lang_v1.actions')</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </td>
        </tr>
    `);

            tr.after(relatedOrdersRow);

            // Initialize DataTable for related orders
            $(`#relatedOrdersTable-${orderId}`).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `{{ action("ApplicationDashboard\OrderController@getRelatedOrders", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId),
                    type: 'GET'
                },
                columns: [
                    { data: 'id', name: 'id' },
                    {
                        data: 'order_type',
                        name: 'order_type',
                        render: function (data, type, row) {
                            // Determine the badge class based on the order type
                            let badgeClass;
                            switch (data) {
                                case 'order_transfer':
                                    badgeClass = 'badge btn-info';
                                    data = 'طلب تحويل';
                                    break;
                                case 'order_refund':
                                    badgeClass = 'badge btn-danger';
                                    data = 'طلب أسترجاع'
                                    break;
                                default:
                                    badgeClass = 'badge badge-secondary'; // For any other statuses
                            }

                            // Format the translation key dynamically
                            const translationKey = `lang_v1.${data}`;

                            // Return the badge with the translated label
                            return `
                                <span class="${badgeClass}">${data}</span>
                            `;
                        }
                    },

                    {
                        data: 'business_location_name', name: 'business_location_name'
                    },
                    { data: 'number', name: 'number' },
                    { data: 'invoice_no', name: 'invoice_no' },

                    {
                        data: 'client_contact_name', name: 'client_contact_name', orderable: true,
                        searchable: true
                    }, // Ensure this matches the added column name
                    { data: 'client_contact_mobile', name: 'client_contact_mobile' }, // Ensure this matches the added column name

                    {
                        data: 'order_status',
                        name: 'order_status',
                        render: function (data, type, row) {
                            let badgeClass;
                            switch (data) {
                                case 'pending': badgeClass = 'badge btn-warning'; break;
                                case 'processing': badgeClass = 'badge btn-info'; break;
                                case 'shipped': badgeClass = 'badge btn-primary'; break;
                                case 'completed': badgeClass = 'badge btn-success'; break;
                                case 'cancelled': badgeClass = 'badge btn-danger'; break;
                                default: badgeClass = 'badge badge-secondary'; // For any other statuses
                            }

                            // Display only the badge for completed or cancelled statuses
                            if (data === 'completed' || data === 'cancelled') {
                                return `<span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                            }


                            // Otherwise, display both the badge and the select dropdown
                            return `
                                <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                                <select class="form-control ${row.order_type === 'order_transfer' ? 'change-order-status' : 'change-refund-order-status'}" data-order-id="${row.id}">
                                    <option value="pending" ${data === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="processing" ${data === 'processing' ? 'selected' : ''}>Processing</option>
                                    <option value="shipped" ${data === 'shipped' ? 'selected' : ''}>Shipped</option>
                                    <option value="completed" ${data === 'completed' ? 'selected' : ''}>Completed</option>
                                    <option value="cancelled" ${data === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>`;
                        }
                    },

                    {
                        data: 'payment_status', name: 'payment_status', render: function (data, type, row) {

                            // Display only the badge for completed or cancelled statuses
                            if (data === 'paid') {
                                return `<span class="badge btn-success">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                            }

                            return `
            <select class="form-control ${row.order_type === 'order_transfer' ? 'change-payment-status' : 'change-refund-payment-status'}"
            data-order-id="${row.id}">
                <option value="pending" ${data === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="paid" ${data === 'paid' ? 'selected' : ''}>Paid</option>
                <option value="failed" ${data === 'failed' ? 'selected' : ''}>Failed</option>
            </select>`;
                        }
                    },
                    { data: 'shipping_cost', name: 'shipping_cost' },
                    { data: 'sub_total', name: 'sub_total' },
                    { data: 'total', name: 'total' },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function (data) {
                            // Format the date using JavaScript
                            if (data) {
                                const date = new Date(data);
                                return date.toLocaleString(); // Adjust format as needed
                            }
                            return '';
                        }
                    },
                    {
                        data: 'has_delivery',
                        name: 'has_delivery',
                        render: function (data, type, row) {
                            // Case 1: If the order status is 'processing' and has no delivery assigned
                            if (row.order_status === 'processing' && row.has_delivery === false) {
                                return `<button class="btn btn-primary assign-delivery-btn" 
                    data-order-id="${row.id}" 
                    data-contact-name="${row.client_contact_name
                                    } ">
                    @lang('lang_v1.assign_delivery')
                </button > `;
                            }
                            if (row.has_delivery === true) {
                                return `<div>
                        <span class="badge btn-secondary">
                        @lang('lang_v1.delivery_assigned')
                    </span>
                    <span class="badge btn-success">
                    ${row.delivery_name}
                    </span>
                        </div>`;
                            }

                            return '';
                        },
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id',
                        name: 'id',
                        render: function (data, type, row) {
                            // Generate the "View Order Info" button
                            let buttons = `<button class="btn btn-info view-order-info-btn" data-order-id="${row.id}">
                          @lang('lang_v1.view_order_info')
                       </button>`;

                            // Conditionally add the "Refund Order" button
                            if (row.order_status === 'completed') {
                                buttons += `<button class="btn btn-warning refund-order-btn" data-order-id="${data}">
                            @lang('lang_v1.refund_order')
                        </button>`;
                            }

                            return buttons;
                        },
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        });

        $(document).on('change', '.change-refund-order-status', function () {
            var orderId = $(this).data('order-id');
            var status = $(this).val();
            $.ajax({
                url: `{{ action("ApplicationDashboard\RefundOrderController@changeOrderStatus", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId), // Replacing the placeholder with the actual orderId
                type: 'POST',
                data: {
                    order_status: status,
                    _token: '{{ csrf_token() }}' // CSRF token for security
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        orders_table.ajax.reload(); // Reload DataTable to reflect the updated status
                    } else {
                        alert('Failed to update order status.');
                    }
                },
                error: function (xhr) {
                    alert('An error occurred: ' + xhr.responseText);
                }
            });
        });

        $(document).on('change', '.change-order-status', function () {
            var orderId = $(this).data('order-id');
            var status = $(this).val();

            $.ajax({
                url: `{{ action("ApplicationDashboard\OrderController@changeOrderStatus", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId), // Replacing the placeholder with the actual orderId
                type: 'POST',
                data: {
                    order_status: status,
                    _token: '{{ csrf_token() }}' // CSRF token for security
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        orders_table.ajax.reload(); // Reload DataTable to reflect the updated status
                    } else {
                        alert('Failed to update order status.');
                    }
                },
                error: function (xhr) {
                    alert('An error occurred: ' + xhr.responseText);
                }
            });
        });

        $(document).on('change', '.change-payment-status', function () {
            var orderId = $(this).data('order-id');
            var status = $(this).val();

            $.ajax({
                url: `{{ action("ApplicationDashboard\OrderController@changePaymentStatus", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId), // Replacing the placeholder with the actual orderId
                type: 'POST',
                data: {
                    payment_status: status,
                    _token: '{{ csrf_token() }}' // CSRF token for security
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        orders_table.ajax.reload(); // Reload DataTable to reflect the updated status
                    } else {
                        alert('Failed to update payment status.');
                    }
                },
                error: function (xhr) {
                    alert('An error occurred: ' + xhr.responseText);
                }
            });
        });


        $(document).on('change', '.change-refund-payment-status', function () {
            var orderId = $(this).data('order-id');
            var status = $(this).val();

            $.ajax({
                url: `{{ action("ApplicationDashboard\RefundOrderController@changePaymentStatus", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId), // Replacing the placeholder with the actual orderId
                type: 'POST',
                data: {
                    payment_status: status,
                    _token: '{{ csrf_token() }}' // CSRF token for security
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        orders_table.ajax.reload(); // Reload DataTable to reflect the updated status
                    } else {
                        alert('Failed to update payment status.');
                    }
                },
                error: function (xhr) {
                    alert('An error occurred: ' + xhr.responseText);
                }
            });
        });


        $(document).on('click', '.assign-delivery-btn', function () {
            var orderId = $(this).data('order-id');
            var contactName = $(this).data('contact-name'); // Get the contact name

            $('#order_id').val(orderId);

            // Set the contact name in the modal
            $('#contact_name_display').text(contactName); // Assume #contact_name_display is the placeholder for contact name

            // Fetch available deliveries
            $.ajax({
                url: `{{ action("ApplicationDashboard\DeliveryController@getAvailableDeliveries" , ['orderId' => ':orderId']) }}`.replace(':orderId', orderId),
                type: 'GET',
                success: function (response) {
                    if (response.success) {
                        var deliveryOptions = response.deliveries.map(delivery => {
                            return `<option value="${delivery.id}">${delivery.name}</option>`;
                        }).join('');
                        $('#delivery_id').html(deliveryOptions);
                        $('#assignDeliveryModal').modal('show');
                    } else {
                        alert('Failed to fetch deliveries.');
                    }
                },
                error: function () {
                    alert('An error occurred while fetching deliveries.');
                }
            });
        });


        // Event listener for saving the delivery assignment
        $('#saveDeliveryAssignment').click(function () {
            var formData = $('#assignDeliveryForm').serialize();

            $.ajax({
                url: '{{ action("ApplicationDashboard\DeliveryController@assignDelivery") }}',
                type: 'POST',
                data: formData + '&_token={{ csrf_token() }}',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        orders_table.ajax.reload();
                        $('#assignDeliveryModal').modal('hide');
                    } else {
                        alert('Failed to assign delivery.');
                    }
                },
                error: function () {
                    alert('An error occurred while assigning delivery.');
                }
            });
        });

        // Event listener for the 'View Order Info' button
        $(document).on('click', '.view-order-info-btn', function () {
            var orderId = $(this).data('order-id'); // Get the order ID

            // Fetch the order details
            $.ajax({
                url: `{{ action("ApplicationDashboard\OrderController@getOrderDetails", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId),
                type: 'GET',
                success: function (response) {
                    if (response.success) {
                        const date = new Date(response.order.created_at);
                        const orderDate = date.toLocaleString();
                        // Populate the modal with the order details
                        $('#view_order_id').val(response.order.id);
                        $('#order_number').text(response.order.number);
                        $('#business_location').text(response.order.business_location.name);
                        $('#client_name').text(response.order.client.contact.name);
                        $('#payment_method').text(response.order.payment_method);
                        $('#shipping_cost').text(response.order.shipping_cost);
                        $('#sub_total').text(response.order.sub_total);
                        $('#total').text(response.order.total);
                        $('#order_status').text(response.order.order_status);
                        $('#payment_status').text(response.order.payment_status);
                        $('#delivery_name').text(response.order.delivery?.contact.name);
                        $('#order_type').text(response.order.order_type);
                        $('#order_date').text(orderDate);
                        $('#invoice_no').text(response.order.transaction?.invoice_no);



                        // Populate the order items
                        const itemsTable = $('#order_items_table tbody');
                        itemsTable.empty(); // Clear existing rows

                        response.order.order_items.forEach(item => {
                            const row = `
                        <tr>
                            <td><img src="${item.product.image_url}" alt="${item.product.name}" style="width: 50px; height: 50px; object-fit: cover;"></td>
                            <td>${item.product.name}  - [${item.variation.name} ]</td>
                            <td>${item.quantity}</td>
                            <td>${item.price}</td>
                            <td>${item.sub_total}</td>
                        </tr>
                    `;
                            itemsTable.append(row);
                        });


                        // Populate the order items
                        const activityLogsTable = $('#activity_logs_table tbody');
                        activityLogsTable.empty(); // Clear existing rows

                        response.activityLogs.forEach(item => {
                            const date = new Date(item.created_at);
                            const formattedDate = date.toLocaleString();

                            const row = `
                        <tr>
                            <td>${item.subject.number}</td>
                            <td>
                            ${item.description}
                            </td>

                            <td>${item.properties.status || ''}</td>
                            <td>${item.created_by}</td>
                            <td>${formattedDate}
                            </td>
                        </tr>
                    `;
                            activityLogsTable.append(row);
                        });


                        // Show the modal
                        $('#viewOrderInfoModal').modal('show');
                    } else {
                        alert('Failed to fetch order details.');
                    }
                },
                error: function () {
                    alert('An error occurred while fetching the order details.');
                }
            });
        });

        $('#viewOrderInfoModal').on('hide.bs.modal', function () {
            // Clear all input fields
            $('#view_order_id').val('');

            // Clear text content
            $('#order_number, #business_location, #client_name, #payment_method, #shipping_cost, #sub_total, #total, #order_status, #payment_status, #delivery_name, #order_type, #order_date, #invoice_no').text('');

            // Clear the order items table
            $('#order_items_table tbody').empty();

            // Clear the activity logs table
            $('#activity_logs_table tbody').empty();
        });


        // Show refund modal
        $(document).on('click', '.refund-order-btn', function () {
            var orderId = $(this).data('order-id');

            // Populate hidden order ID field in the refund modal
            $('#refund_order_id').val(orderId);

            // Fetch the order details
            $.ajax({
                url: `{{ action("ApplicationDashboard\OrderController@getOrderDetails", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId),
                type: 'GET',
                success: function (response) {
                    if (response.success) {
                        const itemsTable = $('#order_items_table tbody');
                        itemsTable.empty(); // Clear existing rows

                        response.order.order_items.forEach(item => {
                            const row = `
                    <tr>
                        <td>
                            <img src="${item.product.image_url}" alt="${item.product.name}" style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td>Name: ${item.product.name} - (${item.variation.name}) / quantity: ${item.quantity}</td>
                        <td>
                            <textarea name="refund_reason_${item.id}" class="form-control refund-reason" rows="2" data-item-id="${item.id}"></textarea>
                        </td>
                        <td>
                            <div class="input-group">
                                <button type="button" class="btn btn-secondary decrement-btn" data-item-id="${item.id}">-</button>
                                <input type="text" name="refund_amount_${item.id}" class="form-control refund-amount" data-item-id="${item.id}" value="0" readonly>
                                <button type="button" class="btn btn-secondary increment-btn" data-item-id="${item.id}">+</button>
                            </div>
                        </td>
                        <td>
                            <select name="refund_status_${item.id}" class="form-control refund-status" data-item-id="${item.id}">
                                <option value="requested">@lang('lang_v1.Requested')</option>
                                <option value="processed">@lang('lang_v1.Processed')</option>
                                <option value="approved">@lang('lang_v1.Approved')</option>
                                <option value="rejected">@lang('lang_v1.Rejected')</option>
                            </select>
                        </td>
                        <td>
                            <textarea name="admin_response_${item.id}" class="form-control refund-admin-response" rows="2" data-item-id="${item.id}"></textarea>
                        </td>
                    </tr>
                    `;
                            // Append the row to the table
                            $('#order_items_table tbody').append(row);

                            // Dynamically set the max value for refund amount based on remaining quantity
                            $(`input[name="refund_amount_${item.id}"]`).attr('max', item.remaining_quantity);
                        });

                        // Show the modal
                        $('#refundOrderModal').modal('show');
                    } else {
                        alert('Failed to fetch order details.');
                    }
                },
                error: function () {
                    alert('An error occurred while fetching the order details.');
                }
            });
        });

        // Event handler for incrementing refund amount
        $(document).on('click', '.increment-btn', function () {
            var itemId = $(this).data('item-id');
            var $input = $(`input[name="refund_amount_${itemId}"]`);
            var currentValue = parseInt($input.val(), 10) || 0;
            var maxQuantity = parseInt($input.attr('max'), 10);

            if (currentValue < maxQuantity) {
                $input.val(currentValue + 1);
            }
        });

        // Event handler for decrementing refund amount
        $(document).on('click', '.decrement-btn', function () {
            var itemId = $(this).data('item-id');
            var $input = $(`input[name="refund_amount_${itemId}"]`);
            var currentValue = parseInt($input.val(), 10) || 0;

            if (currentValue > 0) {
                $input.val(currentValue - 1);
            }
        });


        $('#saveRefund').click(function () {
            const items = [];

            $('#order_items_table tbody tr').each(function () {
                const itemId = $(this).find('.refund-reason').data('item-id');
                const reason = $(this).find('.refund-reason').val().trim();
                const amount = $(this).find('.refund-amount').val().trim();  // Ensure to trim any extra spaces
                const status = $(this).find('.refund-status').val();
                const adminResponse = $(this).find('.refund-admin-response').val().trim();

                // Skip rows where any required data is empty
                if (itemId && reason && amount && status && adminResponse) {
                    items.push({
                        id: itemId,
                        refund_reason: reason,
                        refund_amount: amount,
                        refund_status: status,
                        refund_admin_response: adminResponse,
                    });
                }
            });

            if (items.length === 0) {
                alert('Please fill in refund details for at least one item.');
                return;
            }

            console.log('Final Items Array:', items);

            $.ajax({
                url: '{{ action("ApplicationDashboard\OrderRefundController@store") }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    _token: '{{ csrf_token() }}',
                    order_id: $('#refund_order_id').val(),
                    items: items,
                }),
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#refundOrderModal').modal('hide');
                        orders_table.ajax.reload();
                        // Reload DataTable or update UI
                    } else {
                        alert('Failed to process refund.');
                    }
                },
                error: function () {
                    alert('An error occurred while processing the refund.');
                }
            });
        });

    });

    function printOrderDetails() {
        var modalContent = document.querySelector('#viewOrderInfoModal .modal-content').innerHTML;
        var originalContent = document.body.innerHTML;

        document.body.innerHTML = modalContent;
        window.print();
        document.body.innerHTML = originalContent;

        // Reinitialize scripts after restoring the content (if needed)
        location.reload(); // Refresh to restore event listeners and modal functionality
    }


    document.addEventListener('DOMContentLoaded', function () {
        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0];

        // Set the default value for start_date and end_date
        document.getElementById('statistics_start_date').value = today;
        document.getElementById('statistics_end_date').value = today;
    });

    $('#orderStatisticsModal').on('show.bs.modal', function () {
        fetchStatistics();
    });

    // Fetch statistics on filter button click
    $('#filter_statistics').click(function () {
        fetchStatistics();
    });

    function fetchStatistics() {
        const startDate = $('#statistics_start_date').val();
        const endDate = $('#statistics_end_date').val();

        $.ajax({
            url: '{{ route("orders.statistics") }}',
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;

                    // Ensure values are numbers before calling toFixed
                    const totalOrdersAmount = parseFloat(data.total_orders_amount) || 0;
                    const refundOrdersAmount = parseFloat(data.refund_orders_amount) || 0;
                    const transferOrdersAmount = parseFloat(data.transfer_orders_amount) || 0;
                    const cancelledOrdersAmount = parseFloat(data.cancelled_orders_amount) || 0;
                    const netTotalAmount = parseFloat(data.net_total_amount) || 0;
                    const totalCompletedPaidOrdersAmount = parseFloat(data.total_completed_paid_orders_amount) || 0;
                    const totalCompletedNotPaidOrdersAmount = parseFloat(data.total_completed_not_paid_orders_amount) || 0;

                    // Update the UI with the fetched statistics
                    $('#total_orders_count').text(data.total_orders_count || 0);
                    $('#total_orders_amount').text('$' + totalOrdersAmount.toFixed(2));
                    $('#total_completed_paid_orders_count').text(data.total_completed_paid_orders_count || 0);
                    $('#total_completed_paid_orders_amount').text('$' + totalCompletedPaidOrdersAmount.toFixed(2));
                    $('#total_completed_not_paid_orders_count').text(data.total_completed_not_paid_orders_count || 0);
                    $('#total_completed_not_paid_orders_amount').text('$' + totalCompletedNotPaidOrdersAmount.toFixed(2));
                    $('#total_orders_count').text(data.total_orders_count || 0);
                    $('#total_orders_amount').text('$' + totalOrdersAmount.toFixed(2));
                    $('#refund_orders_count').text(data.refund_orders_count || 0);
                    $('#refund_orders_amount').text('$' + refundOrdersAmount.toFixed(2));
                    $('#transfer_orders_count').text(data.transfer_orders_count || 0);
                    $('#transfer_orders_amount').text('$' + transferOrdersAmount.toFixed(2));
                    $('#cancelled_orders_count').text(data.cancelled_orders_count || 0);
                    $('#cancelled_orders_amount').text('$' + cancelledOrdersAmount.toFixed(2));
                    $('#net_total_amount').text('$' + netTotalAmount.toFixed(2));
                } else {
                    alert('Failed to fetch statistics.');
                }
            },
            error: function () {
                alert('An error occurred while fetching statistics.');
            }
        });
    }
</script>
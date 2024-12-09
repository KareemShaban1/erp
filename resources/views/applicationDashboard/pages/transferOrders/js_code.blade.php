<script>

$(document).ready(function(){

    $('#filter_date').click(function () {
        order_transfer_table.ajax.reload(); // Reload DataTable with the new date filters
    });

    $('#clear_date').click(function () {
        $('#start_date').val('');
        $('#end_date').val('');
        $('#business_location').val('');
        $('#delivery_name').val('');
        $('#status').val('all');
        $('#payment_status').val('all');
        order_transfer_table.ajax.reload();
    });


    //Orders table
    var order_transfer_table = $('#order_transfer_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action("ApplicationDashboard\TransferOrderController@index") }}',
            data: function (d) {
                d.status = $('#status').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.business_location = $('#business_location').val();
                d.delivery_name = $('#delivery_name').val();
                d.payment_status = $('#payment_status').val();
            }
        },
        columnDefs: [
            {
                targets: 2,
                orderable: true,
                searchable: true,
            },
        ],
        columns: [
            { data: 'id', name: 'id' },
            // { data: 'business_location.name', name: 'business_location.name' },
            { data: 'number', name: 'number' },
            { data: 'client_contact_name', name: 'client_contact_name' }, // Ensure this matches the added column name
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
            <select class="form-control change-order-status" data-order-id="${row.id}">
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
                    return `
            <select class="form-control change-payment-status" data-order-id="${row.id}">
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
                data: 'order_status',
                name: 'order_status',
                render: function (data, type, row) {
                    // Case 1: If the order status is 'processing' and has no delivery assigned
                    if (data === 'processing' && row.has_delivery === false) {
                        return `<button class="btn btn-primary assign-delivery-btn" 
                    data-order-id="${row.id}" 
                    data-contact-name="${row.client_contact_name
                            } ">
                    @lang('lang_v1.assign_delivery')
                </button > `;
                    }
                    if (row.has_delivery === true) {
                        return `<span class="badge badge-success">
                        @lang('lang_v1.delivery_assigned')
                    </span>`;
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

                    return buttons;
                },
                orderable: false,
                searchable: false
            }



        ],

        fnDrawCallback: function (oSettings) {
            __currency_convert_recursively($('#order_transfer_table'));
        },
    });


    $(document).on('change', '.change-order-status', function () {
        var orderId = $(this).data('order-id');
        var status = $(this).val();

        $.ajax({
            url: `{{ action("ApplicationDashboard\TransferOrderController@changeOrderStatus", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId), // Replacing the placeholder with the actual orderId
            type: 'POST',
            data: {
                order_status: status,
                _token: '{{ csrf_token() }}' // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    order_transfer_table.ajax.reload(); // Reload DataTable to reflect the updated status
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
            url: `{{ action("ApplicationDashboard\TransferOrderController@changePaymentStatus", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId), // Replacing the placeholder with the actual orderId
            type: 'POST',
            data: {
                payment_status: status,
                _token: '{{ csrf_token() }}' // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    order_transfer_table.ajax.reload(); // Reload DataTable to reflect the updated status
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
                    order_transfer_table.ajax.reload();
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
            url: `{{ action("ApplicationDashboard\TransferOrderController@getOrderTransferDetails", ['orderId' => ':orderId']) }}`.replace(':orderId', orderId),
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const date = new Date(response.order.created_at);
                    const transferOrderDate = date.toLocaleString();
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
                    $('#order_type').text(response.order.order_type);
                    $('#order_refund_date').text(transferOrderDate);
                    $('#invoice_no').text(response.order.transaction?.invoice_no);

                    $('#from_location_name').text(response.order.from_business_location?.name);
                    $('#from_location_city').text(response.order.from_business_location?.city);
                    $('#from_location_mobile').text(response.order.from_business_location?.mobile);

                    $('#to_location_name').text(response.order.to_business_location?.name);
                    $('#to_location_city').text(response.order.to_business_location?.city);
                    $('#to_location_mobile').text(response.order.to_business_location?.mobile);

                    // Populate the order items
                    const itemsTable = $('#order_items_table tbody');
                    itemsTable.empty(); // Clear existing rows

                    response.order.order_items.forEach(item => {
                        const row = `
                        <tr>
                            <td><img src="${item.product.image_url}" alt="${item.product.name}" style="width: 50px; height: 50px; object-fit: cover;"></td>
                            <td>${item.product.name}</td>
                            <td>${item.quantity}</td>
                            <td>${item.price}</td>
                            <td>${item.sub_total}</td>
                        </tr>
                    `;
                        itemsTable.append(row);
                    });

                    const activityLogsTable = $('#activity_logs_table tbody');
                    activityLogsTable.empty(); // Clear existing rows

                    response.activityLogs.forEach(item => {
                        const date = new Date(item.created_at);
                        const formattedDate = date.toLocaleString();

                        const row = `
                        <tr>
                            <td>${item.properties?.order_number || item.properties?.number} </td>
                            <td>
                            ${item.description}
                            </td>

                            <td>${item.properties.status}</td>
                            <td>${item.created_by}</td>
                            <td>${formattedDate}
                            </td>
                        </tr>
                    `;
                        activityLogsTable.append(row);
                    });

                    // Show the modal
                    $('#viewOrderTransferInfoModal').modal('show');
                } else {
                    alert('Failed to fetch order details.');
                }
            },
            error: function () {
                alert('An error occurred while fetching the order details.');
            }
        });
    });


    $('#viewOrderTransferInfoModal').on('hide.bs.modal', function () {
        // Clear all input fields
        $('#view_order_id').val('');

        // Clear text content
        $('#order_number, #business_location, #client_name, #payment_method, #shipping_cost, #sub_total, #total, #order_status, #payment_status, #delivery_name, #order_type, #order_date, #invoice_no, #from_location_name, #from_location_city, #from_location_mobile, #to_location_name, #to_location_city, #to_location_mobile').text('');

        // Clear the order items table
        $('#order_items_table tbody').empty();

        // Clear the activity logs table
        $('#activity_logs_table tbody').empty();
    });

});

</script>
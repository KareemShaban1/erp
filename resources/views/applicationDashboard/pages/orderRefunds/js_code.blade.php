@section('javascript')
    <script>

        $(document).ready(function () {
            var userPermissions = @json(auth()->user()->getAllPermissions()->pluck('name'));
            var isSuperAdmin = @json(auth()->user()->isSuperAdmin());

            $('#filter_date').click(function () {
                order_refunds_table.ajax.reload(); // Reload DataTable with the new date filters
            });
            $('#clear_date').click(function () {
                $('#start_date').val('');
                $('#end_date').val('');
                order_refunds_table.ajax.reload();
            });
            //Orders table
            var order_refunds_table = $('#order_refunds_table').DataTable({
                processing: true,
                serverSide: true,

                ajax: {
                    url: '{{ action("ApplicationDashboard\OrderRefundController@index") }}',
                    data: function (d) {
                        d.status = $('#status').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'order_number', name: 'order.number' },
                    { data: 'amount', name: 'amount' },
                    {
                        data: 'order_item', name: 'order_item',
                        render: function (data) {
                            try {
                                // Parse the order_item JSON string
                                let orderItem = JSON.parse(data);

                                // Check if the parsed data and product properties exist
                                if (orderItem && orderItem.product && orderItem.product.name && orderItem.quantity) {
                                    return `${orderItem.product.name} (${orderItem.variation.name}) (${orderItem.quantity})`;
                                }
                            } catch (e) {
                                console.error('Error parsing order_item:', e);
                            }

                            return 'N/A'; // If parsing fails or the required data is missing, return 'N/A'
                        },
                        searchable: true,
                    },


                    { data: 'client_contact_name', name: 'client_contact_name', searchable: true, }, // Ensure this matches the added column name
                    {
                        data: 'status',
                        name: 'status',
                        render: function (data, type, row) {
                            let badgeClass;
                            switch (data) {
                                case 'requested':
                                    badgeClass = 'badge btn-warning';
                                    break;
                                case 'approved':
                                    badgeClass = 'badge btn-primary';
                                    break;
                                case 'rejected':
                                    badgeClass = 'badge btn-danger';
                                    break;
                                default:
                                    badgeClass = 'badge badge-secondary'; // For any other statuses
                            }

                            // Badge for the status
                            let output = `
                <span class="${badgeClass}">
                    ${data.charAt(0).toUpperCase() + data.slice(1)}
                </span>
            `;

                            // Add select dropdown if status is not "approved"
                            if (data !== 'approved') {
                                output += `
                    <select class="form-control change-refund-status" 
                            data-order-refund-id="${row.id}">
                        <option value="requested" ${data === 'requested' ? 'selected' : ''}>Requested</option>
                        <option value="approved" ${data === 'approved' ? 'selected' : ''}>Approved</option>
                        <option value="rejected" ${data === 'rejected' ? 'selected' : ''}>Rejected</option>
                    </select>
                `;
                            }

                            return output;
                        }
                    },



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
                        data: 'id',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            // Initialize an empty string to accumulate buttons
                            let buttons = '';

                            // refund_reasons
                            if (userPermissions.includes('refund_reasons.update') || isSuperAdmin) {
                                // Add the "Edit Order Refund" button
                                buttons += `<button class="btn btn-sm btn-primary edit-order-refund" 
                            data-id="${row.id}">
                            @lang('lang_v1.edit')
                            </button>`;
                            }


                            // Add the "View Order Info" button
                            buttons += `<button class="btn btn-info view-order-refund-info-btn" 
                            data-order-id="${row.id}">
                            @lang('lang_v1.view_order_info')
                            </button>`;

                            return buttons;
                        }

                    }
                    // other columns as needed
                ]
            });





            $(document).on('change', '.change-refund-status', function () {
                var orderRefundId = $(this).data('order-refund-id');
                var status = $(this).val();

                $.ajax({
                    url: `{{ action("ApplicationDashboard\OrderRefundController@changeOrderRefundStatus", ['orderRefundId' => ':orderRefundId']) }}`.replace(':orderRefundId', orderRefundId), // Replacing the placeholder with the actual orderId
                    type: 'POST',
                    data: {
                        status: status,
                        _token: '{{ csrf_token() }}' // CSRF token for security
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // alert(response.message);
                            order_refunds_table.ajax.reload(); // Reload DataTable to reflect the updated status
                        } else {
                            alert('Failed to update order status.');
                        }
                    },
                    error: function (xhr) {
                        alert('An error occurred: ' + xhr.responseText);
                    }
                });
            });


            $(document).on('click', '.edit-order-refund', function () {
                var orderRefundId = $(this).data('id');
                var url = $(this).data('url');

                // Fetch current order refund data using AJAX
                $.ajax({
                    // url: `applicationDashboard/order-refunds/${orderRefundId}`,
                    url: `{{ action("ApplicationDashboard\OrderRefundController@show", ['order_refund' => ':order_refund']) }}`.replace(':order_refund', orderRefundId), // Replacing the placeholder with the actual orderId
                    type: 'GET',
                    success: function (data) {
                        // Populate form fields with existing data
                        $('#orderRefundId').val(data.id);
                        $('#orderRefundStatus').val(data.status);
                        $('#orderRefundReason').val(data.reason);
                        $('#orderRefundResponse').val(data.admin_response);

                        // Open modal
                        $('#editOrderRefundModal').modal('show');
                    },
                    error: function () {
                        alert('Failed to fetch order refund data.');
                    }
                });
            });

            $('#editOrderRefundForm').submit(function (e) {
                e.preventDefault();
                var orderRefundId = $('#orderRefundId').val();
                var status = $('#orderRefundStatus').val();
                var reason = $('#orderRefundReason').val();
                var admin_response = $('#orderRefundResponse').val();

                // Send updated data to the server
                $.ajax({
                    // url: `/order-refunds/${orderRefundId}`,
                    url: `{{ action("ApplicationDashboard\OrderRefundController@update", ['order_refund' => ':order_refund']) }}`.replace(':order_refund', orderRefundId), // Replacing the placeholder with the actual orderId
                    type: 'PUT',
                    data: {
                        status: status,
                        reason: reason,
                        admin_response: admin_response,
                        _token: '{{ csrf_token() }}' // CSRF token for security
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#editOrderRefundModal').modal('hide');
                            order_refunds_table.ajax.reload(); // Refresh the DataTable
                        } else {
                            alert('Failed to update order refund data.');
                        }
                    },
                    error: function (xhr) {
                        alert('An error occurred: ' + xhr.responseText);
                    }
                });
            });


            $(document).on('click', '.view-order-refund-info-btn', function () {
                var orderRefundId = $(this).data('order-id'); // Get the order ID

                // Fetch the order details
                $.ajax({
                    url: `{{ action("ApplicationDashboard\OrderRefundController@getRefundDetails", ['orderRefundId' => ':orderRefundId']) }}`.replace(':orderRefundId', orderRefundId),
                    type: 'GET',
                    success: function (response) {
                        if (response.success) {
                            const date = new Date(response.order_refund.order.created_at);
                            const orderRefundDate = date.toLocaleString();
                            // Populate the modal with the order details
                            $('#view_order_id').val(response.order_refund.order.id);
                            $('#order_number').text(response.order_refund.order.number);
                            $('#business_location').text(response.order_refund.order.business_location.name);
                            $('#client_name').text(response.order_refund.order.client.contact.name);
                            $('#payment_method').text(response.order_refund.order.payment_method);
                            $('#shipping_cost').text(response.order_refund.order.shipping_cost);
                            $('#sub_total').text(response.order_refund.order.sub_total);
                            $('#total').text(response.order_refund.order.total);
                            $('#order_status').text(response.order_refund.order.order_status);
                            $('#payment_status').text(response.order_refund.order.payment_status);
                            $('#delivery_name').text(response.order_refund.order.delivery?.contact.name);
                            $('#order_type').text(response.order_refund.order.order_type);
                            $('#order_refund_date').text(orderRefundDate);


                            // Populate the order items
                            const itemsTable = $('#order_items_table tbody');
                            itemsTable.empty(); // Clear existing rows

                            response.order_refund.order.order_items.forEach(item => {
                                const row = `
                            <tr>
                                <td><img src="${item.product.image_url}" alt="${item.product.name}" style="width: 50px; height: 50px; object-fit: cover;"></td>
                                <td>${item.product.name}</td>
                                <td>${response.order_refund.amount}</td>
                                <td>${item.price}</td>
                                <td>${response.order_refund.amount * item.price}</td>
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
                            $('#viewOrderRefundInfoModal').modal('show');
                        } else {
                            alert('Failed to fetch order details.');
                        }
                    },
                    error: function () {
                        alert('An error occurred while fetching the order details.');
                    }
                });
            });

        });

    </script>
@endsection
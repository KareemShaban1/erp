@can('category.view')
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="category_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@if(!empty($module_category_data['taxonomy_label']))
                        {{$module_category_data['taxonomy_label']}}
                        @else
                        @lang( 'category.category' )
                        @endif
                    </th>
                    @if($is_cat_code_enabled)
                        <th>{{ $module_category_data['taxonomy_code_label'] ?? __( 'category.code' )}}</th>
                    @endif
                    <th>@lang( 'lang_v1.description' )</th>
                    <th>@lang( 'messages.action' )</th>
                </tr>
            </thead>
        </table>
    </div>
@endcan
